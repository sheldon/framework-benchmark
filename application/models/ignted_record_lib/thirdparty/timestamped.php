<?php
/*
 * Created on 2008 June 30
 * by Jonas Flodén <jonas@koalasoft.se>
 */
/*
 * Copyright (c) 2008, Jonas Flodén
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * The name of Jonas Flodén may not be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY Jonas Flodén ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL Jonas Flodén BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
/**
 * @addtogroup IgnitedRecord
 * @{
 */
/**
 * Timestamp behaviour for IgnitedRecord
 *
 * Provides automatic timestamps for the time of creation and last update of the record
 *
 * Usage:
 *  var $act_as = 'timestamped'; - Use default table columns: created_at and updated_at
 *  var $act_as = array('timestamped'=>array('option'=>'value',..)); - To specify options
 *
 * The following options are available:
 * created_at - Specify the column for the creation timestamp, if set to false creation timestamps will not be used, default: 'created_at'
 * updated_at - Specify the column for the last update timestamp, if set to false last update timestamps will not be used, default: 'updated_at'
 * localtime - If set to true timestamps will be in local time, if false timestamps will be in UTC, default: false
 *
 * The timestamps will be added to IR_record object with the defined column names.
 * So with the default column names:
 * echo object->updated_at;
 * Note: Even though these values can be changed, the changes will not propagate to the database on save().
 * So the following code can be a little confusing:
 * $obj = $this->my_model->new_record();
 * $obj->prop = value;
 * $obj->save();
 * $obj->created_at = '1980-01-01 00:00';
 * $obj->save();
 * echo $obj->created_at; // This will print 1980-01-01 00:00 even though the timestamp in the database is different
 *
 *
 * @author Jonas Flodén <jonas@koalasoft.se>
 * @par Copyright
 * Copyright (c) 2008, Jonas Flodén <jonas@koalasoft.se>
 *
 * Revisions
 * 2008-06-30 Initial version
 * 2008-07-03 Pass parameters by reference for PHP4 compatibility (by Martin Wernstahl)
 * 2008-07-28 Add option to use local time instead of UTC and make both fields optional
 * 2008-08-08 Fixed bug: Object was not updated with correct timestamp after save
 *
 */
class IgnitedRecord_timestamped
{
	var $__created_at;
	var $__updated_at;
	var $__localtime;

	function IgnitedRecord_timestamped(&$ORM, $opts)
	{
		// set opts
		$this->__created_at = isset($opts['created_at']) ? $opts['created_at'] : 'created_at';
		$this->__updated_at = isset($opts['updated_at']) ? $opts['updated_at'] : 'updated_at';
		$this->__localtime = isset($opts['localtime']) ? $opts['localtime'] : false;

		// hooks
		$ORM->add_hook('save_pre_insert',array(&$this,'_pre_insert'));
		$ORM->add_hook('save_pre_update',array(&$this,'_pre_update'));
		$ORM->add_hook('save_post_insert',array(&$this,'_post_insert'));
		$ORM->add_hook('save_post_update',array(&$this,'_post_update'));
		log_message('debug','IgnitedRecord: Behaviour class IgnitedRecord_timestamped has been initialized');
	}

	function _set_time(&$data, &$field, $update = false)
	{
		if ($update)
			$this->__time = ($this->__localtime) ? date('Y-m-d H:i:s') : gmdate('Y-m-d H:i:s');

		if ($field !== false)
		{
			if (is_array($data))
				$data[$field] = $this->__time;
			else if (is_object($data))
				$data->$field = $this->__time;
		}
	}

	function _pre_insert(&$data)
	{
		$this->_set_time($data, $this->__created_at, true);
		$this->_set_time($data, $this->__updated_at);
	}

	function _pre_update(&$data)
	{
		$this->_set_time($data, $this->__updated_at, true);
		// Prevent user from updating the 'created_at' column
		if ($this->__created_at !== false)
			unset($data[$this->__created_at]);
	}

	function _post_insert(&$object)
	{
		$this->_set_time($object, $this->__created_at);
		$this->_set_time($object, $this->__updated_at);
	}

	function _post_update(&$object)
	{
		$this->_set_time($object, $this->__updated_at);
	}
}
/**
 * @}
 */
/* End of file timestamped.php */