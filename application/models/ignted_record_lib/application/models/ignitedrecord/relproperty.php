<?php 
/*
 * Created on 2008 Jul 10
 * by Martin Wernstahl <m4rw3r@gmail.com>
 */

/*
 * Copyright (c) 2008, Martin Wernstahl
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * The name of Martin Wernstahl may not be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY Martin Wernstahl ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL Martin Wernstahl BE LIABLE FOR ANY
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
 * Stores the properties for a Belongs To relationship.
 * 
 * @since 0.2.0
 * @author Martin Wernstahl <m4rw3r@gmail.com>
 * @par Copyright
 * Copyright (c) 2008, Martin Wernstahl <m4rw3r@gmail.com>
 */
class IR_RelProperty{
	/**
	 * The table to relate to.
	 */
	var $table;
	
	/**
	 * The model name to use when creating related objects.
	 */
	var $model;
	
	/**
	 * The column that the other column relates to.
	 */
	var $fk;
	
	// --------------------------------------------------------------------
		
	/**
	 * Loads the settings array.
	 *
	 * @param $settings The settings
	 */
	function IR_RelProperty($settings = array())
	{
		if(isset($settings['table']))
		{
			$this->table = $settings['table'];
		}
		if(isset($settings['model']))
		{
			$this->model = $settings['model'];
		}
		if(isset($settings['foreign_key']))
		{
			$this->fk = $settings['foreign_key'];
		}
		if(isset($settings['fk']))
		{
			$this->fk = $settings['fk'];
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Initializes this model with a plural name.
	 * 
	 * The plural is assigned to $this->table if it isn't already set
	 * 
	 * @access private
	 * @param $plural The name
	 */
	function plural($plural)
	{
		if( ! isset($this->table))
			$this->table = $plural;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Initializes this model with a singular name.
	 * 
	 * The singular is assigned to $this->model if it isn't already set
	 * 
	 * @access private
	 * @param $plural The name
	 */
	function singular($singular)
	{
		if( ! isset($this->model))
			$this->model = $singular;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Sets the model name for this relation.
	 * 
	 * @param $model The modelname
	 * @return $this
	 */
	function &model($model)
	{
		$this->model = $model;
		return $this;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Sets the table name for this relation.
	 * 
	 * @param $table The table name
	 * @return $this
	 */
	function &table($table)
	{
		$this->table = $table;
		return $this;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Sets the foreign key column for this relation.
	 * 
	 * @param $col The column name
	 * @return $this
	 */
	function &foreign_key($col)
	{
		$this->fk = $col;
		return $this;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Sets the foreign key column for this relation.
	 * 
	 * Alias for column().
	 * 
	 * @param $col The column name
	 * @return $this
	 */
	function &fk($col)
	{
		return $this->column($col);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Returns the table used by this relation.
	 * 
	 * @return string
	 */
	function get_table()
	{
		return isset($this->table) ? $this->table : plural($this->model);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Returns the model used by this relation.
	 * 
	 * @return string
	 */
	function get_model()
	{
		if(isset($this->model))
			return $this->model;
		$this->model = $this->_get_modelname($this->table);
		return $this->model;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Returns the foreign key used by this relation.
	 * 
	 * @return string
	 */
	function get_fk()
	{
		return isset($this->fk)
				? $this->fk
				: ($this->get_model() == false
					? singular($this->get_table())
					: $this->get_model()
				  ).'_id';
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Returns the modelname of the tablename.
	 * 
	 * Tries if a model with $tablename exists, then tries with singular form of the $tablename. \n
	 * Called if no model is defined for a table.
	 * 
	 * @access private
	 * @param $tablename The tablename to find a modelname of.
	 * @return The modelname or '' if no model is found
	 */
	function _get_modelname($tablename)
	{
		$tablename = strtolower($tablename);
		$model = false;
		
		// check the CI props for an initialized IR model with that name
		$CI =& get_instance();
		
		foreach(array(singular($tablename), $tablename, singular($tablename).'_model', $tablename.'_model') as $name)
		{
			if(isset($CI->$name) && (is_a($CI->$name,'IgnitedRecord') OR is_a($CI->$name,'Model')))
			{
				$model = $name;
				break;
			}
			if(file_exists(APPPATH.'/models/'.$name.EXT))
			{
				$model = $name;
				break;
			}
		}
		
		return $model;
	}
}

/**
 * Stores the properties for a Has ... relationship.
 * 
 * @since 0.2.0
 * @author Martin Wernstahl <m4rw3r@gmail.com>
 * @par Copyright
 * Copyright (c) 2008, Martin Wernstahl <m4rw3r@gmail.com>
 */
class IR_RelProperty_has extends IR_RelProperty{
	/**
	 * The mode that utilizes this object.
	 * Used to get the default column name to relate with.
	 */
	var $parent_model;
	
	// --------------------------------------------------------------------
		
	/**
	 * Returns the column used by this relation.
	 * 
	 * @return string
	 */
	function get_fk()
	{
		return isset($this->fk) ? $this->fk : $this->parent_model->get_modelname().'_id';
	}
}

/**
 * Stores the properties for a Has And Belongs To Many relationship.
 * 
 * @since 0.2.0
 * @author Martin Wernstahl <m4rw3r@gmail.com>
 * @par Copyright
 * Copyright (c) 2008, Martin Wernstahl <m4rw3r@gmail.com>
 */
class IR_RelProperty_habtm extends IR_RelProperty_has{
	/**
	 * The foreign key linking the join table with the related table.
	 */
	var $related_fk;
	
	/**
	 * The join table name.
	 */
	var $join_table;
	
	/**
	 * The table of the model that utilizes this object.
	 * Used to get the default join-table name.
	 */
	var $parent_table;
	
	// --------------------------------------------------------------------
		
	/**
	 * Also inits the foreign columns.
	 */
	function IR_RelProp_habtm($settings = array())
	{
		parent::IR_RelProp($settings);
		foreach(array('related_foreign_key', 'related_fk', 'r_fk') as $key)
		{
			if(isset($settings[$key]))
			{
				$this->related_fk = $settings[$key];
			}
		}
		
		if(isset($settings['join_table']))
		{
			$this->join_table = $settings['join_table'];
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Sets the table used for linking the two tables together.
	 * 
	 * @param $table The table
	 * @return $this
	 */
	function &join_table($table)
	{
		$this->join_table = $table;
		return $this;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Returns the table name used in linking the two tables.
	 * 
	 * @return string
	 */
	function get_join_table()
	{
		return isset($this->join_table) ? $this->join_table :
				(strcmp($this->parent_table, $this->get_table()) < 0 ? $this->parent_table.'_'.$this->get_table() : $this->get_table().'_'.$this->parent_table);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Sets the foreign key the related table relates to in the relation table.
	 * 
	 * @param $col The column
	 * @return $this
	 */
	function &related_foreign_key($col)
	{
		$this->related_fk = $col;
		return $this;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Alias for related_foreign_key().
	 * 
	 * @param $col The column
	 * @return $this
	 */
	function related_fk($col)
	{
		return $this->related_foreign_key($col);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Alias for related_foreign_key().
	 * 
	 * @param $col The column
	 * @return $this
	 */
	function r_fk($col)
	{
		return $this->related_foreign_key($col);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Returns the column name of the column that is linking the relation table and the foreign table.
	 * 
	 * @return string
	 */
	function get_related_fk()
	{
		return isset($this->related_fk)
				? $this->related_fk
				: ($this->get_model() == false
					? singular($this->get_table())
					: $this->get_model()
				  ).'_id';
	}
}
/**
 * @}
 */

/* End of file relproperty.php */
/* Location: ./application/models/ignitedrecord/relproperty.php */