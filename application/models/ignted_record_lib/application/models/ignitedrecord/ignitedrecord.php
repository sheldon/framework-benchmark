<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * Created on 2008 Mar 28
 * by Martin Wernstahl <m4rw3r@gmail.com>
 */

/**
 * @page BSD_LICENSE BSD License
 * @code
 * 
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
 * 
 * @endcode
 */

/**
 * @addtogroup IgnitedRecord
 * @{
 * An Object-Relational-Mapper library, similar to Ruby on Rails' ActiveRecord class.
 * 
 * @version 0.2.0
 * 
 * @author Martin Wernstahl <m4rw3r@gmail.com>
 * 
 * @par Copyright
 * Copyright (c) 2008, Martin Wernstahl <m4rw3r@gmail.com>
 * 
 * @par License:
 * Released under the BSD Lisence: @ref BSD_LICENSE
 * 
 * @link http://www.assembla.com/spaces/IgnitedRecord
 * 
 * @par PHP Version required:
 * PHP 4.3.2 or greater
 */

/**
 * Include the wrapper for ActiveRecord.
 */
if(defined('IR_USE_IQ') && IR_USE_IQ === true)
{
	// IgnitedQuery based wrapper
	require_once 'base_iq.php';
}
else
{
	// CodeIgniter's ActiveRecord based wrapper
	require_once 'base.php';
}

/**
 * Include the objects to hold data.
 */
require_once 'record.php';

/**
 * Include relation property classes.
 */
require_once 'relproperty.php';

/**
 * Include relation fetching object.
 */
require_once 'relquery.php';

/**
 * A model for ORM, extend to use.
 * 
 * Uses the CodeIgniter inflector helper to pluralize / singularize modelname / tablename.
 * 
 * @author Martin Wernstahl <m4rw3r@gmail.com>
 * @par Copyright
 * Copyright (c) 2008, Martin Wernstahl <m4rw3r@gmail.com>
 * 
 * @todo Edit the relation code to support multiple primary keys
 * @todo Check the return value of save() in all code that calls save()
 */
class IgnitedRecord extends IR_base{
	/**
	 * The table that stores all database rows.
	 * 
	 * Default is plural of classname (using CodeIgniters Inflector helper) \n
	 * Override to specifically define the tablename
	 */
	var $table = null;
	
	/**
	 * The id column in database.
	 * 
	 * Usually expected to be an auto_incremental unsigned int,
	 * IgnitedRecord never sets this column.
	 * 
	 * Note: If this column is not an auto_incremental int,
	 *       you MUST set this column in the record objects before an INSERT is performed
	 */
	var $id_col = 'id';
	
	/**
	 * Cache for the table field metadata.
	 * 
	 * Stores the names of the columns.
	 * If you define this by hand, you save one query
	 */
	var $columns;
	
	/**
	 * The classname of the classes produced by this factory.
	 * 
	 * Override normally to use another class than IR_record
	 * (preferably a descendant class)
	 */
	var $child_class = 'IR_record';
	
	/**
	 * Defines how the database should behave.
	 * 
	 * Packaged with IgnitedRecord by default: tree
	 * (list, rev (revision handling) are not finished)
	 * 
	 * Set to empty array (default) if to not use any special behaviour.
	 */
	var $act_as = array();
	
	/**
	 * Data for a Belongs To relationships.
	 * 
	 * There are two types of values that can be entered:
	 * 
	 * @arg Relation name, as a string or multiple in array
	 * @arg An array with this structure (can be put inside another array to enable multiple):
	 * @code
	 * var $belongs_to = array('name'  => 'relationname',
	 *                         'table' => 'tablename', // all others except this one can be omitted
	 *                         'fk'    => 'foreign_key_col',
	 *                         'model' => 'modelname'
	 *                        );
	 * @endcode
	 * 
	 * Default values are the following:
	 * @arg name => modelname or singular(tablename)
	 * @arg model => tablename or singular(tablename), if a model with that name exists
	 * @arg fk => tablename_id
	 * 
	 * @note The foreign key is stored in this table, with default column name othertablename_id
	 * @note Needs to be defined before constructor kicks in.
	 */
	var $belongs_to = array();
	
	/**
	 * Data for a Has Many relationships.
	 * 
	 * There are two types of values that can be entered:
	 * 
	 * @arg Relation name, as a string or multiple in array
	 * @arg An array with this structure (can be put inside another array to enable multiple):
	 * @code
	 * var $has_many = array('name'  => 'relationname', // all others except this one can be omitted
	 *                       'table' => 'tablename', // or this one
	 *                       'fk'    => 'foreign_key_col',
	 *                       'model' => 'modelname'
	 *                      );
	 * @endcode
	 * 
	 * Default values are the following:
	 * @arg name => tablename
	 * @arg model => tablename or singular(tablename), if a model with that name exists
	 * @arg fk => {$this->table}_id
	 * 
	 * @note The foreign key is stored in the other table, with default column name thistablename_id
	 * @note Needs to be defined before constructor kicks in.
	 */
	var $has_many = array();
	
	/**
	 * Data for a Has One relationships.
	 * 
	 * There are two types of values that can be entered:
	 * 
	 * @arg Relation name, as a string or multiple in array
	 * @arg An array with this structure (can be put inside another array to enable multiple):
	 * @code
	 * var $has_one = array('name'  => 'relationname', // all others except this one can be omitted
	 *                      'table' => 'tablename', // or this one
	 *                      'fk'    => 'foreign_key_col',
	 *                      'model' => 'modelname'
	 *                     );
	 * @endcode
	 * 
	 * Default values are the following:
	 * @arg name => modelname or singular(tablename)
	 * @arg model => tablename or singular(tablename), if a model with that name exists
	 * @arg fk => {$this->table}_id
	 * 
	 * @note The foreign key is stored in the other table, with default column name thistablename_id
	 * @note Needs to be defined before constructor kicks in.
	 */
	var $has_one = array();
	
	/**
	 * Data for a Has And Belongs To many relationships.
	 * 
	 * There are two types of values that can be entered:
	 * 
	 * @arg Relation name, as a string or multiple in array
	 * @arg An array with this structure (can be put inside another array to enable multiple):
	 * @code
	 * var $has_and_belongs_to_many = array('name'        => 'relationname', // all others except this one can be omitted
	 *                                        'table'       => 'tablename', // or this one
	 *                                        'join_table'  => 'relation_tablename'
	 *                                        'fk'          => 'foreign_key_col',
	 *                                        'related_fk'  => 'foreign_key_col',
	 *                                        'model'       => 'modelname'
	 *                                       );
	 * @endcode
	 * 
	 * Default values are the following:
	 * @arg name => tablename
	 * @arg join_table => tablename_{$this->table} or {$this->table}_tablename (arranged in alphabetical order)
	 * @arg fk => {$this->table}_id
	 * @arg related_fk => tablename_id
	 * @arg model => tablename or singular(tablename), if a model with that name exists
	 * 
	 * @note Needs to be defined before constructor kicks in.
	 */
	var $habtm = array();
	
	/**
	 * Alias for $habtm
	 */
	var $has_and_belongs_to_many = array();
	
	/**
	 * Child class helpers.
	 * 
	 * These are assigned to properties of the child class.
	 * They should be a class whose constructor takes a reference to the IR_record.
	 * 
	 * Is assigned by behaviours.
	 * key => propertyname, value => classname
	 * 
	 * @access private
	 * (accessed by Behaviours)
	 */
	var $child_class_helpers = array();
	
	/**
	 * Stores the names of the loaded behaviours
	 * 
	 * @access private
	 */
	var $loaded_behaviours = array();
	
	/**
	 * Stores all the registered hooks.
	 * 
	 * @access private
	 */
	var $hooks;
	
	/**
	 * The model name of this model.
	 * 
	 * Note: DO NOT ASSIGN, IgnitedRecord does this automatically
	 * @access private
	 */
	var $model_name;
	
	// --------------------------------------------------------------------
		
	/**
	 * Constructor.
	 * 
	 * @since 0.1.0 RC 1
	 * 
	 * Loads Behaviours.
	 * Sets the tablename if not already set and normalizes relationship data.
	 * Also loads the CodeIgniter inflector helper (if not loaded).
	 * 
	 * @param $settings An array containing the settings (optional)
	 *                  Double underscores are automatically prepended
	 */
	function IgnitedRecord($settings = array())
	{
		parent::IR_base();
		
		// init the model with the supplied settings
		foreach($settings as $key => $value)
		{
			$this->$key = $value;
		}
		
		// load inflector
		$CI =& get_instance();
		$CI->load->helper('inflector');
		
		$this->_load_behaviours($this->act_as);
		
		// set default classname if not already set
		if($this->table == null)
		{
			$class = get_class($this);
			
			// remove '_model' from classname if it exists
			if(strtolower(substr($class,-6)) == '_model')
				$class = substr($class,0,-6);
			
			$this->table = plural($class);
		}
		
		
		//    NORMALIZE RELATIONS
		//  -----------------------
		
		$types = array('has_many' => true,
					   'has_one' => false,
					   'belongs_to' => false,
					   'habtm' => true,
					   'has_and_belongs_to_many' => true);
		
		foreach($types as $rel => $plural)
		{
			// if there is no data, skip here for speed
			if( ! count($this->$rel))
			{
				$this->$rel = array(); // reset, just in case
				continue;
			}
			
			// only a string, then make it the relation name
			if(is_string($this->$rel))
				$this->$rel = array('name' => $this->$rel);
			
			// is it a single relation? then encapsulate it in an array
			$var =& $this->$rel;
			if(isset($var['table']) OR isset($var['name']))
				$this->$rel = array($this->$rel);
			
			$tmp = $this->$rel;
			$this->$rel = array(); // reset, so nothing may survive and cause a mess
			
			foreach($tmp as $data)
			{
				if(is_string($data))
					$data = array('name' => $data);
				
				if( ! (isset($data['name']) OR isset($data['table'])))
				{
					show_error('IgnitedRecord: No tablename and/or relation name was specified when defining a relation of type "'.
							$rel.'".');
				}
				
				// get the right relation name
				if(isset($data['name']))
				{
					// if we have a name, use it
					$name = $data['name'];
				}
				elseif($plural)
				{
					// we must have a name or a table, then table is plural and suitable
					$name = $data['table'];
				}
				elseif(isset($data['model']))
				{
					// already a model?
					$name = $data['model'];
				}
				else
				{
					// nope, use singular of tablename
					$name = singular($data['table']);
				}
				
				// call the relation specifying function
				$this->$rel($name, $data);
			}
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * To fool CI, letting it think this is a proper model.
	 * @access private
	 */
	function _assign_libraries(){}
	
	// --------------------------------------------------------------------
		
	/**
	 * Sets the class to produce.
	 * 
	 * Preferably descendants of IR_Record
	 * 
	 * @param $class_name The class name of the objects to create
	 */
	function child_class($class_name)
	{
		$this->child_class = $class_name;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies the settings for a Has Many relationship.
	 * 
	 * @since 0.2.0
	 * @access public
	 * @param $plural The tablename (or a plural relation name)
	 * @param $settings An array containing optional settings
	 * @return An IR_RelProperty_has object
	 */
	function &has_many($plural, $settings = array())
	{
		$obj = new IR_RelProperty_has($settings);
		$obj->plural($plural);
		$obj->parent_model =& $this;
		$this->has_many[$plural] =& $obj;
		return $obj;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies the settings for a Has One relationship.
	 * 
	 * @since 0.2.0
	 * @access public
	 * @param $singular The modelname (or a singular relation name)
	 * @param $settings An array containing optional settings
	 * @return An IR_RelProperty_has object
	 */
	function &has_one($singular, $settings = array())
	{
		$obj = new IR_RelProperty_has($settings);
		$obj->singular($singular);
		$obj->parent_model =& $this;
		$this->has_one[$singular] =& $obj;
		return $obj;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies the settings for a Belongs To relationship.
	 * 
	 * @since 0.2.0
	 * @access public
	 * @param $singular The modelname (or a singular relation name)
	 * @param $settings An array containing optional settings
	 * @return An IR_RelProperty object
	 */
	function &belongs_to($singular, $settings = array())
	{
		$obj = new IR_RelProperty($settings);
		$obj->singular($singular);
		$this->belongs_to[$singular] =& $obj;
		return $obj;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies the settings for a Has And Belongs To Many relationship.
	 * 
	 * @since 0.2.0
	 * @access public
	 * @param $plural The tablename (or a plural relation name)
	 * @param $settings An array containing optional settings
	 * @return An IR_RelProperty_habtm object
	 */
	function &habtm($plural, $settings = array())
	{
		$obj = new IR_RelProperty_habtm($settings);
		$obj->plural($plural);
		$obj->parent_model =& $this;
		$obj->parent_table = $this->table;
		$this->habtm[$plural] =& $obj;
		return $obj;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Alias for habtm().
	 * 
	 * @since 0.2.0
	 * @access public
	 * @param $plural The tablename (or a plural relation name)
	 * @param $settings An array containing optional settings
	 * @return An IR_RelProp_habtm object
	 */
	function &has_and_belongs_to_many($plural)
	{
		return $this->habtm($plural);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Makes an on the fly loading of behaviours.
	 * 
	 * @since 0.2.0
	 * @access private
	 * @param $behaviour The behaviour(s) to load (accepts the same data as $act_as)
	 * @return void
	 */
	function act_as($behaviour)
	{
		$this->_load_behaviours($behaviour);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Adds a hook to the specified name and priority.
	 * 
	 * @access public
	 * @param $name The name of the hook
	 * @param $function The function to be called, or the array(classobj,function) to be called
	 * @param $priority The priority of the function lower = higher priority
	 * @return void
	 */
	function add_hook($name, $function, $priority = 10)
	{
		$this->hooks[$name][$priority][] = $function;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Runs the hook(s) registred with the name $name.
	 * 
	 * @access private
	 * @param $name The name of the hook to be called
	 * @param $data The array containing the data to pass on to registered functions
	 * @return true, or if any of the attached hooks want to abort, false
	 */
	function hook($name,$data = array())
	{
		// Have we got a hook for this specific event?
		if ( ! isset($this->hooks[$name]))
		{
			// No, do nothing
			return true;
		}
		else
		{
			// Yes, sort the list by priority
			ksort($this->hooks[$name]);
		}
		
		foreach($this->hooks[$name] as $priority => $functions)
		{
			if(is_array($functions))
			{
				foreach($functions as $func)
				{
					if(call_user_func_array($func,$data) === false)
						return false;  // abort directly
				}
			}
		}
		
		return true;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Creates a new model from the parameters sent.
	 * 
	 * Can be called statically.
	 * 
	 * @since 0.1.0 RC 2
	 * @access public
	 * @param $table The table the generated model will link to
	 * @param $settings The settings of this model 
	 *        (stored as propertyname (minus the preceding double underscore) as key and value as value)
	 * @return A new derived class
	 */
	function &factory($table, $settings = array())
	{
		$settings['table'] = $table;
		$model = new IgnitedRecord($settings); // PHP 4: $model =& new IgnitedRecord($settings);
		return $model;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Creates a new model from a YAML file/string.
	 * 
	 * Can be called statically.
	 *
	 * @since 0.1.1
	 * @access public
	 * @param $filename The filename or the yaml string
	 * @return An instance of IgnitedRecord instantiated with the parameters from the YAML data
	 */
	function &yamlfactory($filename)
	{
		$CI =& get_instance();
		// load the yaml parser
		$CI->load->helper('yayparser');
		
		if(file_exists($filename))
		{
			$yaml = implode('', file($filename));
			// parse (replace this with whatever parser you use)
			$array = yayparser($yaml);
		}
		elseif(strpos($filename,"\n") !== false)
		{
			// parse (replace this with whatever parser you use)
			$array = yayparser($filename);
		}
		else
		{
			show_error('IgnitedRecord: The yaml file "'.$filename.'" cannot be found.');
		}
		
		if( ! isset($array['table']))
		{
			show_error('IgnitedRecord: YAMLfactory(): The table setting must be set.');
		}
		
		$model = new IgnitedRecord($array); // PHP 4: $model =& new IgnitedRecord($array);
		return $model;
	}
	
	//////////////////////////////////////////////
	//    Update and Find methods
	//////////////////////////////////////////////
	
	/**
	 * Fetches an IR_record from the db.
	 * 
	 * @since 0.1.0 RC 2
	 * 
	 * To be used with CodeIgniter's ActiveRecord class to sort and filter the query.
	 * This method makes only one call, which fetches one row of data:
	 * @code
	 * $this->db->get($this->table,1);
	 * @endcode
	 *
	 * @access public
	 * @return A populated IR_record if result is found, false otherwise
	 */
	function &get()
	{
		$this->hook('pre_get');
		$query = $this->db_get($this->table,1);
		
		if( ! $query->num_rows())
		{
			$false = false;
			return $false;
		}
		
		$obj =& $this->_dbobj2ORM($query->row_array());
		$query->free_result();
		
		$this->hook('post_get',array(&$obj));
		return $obj;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Fetches the IR_record object with the id $id.
	 * 
	 * In the case with multiple primary keys you have to specify the values of
	 * all primary key columns. They must be in the same order as the values in
	 * $this->id_col.
	 *
	 * @access public
	 * @param $id The id of the row, if omitted it will act as an alias for get()
	 * @return A populated IR_record if result is found, false otherwise
	 */
	function &find($id = false)
	{
		// alias get() if we don't have an id
		if($id === false)
		{
			return $this->get();
		}
		
		$this->hook('pre_find',array($id));
		
		// build where array
		if(($where = $this->_array_combine((Array)$this->id_col, (Array)$id)) === false)
		{
			log_message('error','IgnitedRecord: Number of primary key columns and number of data argumnents does not match in call to find() for table '.$this->table.'.');
			$false = false;
			return $false;
		}
		
		// fetch
		$query = $this->db_get_where($this->table,$where,1);
		if( ! $query->num_rows())
		{
			$false = false;
			return $false;
		}
		
		$obj =& $this->_dbobj2ORM($query->row_array());
		$query->free_result();
		
		$this->hook('post_find',array($id,&$obj));
		return $obj;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Fetches the IR_record object where the $column contains $data.
	 * 
	 * This function merges the $column and $data into one array,
	 * where the $column becomes the key(s) in the array and
	 * the $data becomes the value(s), using _array_combine(). \n
	 * This array is then passed into CodeIgniter's db::get_where()
	 * method as the where clause.
	 * 
	 * @access public
	 * @param $column The column name(s) to match, typecasted to array if not already
	 * @param $data The data value(s) to match, typecasted to array if not already
	 * @return A populated IR_record if result is found, false otherwise
	 */
	function &find_by($column, $data)
	{
		if(($where = $this->_array_combine((Array)$column, (Array)$data)) === false)
		{
			log_message('error','IgnitedRecord: Number of columns and number of data argumnents does not match in call to find_by().');
			$false = false;
			return $false;
		}
		
		$this->hook('pre_find_by',array(&$where));
		
		// fetch
		$query = $this->db_get_where($this->table, $where, 1);
		if( ! $query->num_rows())
		{
			$false = false;
			return $false;
		}
		
		$obj =& $this->_dbobj2ORM($query->row_array());
		$query->free_result();
		
		$this->hook('post_find_by',array($where,&$obj));
		return $obj;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Fetches the IR_record object with the sql supplied.
	 * 
	 * @access public
	 * @param $sql The sql query, needs escaping of values (must start with SELECT * or equivalent)
	 * @return A populated IR_record if a result is found, false otherwise
	 */
	function &find_by_sql($sql)
	{
		$this->hook('pre_find_by_sql',array(&$sql));
		$query = $this->db_query($sql);
		if( ! $query->num_rows())
		{
			$false = false;
			return $false;
		}
		
		$obj =& $this->_dbobj2ORM($query->row_array());
		$query->free_result();
		
		$this->hook('post_find_by_sql',array($sql,&$obj));
		return $obj;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Fetches all IR_record objects in the database.
	 * 
	 * @access public
	 * @return An array with populated IR_records, empty array if table is empty
	 */
	function &find_all()
	{
		$arr = array();
		$this->hook('pre_find_all');
		$query = $this->db_get($this->table);
		
		foreach($query->result_array() as $row)
		{
			$arr[] =& $this->_dbobj2ORM($row);
		}
		$query->free_result();
		
		$this->hook('post_find_all',array(&$arr));
		return $arr;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Fetches all IR_record objects where the $column contains $data.
	 * 
	 * This function merges the $column and $data into one array,
	 * where the $column becomes the key(s) in the array and
	 * the $data becomes the value(s), using _array_combine(). \n
	 * This array is then passed into CodeIgniter's db::get_where()
	 * method as the where clause.
	 * 
	 * @access public
	 * @param $column The column name(s) to match
	 * @param $data The data which will be used to match
	 * @return An array with populated IR_records if results are found, empty array otherwise
	 */
	function &find_all_by($column, $data)
	{
		$arr = array();
		if(($where = $this->_array_combine((Array)$column, (Array)$data)) === false)
		{
			log_message('error','IgnitedRecord: Number of columns and number of data argumnents does not match in call to find_by().');
			return $arr;
		}
		
		$this->hook('pre_find_by',array(&$where));
		$query = $this->db_get_where($this->table, $where);
		
		foreach($query->result_array() as $row)
		{
			$arr[] =& $this->_dbobj2ORM($row);
		}
		$query->free_result();
		
		$this->hook('post_find_all_by',array($where,&$obj));
		return $arr;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Fetches all IR_record objects which match the sql supplied.
	 * 
	 * @access public
	 * @param $sql The sql query, needs escaping of values (must start with SELECT * or equivalent)
	 * @return An array with populated IR_records if resultas are found, false otherwise
	 */
	function &find_all_by_sql($sql)
	{
		$this->hook('pre_find_by_sql',array(&$sql));
		$arr = array();
		$query = $this->db_query($sql);
		
		foreach($query->result_array() as $row)
		{
			$arr[] =& $this->_dbobj2ORM($row);
		}
		$query->free_result();
		
		$this->hook('post_find_all_by',array($sql,&$arr));
		return $arr;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Creates a new empty IR_record object.
	 * 
	 * @access public
	 * @param $data The data of the new object, is assigned to the new object before it is returned
	 * @return A new IR_record
	 */
	function &new_record($data = array())
	{
		$class = $this->child_class;
		$obj = new $class($this,$data); // PHP 4: $obj =& new $class($this,$data);
		
		foreach($this->child_class_helpers as $name => $hclass)
		{
			$obj->$name = new $hclass($obj); // PHP 4: $obj->$name =& new $hclass($obj);
		}
		
		$this->hook('post_new_record',array(&$obj));
		return $obj;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Saves the supplied object in the databse.
	 * 
	 * Takes a reference of the object (edits the object after insert)'
	 * 
	 * Note: Can only save the $object if it belongs to the same table as this model
	 * 
	 * @access public
	 * @param $object The object to be inserted or updated, passed by reference
	 * @param $force If to force the save, to save even unchanged objects
	 * @return true if the record was saved, false otherwise
	 */
	function save(&$object, $force = false)
	{
		if($this->table == $object->__table)
		{
			if($this->hook('save_pre_strip_data',array(&$object)) === false)
			{
				log_message('debug','IgnitedRecord: save() method didn\'t save object, the attached hooks aborted saving on hook "save_pre_strip_data".');
				$this->hook('save_abort',array(&$object));
				return false; // hooks failed
			}
			
			// remove some values that shall not belong in the database
			$data =& $this->_strip_data($object);
			$this->hook('save_post_strip_data',array(&$data));
			
			if($object->__data == $data && $force == false)
			{
				log_message('debug','IgnitedRecord: save() method didn\'t save object, the object hasn\'t been edited.');
				$this->hook('save_abort',array(&$object));
				return false;
			}
			
			if(empty($data))
			{
				log_message('error','IgnitedRecord: the object passed to save() is empty.');
				$this->hook('save_abort',array(&$object));
				return false;
			}
			
			// check if row exists
			if($object->__id == null)
			{
				// no row exists in database, insert
				$this->hook('save_pre_insert',array(&$data));
				$ret = $this->db->insert($this->table,$data);
				$ret = ($ret && $this->db->affected_rows() > 0);
				
				$object->__id = array();
				foreach((Array)$this->id_col as $prop)
				{
					if(isset($data[$prop]))
						$object->__id[] = $data[$prop];
					else
						$object->__id[] = $this->db->insert_id(); // grabs the id of the inserted row
				}
				
				// Currently a dirty fix for relations:
				if(count($object->__id) == 1)
					$object->__id = $object->__id[0];
				
				$this->hook('save_post_insert',array(&$object));
			}
			else
			{
				// remove the ID properties
				foreach((Array)$this->id_col as $prop)
				{
					unset($data[$prop]);
				}
				
				// update
				$this->hook('save_pre_update',array(&$data));
				
				if(($where = $this->_array_combine((Array)$this->id_col, (Array)$object->__id)) === false)
				{
					log_message('error','IgnitedRecord: Number of primary columns and number of data argumnents does not match in save() for table '.$this->table.'.');
					$false = false;
					return $false;
				}
				
				$this->db->where($where);
				$ret = $this->db->update($this->table,$data);
				
				$ret = ($ret && $this->db->affected_rows() > 0);
				$this->hook('save_post_update',array(&$object));
			}
			$this->hook('post_save',array(&$object));
			return $ret;
		}
		else
		{
			show_error('Incompatible object supplied to '.classname($this).', tables does not match');
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Updates the data in the supplied object.
	 * 
	 * If the object does not exist in the database,
	 * the object will still contain all data.
	 * (but the relations and id will be stripped)
	 * 
	 * @note Can only perform an update if the $object belongs to the same table as this instance
	 * @note Replaces the object with a clean one, losing all loaded relations
	 * (you have to load them again with load_rel() if you need them)
	 * 
	 * @access public
	 * @param $object The object to be updated, passed by reference
	 * 
	 * @todo Add abort hook and make it fire if a hooked function wants to abort (like save())
	 */
	function update(&$object)
	{
		if($this->table == $object->__instance->table)
		{
			// check if row exists
			if($object->in_db())
			{
				if($this->hook('pre_update',array(&$object)) === false)
					return false; // hooks failed
				
				if(($where = $this->_array_combine((Array)$this->id_col, (Array)$object->__id)) === false)
				{
					log_message('error','IgnitedRecord: Number of primary columns and number of data arguments does not match in update() for table '.$this->table.'.');
					$false = false;
					return $false;
				}
				
				$query = $this->db->get_where($this->table,$where,1);
				if($query->num_rows())
					$object =& $this->_dbobj2ORM($query->row_array());
				
				else
				{
					$object->__id = null;
				}
				
				$query->free_result();
				$this->hook('post_update',array(&$object));
			}
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Deletes the $object from the database if it exists in database.
	 * 
	 * Also clears all relations with other rows.
	 * 
	 * @access public
	 * @param $object The IR_record to remove from database
	 * 
	 * @todo Add abort hook and make it fire if a hooked function wants to abort (like save())
	 */
	function delete(&$object)
	{
		if($object->in_db())
		{
			if($this->hook('pre_delete',array(&$object)) === false)
				return false; // hooks failed
			
			// no need to do anything for Belongs To, the data is stored in this table
			
			foreach($this->has_many as $table => $data)
			{
				$this->db->set($data->get_fk(), null);
				$this->db->where($data->get_fk(), $obj->__id);
				$this->db->update($table);
			}
			
			foreach($this->has_one as $table => $data)
			{
				$this->db->set($data->get_fk(), null);
				$this->db->where($data->get_fk(), $obj->__id);
				$this->db->update($table);
			}
			
			foreach($this->habtm as $table => $data)
			{
				$this->db->delete($data->get_join_table(),array($data->get_fk() => $object->__id));
			}
			
			unset($object->__relations);
			$object->__relations = array();
			
			$this->hook('pre_delete_query',array(&$object));
			if(($where = $this->_array_combine((Array)$this->id_col, (Array)$object->__id)) === false)
			{
				log_message('error','IgnitedRecord: Number of primary columns and number of data arguments does not match in delete() for table '.$this->table.'.');
				$false = false;
				return $false;
			}
			
			$this->db->delete($this->table,$where);
			unset($object->__id);
			
			$this->hook('post_delete',array(&$object));
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Removes all data from the associated table, and also all relationships.
	 * 
	 * Attention: All Data Will Be Lost! Including all relations with other tables!
	 *            Not the related objects, though.
	 * 
	 * @access public
	 */
	function delete_all()
	{
		$this->db->empty_table($this->table);
		
		// Belongs to relations are stored in this table, no need to do anything
		
		foreach($this->has_many as $table => $data)
		{
			$this->db->set($data['col'],null);
			$this->db->update($table);
		}
		
		foreach($this->has_one as $table => $data)
		{
			$this->db->set($data['col'],null);
			$this->db->update($table);
		}
		
		foreach($this->habtm as $table => $data)
		{
			// remove all rows where this_col is not null
			// which is enabling the use of a relations table for more than two tables
			$this->db->delete($data['rel_table'],array($data['this_col'].' !=' => null));
		}
	}
	
	//////////////////////////////////////////////
	//    Relationship methods
	//////////////////////////////////////////////
	
	/**
	 * JOINs the related object(s) into the current query in construction.
	 * 
	 * They are prefixed with their relation name.
	 * The sql where will only find rows with a related row attached to them.
	 * 
	 * Note: It is only possible to join Has One or Belongs To relationships.
	 * Note: Requires IgnitedQuery
	 * 
	 * @access public
	 * @param $name The relation name
	 * @param $columns The columns to fetch
	 * @return $this
	 */
	function join_related($name, $columns = false)
	{
		if( ! is_a($this, 'IgnitedQuery'))
		{
			show_error('IgnitedRecord: join_related() requires IgnitedRecord to utilize IgnitedQuery as SQL-string builder.');
			return;
		}
		
		// only join relations relating to a SINGLE other row
		foreach(array('has_one', 'belongs_to') as $rel)
		{
			$prop =& $this->$rel;
			$func = 'join_'.$rel;
			
			if(isset($prop[$name]))
			{
				$this->$func($this, $name, $prop[$name], (Array)$columns);
			}
		}
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	// These methods below are called by objects this class creates,
	// so I recommend that you only call these methods if you know what you are doing.
	// Please use the methods in IR_record instead
		
	/**
	 * JOINs a related has one relationship into this obejct,
	 * 
	 * @access private
	 * @param $q_obj The query object to add the code to
	 * @param $name The name of the relation
	 * @param $rel The relation property obejct
	 * @param $columns The columns to fetch
	 */
	function join_has_one(&$q_obj, $name, $rel, $columns = false)
	{
		// fetch the columns if they are not already specified
		empty($columns) AND $columns = $this->db->list_fields($rel->get_table());
		
		// add the identifier if it isn't already there
		if( ! in_array($rel->get_fk(), $columns))
			$columns[] = $rel->get_fk();
		
		// construct subquery
		$q = new IgnitedQuery();
		$select = array();
		
		foreach($columns as $field)
		{
			$select[$field] = $name.'_'.$field;
		}
		
		$q->select($select);
		$q->from($rel->get_table());
		$q->alias('related_'.$name);
		
		// add subquery
		$q_obj->from($q);
		
		$q_obj->where($this->table.'.'.$this->id_col, 'related_'.$name.'.'.$name.'_'.$rel->get_fk(), false);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * JOINs a related has one relationship into this obejct,
	 * 
	 * @access private
	 * @param $q_obj The query object to add the code to
	 * @param $name The name of the relation
	 * @param $rel The relation property obejct
	 * @param $columns The columns to fetch
	 */
	function join_belongs_to(&$q_obj, $name, $rel, $columns = false)
	{
		// fetch the columns if they are not already specified
		empty($columns) AND $columns = $this->db->list_fields($rel->get_table());
		
		// get the id column for the related object
		if(($model = $rel->get_model()) != '')
		{
			$CI =& get_instance();
			$model =& $CI->$model;
			$col = $model->id_col;
		}
		else
		{
			$col = 'id';
		}
		
		// add the identifier if it isn't already there
		if( ! in_array($col, $columns))
			$columns[] = $col;
		
		// construct subquery
		$q = new IgnitedQuery();
		$select = array();
		
		foreach($columns as $field)
		{
			$select[$field] = $name.'_'.$field;
		}
		
		$q->select($select);
		$q->from($rel->get_table());
		$q->alias('related_'.$name);
		
		// add subquery
		$q_obj->from($q);
		
		$q_obj->where($this->table.'.'.$rel->get_fk(), 'related_'.$name.'.'.$name.'_'.$col, false);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Creates a relation query object matching the provided relation name.
	 * 
	 * If no relation name is found, a relation query object is returned,
	 * this object returns false upon get().
	 * 
	 * @access private
	 * (accessed by child objects)
	 * @param $object	The child object
	 * @param $name		The relation name
	 */
	function &load_rel(&$object, $name)
	{
		if($object->__table == $this->table && $object->in_db())
		{
			foreach(array('has_many', 'has_one', 'belongs_to', 'habtm') as $rel)
			{
				$prop =& $this->$rel;
				$func = 'load_'.$rel;
				
				if(isset($prop[$name]))
				{
					$ret =& $this->$func($object, $name, $prop[$name]);
					return $ret;
				}
			}
		}
		
		$rel_query = new IR_RelQuery(); // PHP 4: $rel_query =& new IR_RelQuery()
		$rel_query->no_rel = true;
		return $rel_query;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Creates a Has Many relationship query object.
	 *
	 * @access private
	 * @param $object The object to load relations for
	 * @param $name The relationname
	 * @param $data The relationship data
	 */
	function load_has_many(&$obj, $name, $data)
	{
		if( ! $obj->in_db())
			return;
		
		// create query object
		$rel_query = new IR_RelQuery(); // PHP 4: $rel_query =& new IR_RelQuery()
		$rel_query->table = $data->get_table();
		$rel_query->from($data->get_table());
		$rel_query->multiple = true;
		
		if(($model = $data->get_model()) != '')
		{
			// use that model to instantiate the objects
			$CI =& get_instance();
			$rel_query->model_inst =& $CI->$model;
			
			$rel_query->where($data->get_fk(), $obj->__id);
		}
		else
		{
			// no model = normal orm OBJ
			$rel_query->no_instance = true;
			$rel_query->model_inst =& $this;
			
			// assume that id is the unique id
			$rel_query->where($data->get_fk(), $obj->__id);
		}
		
		return $rel_query;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Creates a Has One relationship query object.
	 *
	 * @access private
	 * @param $object The object to load relations for
	 * @param $name The relationname
	 * @param $data The relationship data
	 */
	function load_has_one(&$obj, $name, $data)
	{
		if( ! $obj->in_db())
			return;
		
		// create query object
		$rel_query = new IR_RelQuery(); // PHP 4: $rel_query =& new IR_RelQuery()
		$rel_query->table = $data->get_table();
		$rel_query->from($data->get_table());
		$rel_query->limit(1); // this is a has ONE relationship
		$rel_query->multiple = false;
		
		if(($model = $data->get_model()) != '')
		{
			// use that model to instantiate the objects
			$CI =& get_instance();
			$rel_query->model_inst =& $CI->$model;
			
			$rel_query->where($data->column(), $obj->__id);
		}
		else
		{
			// no model = normal orm OBJ
			$rel_query->no_instance = true;
			$rel_query->model_inst =& $this;
			
			// assume that id is the unique id
			$rel_query->where($data->get_fk(), $obj->__id);
		}
		
		return $rel_query;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Creates a Belongs To relationship query object.
	 *
	 * @access private
	 * @param $object The object to load relations for
	 * @param $name The relationname
	 * @param $data The relationship data
	 */
	function load_belongs_to(&$obj, $name, $data)
	{
		if( ! $obj->in_db())
			return;
		
		// create query object
		$rel_query = new IR_RelQuery(); // PHP 4: $rel_query =& new IR_RelQuery()
		$rel_query->table = $data->get_table();
		$rel_query->from($data->get_table());
		$rel_query->limit(1);
		$rel_query->multiple = false;
		
		if(isset($obj->{$data->get_fk()}))
		{
			if(($model = $data->get_model()) != '')
			{
				// use that model to instantiate the objects
				$CI =& get_instance();
				$rel_query->model_inst =& $CI->$model;
				
				$rel_query->where($rel_query->model_inst->id_col, $obj->{$data->get_fk()});
			}
			else
			{
				// no model = normal orm OBJ
				$rel_query->no_instance = true;
				$rel_query->model_inst =& $this;
				
				// assume that id is the unique id
				$rel_query->where('id', $obj->{$data->get_fk()});
			}
		}
		else
		{
			log_message('debug', 'The column "'.$data->get_fk().
				'" was not set in the table '.$obj->__table.' on uid '.$obj->__id);
			$rel_query->no_rel = true;
		}
		
		return $rel_query;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Creates a Has And Belongs To Many (habtm) relationship query object.
	 * 
	 * If IgnitedQuery is used as the wrapper, this method creates a subquery
	 * to fetch all id's instead of first querying for the ids and then shoving
	 * them back to the db.
	 * 
	 * A performance improvement, to simplify the paragraph above :P
	 * 
	 * @access private
	 * @param $object The object to load relations for
	 * @param $name The relationname
	 * @param $data The relationship data
	 */
	function &load_habtm(&$obj, $name, $data)
	{
		
		// create query object
		$rel_query = new IR_RelQuery(); // PHP 4: $rel_query =& new IR_RelQuery()
		$rel_query->table = $data->get_table();
		$rel_query->from($data->get_table());
		$rel_query->multiple = true;
		
		// fetch the id column
		if($data->get_model() != '')
		{
			// use that model to instantiate the objects
			$CI =& get_instance();
			$rel_query->model_inst =& $CI->{$data->get_model()};
			
			$id = $rel_query->model_inst->id_col;
		}
		else
		{
			// no model = normal orm OBJ
			$rel_query->no_instance = true;
			$rel_query->model_inst =& $this;
			
			// assume that id is the unique id
			$id = 'id';
		}
		
		// fetch ids
		if( ! is_a($this, 'IgnitedQuery'))
		{
			// get data from relations table
			$this->db->select($data->get_related_fk());
			$query = $this->db->get_where($data->get_join_table(),array($data->get_fk() => $obj->__id));
			
			// get all ids
			$ids = array();
			foreach($query->result() as $row)
			{
				$ids[] = $row->{$data->get_related_fk()};
			}
			$query->free_result();
		}
		else
		{
			// generate a suquery
			$ids = new IgnitedQuery();
			$ids->select($data->get_related_fk());
			$ids->from($data->get_join_table());
			$ids->where($data->get_fk(), $obj->__id);
		}
		
		// insert id filter
		$rel_query->where_in($id, $ids);
		
		return $rel_query;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Establishes a relationship between the two supplied objects.
	 * 
	 * Determines which method that shall be used; Belongs To, Has Many, Has One or Has And Belongs To Many. \n
	 * Performs some checking of input
	 * 
	 * @param $child An IR_record created by this IgnitedRecord model
	 * @param $object Another IR_record (must have an instance tied to it)
	 * @param $rel_name The name of the relationship, leave blank if to auto determine
	 * @return true if the establishment of a relation succeeded, false otherwise
	 */
	function establish_relationship(&$child, &$object, $rel_name = false)
	{
		if( ! isset($child->__instance) || $child->__instance->table != $this->table)
		{
			log_message('error','An incompatible object was supplied to an IgnitedRecord object belonging to the table '. $child->__table);
			return false;
		}
		
		if( ! isset($object->__instance)){
			log_message('error','An incompatible object was supplied to an IgnitedRecord object belonging to the table '. $object->__table);
			return false;
		}
		
		// if we don't have a name, find it
		if(empty($rel_name)){
			foreach(array('has_many', 'has_one', 'belongs_to', 'habtm') as $rel)
			{
				foreach($this->$rel as $name => $obj)
				{
					if($obj->get_table() == $object->__table)
					{
						$rel_name = $name;
						break(2); // Jump happily two times because we've found it!!! :P
					}
				}
				
			}
		}
		
		foreach(array('has_many', 'has_one', 'belongs_to', 'habtm') as $rel)
		{
			$prop =& $this->$rel;
			$method = 'establish_'.$rel.'_relationship';
			
			if(in_array($rel_name, array_keys($prop)))
			{
				return $this->$method($prop[$rel_name], $child, $object);
			}
		}
		
		log_message('error',"IgnitedRecord: no relation with the relationname $rel_name was found, no relation established.");
		return false;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Establishes a Belongs To relationship between $child and $object.
	 * 
	 * @note No checking if a relationship are defined between the two.
	 * 
	 * @access private
	 * @param $rel The relationship data array
	 * @param $child An IR_record created by this IgnitedRecord model
	 * @param $object Another IR_record (must have an instance tied to it)
	 * @return true if the establishment of a relation succeeded, false otherwise
	 */
	function establish_belongs_to_relationship($rel, &$child, &$object){
		// $child Belongs To $object
		if($rel->get_table() != $object->__table){
			// no matching table
			log_message('error','IgnitedRecord: The table '.$child->__table.' has not got a relation with the table '.$object->__table);
			return false;
		}
		
		$column = $rel->get_fk();
		
		if( ! isset($object->__id)){
			// object does not exist in database, save it
			if( ! $object->save())
				return false;
		}
		
		// object now exists in database
		$child->$column = $object->__id;
		
		return $child->save();
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Establishes a Has Many relationship between $child and $object.
	 * 
	 * @note No checking if a relationship are defined between the two.
	 * 
	 * @access private
	 * @param $rel The relationship data array
	 * @param $child An IR_record created by this IgnitedRecord model
	 * @param $object Another IR_record (must have an instance tied to it)
	 * @return true if the establishment of a relation succeeded, false otherwise
	 */
	function establish_has_many_relationship($rel, &$child, &$object){
		// $child Has Many of $object type
		// Like an inverted Belongs To relationship
		if($rel->get_table() != $object->__table){
			// no matching table
			log_message('error','IgnitedRecord: The table '.$child->__table.' has not got a relation with the table '.$object->__table);
			return false;
		}
		
		$column = $rel->get_fk();
		
		if( ! isset($child->__id)){
			// this object does not exist in database, save it
			if( ! $child->save())
				return false;
		}
		
		// this object now exists in database
		$object->$column = $child->__id;
		
		return $object->save();
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Establishes a Has One relationship between $child and $object.
	 * 
	 * @note No checking if a relationship are defined between the two.
	 * 
	 * @access private
	 * @param $rel The relationship data array
	 * @param $child An IR_record created by this IgnitedRecord model
	 * @param $object Another IR_record (must have an instance tied to it)
	 * @return true if the establishment of a relation succeeded, false otherwise
	 */
	function establish_has_one_relationship($rel, &$child, &$object){
		// $child Has One of $object type
		if($rel->get_table() != $object->__table){
			// no matching table
			log_message('error','IgnitedRecord: The table '.$child->__table.' has not got a relation with the table '.$object->__table);
			return false;
		}
		
		$column = $rel->get_fk();
		
		if( ! isset($child->__id)){
			if( ! $child->save())
			{
				return false;
			}
		}
		else{
			$related =& $object->__instance->find_all_by($column,$child->__id);
			// remove all relationships to related objects in that table
			foreach($related as $r){
				$r->$column = null;
				$r->save();
			}
		}
		
		$object->$column = $child->__id;
		
		return $object->save();
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Establishes a Has And Belongs To Many relationship between $child and $object.
	 * 
	 * @access private
	 * @param $rel The relationship data array
	 * @param $child An IR_record created by this IgnitedRecord model
	 * @param $object Another IR_record (must have an instance tied to it)
	 * @return true if the establishment of a relation succeeded, false otherwise
	 */
	function establish_habtm_relationship($rel, &$child, &$object){
		if($rel->get_table() != $object->__table){
			// no matching table
			log_message('error','IgnitedRecord: The table '.$child->__table.
								' has not got a relation with the table '.$object->__table);
			return false;
		}
		
		if( ! isset($child->__id) && ! $child->save()){
			return false;
		}
		
		if( ! isset($object->__id) && ! $object->save()){
			return false;
		}
		
		$this->db->where($rel->get_fk(),$child->__id);
		$this->db->where($rel->get_related_fk(),$object->__id);
		$query = $this->db->get($rel->get_join_table());
		
		$success = true;
		
		if( ! $query->num_rows()){
			// no relationship established
			$data[$rel->get_fk()] = $child->__id;
			$data[$rel->get_related_fk()] = $object->__id;
			$success = $this->db->insert($rel->get_join_table(),$data);
		}
		
		$query->free_result();
		
		return $success;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Removes the relationship between the two supplied objects if it exists.
	 * 
	 * Determines which method that shall be used; Belongs To, Has Many, Has One or Has And Belongs To Many. \n
	 * Performs some checking of input
	 * 
	 * @access private
	 * @param $child An IR_record created by this IgnitedRecord model
	 * @param $object Another IR_record (must have an instance tied to it)
	 * @param $rel_name The name of the relationship, leave blank if to auto determine
	 */
	function remove_relationship(&$child, &$object, $rel_name = false){
		if( ! isset($child->__instance) || $child->__instance->table != $this->table){
			log_message('error','An incompatible object was supplied to an IgnitedRecord object belonging to the table '
								  .$child->__table);
			return false;
		}
		if( ! isset($object->__instance)){
			log_message('error','An incompatible object was supplied to an IgnitedRecord object belonging to the table '
								  .$object->__table);
			return false;
		}
		
		// if we don't have a name, find it
		if($rel_name == false){
			foreach(array('has_many', 'has_one', 'belongs_to', 'habtm') as $rel)
			{
				foreach($this->$rel as $name => $obj)
				{
					if($obj->get_table() == $object->__table)
					{
						$rel_name = $name;
						break(2); // jump two times because we've found it! :P
					}
				}
				
			}
		}
		
		
		foreach(array('has_many', 'has_one', 'belongs_to', 'habtm') as $rel)
		{
			$prop =& $this->$rel;
			$method = 'remove_'.$rel.'_relationship';
			
			if(in_array($rel_name, array_keys($prop)))
			{
				return $this->$method($prop[$rel_name], $child, $object);
			}
		}
		
		log_message('error','IgnitedRecord: no relation with the relationname '.
						     $rel_name.' was found, no relation removed.');
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Removes a Belongs To relationship between $child and $object, if it exists.
	 * 
	 * @note No checking if a relationship are defined between the two.
	 * 
	 * @access private
	 * @param $rel The relationship data array
	 * @param $child An IR_record created by this IgnitedRecord model
	 * @param $object Another IR_record (must have an instance tied to it)
	 */
	function remove_belongs_to_relationship($rel, &$child, &$object){
		// $child Belongs To $object
		if($rel->get_table() != $object->__table){
			// no matching table
			log_message('error','IgnitedRecord: The table '.$child->__table.
								' has not got a relation with the table '.$object->__table);
			return;
		}
		
		$column = $rel->get_fk();
		
		if( ! isset($object->__id) || ! isset($child->$column))
			return;
		
		if($child->$column == $object->__id){
			$child->$column = null;
			$child->save();
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Removes a Has Many relationship between $child and $object, if it exists.
	 * 
	 * @note No checking if a relationship are defined between the two.
	 * 
	 * @access private
	 * @param $rel The relationship data array
	 * @param $child An IR_record created by this IgnitedRecord model
	 * @param $object Another IR_record (must have an instance tied to it)
	 */
	function remove_has_many_relationship($rel, &$child, &$object){
		// $child Has Many of $object type
		// like an inverted Belongs To relationship
		if($rel->get_table() != $object->__table){
			// no matching table
			log_message('error','IgnitedRecord: The table '.$child->__table.
								' has not got a relation with the table '.$object->__table);
			return;
		}
		
		$column = $rel->get_fk();
		
		if( ! isset($child->__id) || !isset($object->$column))
			return;
		
		if($object->$column == $child->__id){
			$object->$column = null;
			$object->save();
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Removes a Has One relationship between $child and $object, if it exists.
	 * 
	 * @note No checking if a relationship are defined between the two.
	 * 
	 * @access private
	 * @param $rel The relationship data array
	 * @param $child An IR_record created by this IgnitedRecord model
	 * @param $object Another IR_record (must have an instance tied to it)
	 */
	function remove_has_one_relationship($rel, &$child, &$object){
		// $child Has One of $object type
		if($rel->get_table() != $object->__table){
			// no matching table
			log_message('error','IgnitedRecord: The table '.$child->__table.
								' has not got a relation with the table '.$object->__table);
			return;
		}
		
		$column = $rel->get_fk();
		
		// works exactly like Has Many (only when establishing a relationship is it different)
		if( ! isset($child->__id) || !isset($object->$column))
			return;
		
		if($object->$column == $child->__id){
			$object->$column = null;
			$object->save();
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Removes a Has And Belongs To Many relationship between $child and $object, if it exists.
	 * 
	 * @note No checking if a relationship are defined between the two.
	 * 
	 * @access private
	 * @param $rel The relationship data array
	 * @param $child An IR_record created by this IgnitedRecord model
	 * @param $object Another IR_record (must have an instance tied to it)
	 */
	function remove_habtm_relationship($rel, &$child, &$object){
		if($rel->get_table() != $object->__table){
			// no matching table
			log_message('error','IgnitedRecord: The table '.$child->__table.
								' has not got a has and belongs to relation with the table '.$object->__table);
			return;
		}
		
		if( ! isset($child->__id) || ! isset($object->__id))
			return;
		
		$this->db->where($rel->get_fk(),$child->__id);
		$this->db->where($rel->get_related_fk(),$object->__id);
		$this->db->delete($rel->get_join_table());
	}
	
	//////////////////////////////////////////////
	//    Private methods
	//////////////////////////////////////////////
		
	/**
	 * Aggregates the behaviours into this object, only PHP 5.
	 * 
	 * In PHP 4, this method won't aggregate the behaviours,
	 * so you have to directly call the behaviour.
	 * 
	 * @code
	 * $object->behaviourname->method();
	 * // instead of
	 * $object->method();
	 * @endcode
	 * 
	 * @param $method The method called
	 * @param $args The argument array sent to the method
	 * @return Whatever the helper method returns
	 */
	function __call($method,$args)
	{
		foreach($this->loaded_behaviours as $property)
		{
			if(method_exists($this->$property, $method))
				return call_user_func_array(array($this->$property, $method),$args);
		}
		
		show_error("IgnitedRecord: Method $method is not found.");
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Creates an IR_record object from the supplied db row.
	 * 
	 * @access private
	 * @param $data The data to load from (array)
	 * @param $dynamic If to load the object dynamically, true uses the user defined class otherwise it uses the IR_record
	 * @return A populated IR_record (or descendant of it)
	 */
	function &_dbobj2ORM($data,$dynamic = true)
	{
		$class = $this->child_class;
		$id = array();
		
		foreach((Array)$this->id_col as $prop)
		{
			if(isset($data[$prop]))
				$id[] = $data[$prop];
		}
		
		// TEH HOOKS IZ ONLY OURS!!! NOONE ELZE SHOULD HAZ THEM!!!
		// ---  Insert cat with jealous grin here :P  ---
		if($dynamic)
			$this->hook('pre_instantiate_record',array(&$data, &$id, &$class));
		
		// Currently a dirty fix for relations:
		if(count($id) == 1)
			$id = $id[0];
		
		if($dynamic)
		{
			// let user decide
			$obj = new $class($this,$data,$id); // PHP 4: $obj =& new $class($this,$data,$id);
		}
		else
		{
			$obj = new IR_record($this,$data,$id); // PHP 4: $obj =& new IR_record($this,$data,$id);
		}
		
		if($dynamic)
		{
			$this->hook('post_instantiate_record',array(&$obj, &$data));
			
			// add the child class helpers to the record
			foreach($this->child_class_helpers as $name => $hclass)
			{
				$obj->$name = new $hclass($obj); // PHP 4: $obj->$name =& new $hclass($obj);
			}
		}
		
		// save a copy of the data to determine if we have altered the object on save()
		$obj->__data = $data;
		
		if($dynamic)
			$this->hook('post_create_record_helpers',array(&$obj, &$data));
		
		return $obj;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Fetches all data properties from an object.
	 * 
	 * Skips the relation properties, the child helpers
	 * instance variables (tied to IgnitedRecord and other classes)
	 * and also the id column (all properties which are not table columns).
	 * 
	 * @access private
	 * @param $object The object to be cleaned
	 * @return An associative array containing the data from the object,
	 * key is property name and value is value of the property
	 */
	function &_strip_data(&$object)
	{
		if( ! isset($this->columns))
			$this->columns = $this->db->list_fields($this->table);
		
		foreach($this->columns as $col)
		{
			if(isset($object->$col))
				$data[$col] = $object->$col;
		}
		
		return $data;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Loads the behaviours specified in $act_as.
	 * 
	 * Expects the behaviours to be localized in files which lie in the subfolder
	 * behaviours/ with the names like:
	 * behaviourname.php
	 * 
	 * and to have the class name linke this:
	 * IgnitedRecord_behaviourname
	 * 
	 * If the behaviour class already exists, no loading of files takes place.
	 * The behaviourname is always lowercase
	 * 
	 * @access private
	 * @param $list The list of behaviours to load
	 */
	function _load_behaviours($list)
	{
		foreach((Array) $list as $key => $act)
		{
			if( ! is_numeric($key))
			{
				$opt = $act;
				$act = $key;
			}
			else
			{
				$opt = array();
			}
			
			$act = strtolower($act);
			$exists = false;
			$class_name = 'IgnitedRecord_'.$act;
			
			// is loaded?, if not, try to load
			if( ! class_exists($class_name))
			{
				$path = dirname(__FILE__);
				
				if(file_exists($path.'/behaviours/'.$act.'.php'))
				{
					include_once($path.'/behaviours/'.$act.'.php');
					if(class_exists($class_name))
						$exists = true;
				}
				else
				{
					log_message('error','IgnitedRecord: Behaviour file '.$act.
										  '.php does not exists in the behaviours dir. Cannot load '.$act.'.');
				}
			}
			else
			{
				$exists = true;
			}
			
			if($exists == true)
			{
				// Check if a reference to a library, model or anything else
				// exists in $this,
				if(isset($this->$act))
				{
					// unset removes reference, prevents overwrite of the original data
					unset($this->$act);
				}
				
				// load behaviour
				$this->$act = new $class_name($this,$opt); // PHP 4: $this->$act =& new $class_name($this,$opt);
				$this->loaded_behaviours[] = $act;
			}
			else
			{
				log_message('error','IgnitedRecord: Behaviour class IgnitedRecord_'.$act.
									  ' does not exists. Cannot load '.$act.'.');
			}
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Returns the modelname of this object.
	 * 
	 * Which is classname or the name of the CI property this object lies in.
	 * 
	 * @access private
	 * @return string, or false if not found
	 */
	function get_modelname()
	{
		// check cache
		if(isset($this->model_name))
		{
			return $this->model_name;
		}
		
		$class = strtolower(get_class($this));
		
		// check if it is a user defined class
		if($class == 'ignitedrecord')
		{
			// no, set default and then iterate over the CI properties
			$class = singular($this->table);
			$CI =& get_instance();
		
			if(floor(phpversion()) < 5)
			{
				// PHP 4, serialize it here, to gain performance
				$serialized = serialize($this);
			
				foreach(array_keys(get_object_vars($CI)) as $key)
				{
					// PHP 4 cannot handle self-referencing properties,
					// use serialize to determine if they are identical
					if(serialize($CI->$key) === $serialized)
					{
						$class = $key;
						break;
					}
				}
			}
			else
			{
				// PHP 5+
				foreach(array_keys(get_object_vars($CI)) as $key)
				{
					// PHP 5 can compare objects with self-referencing properties
					if($CI->$key === $this)
					{
						$class = $key;
						break;
					}
				}
			}
		}
		
		// remove '_model' from classname if it exists
		if(strtolower(substr($class,-6)) == '_model')
			$class = substr($class,0,-6);
		
		$this->model_name = $class;
		return $class;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Combines two non asociative arrays to one asociative.
	 * 
	 * Exists in PHP 5, but not in 4
	 * 
	 * @access private
	 * @param $keys The array with the keys
	 * @param $values The array with the values
	 * @return An asociative array with the keys from $keys and the values from $values
	 */
	function _array_combine($keys,$values)
	{
		if(function_exists('array_combine'))
			return array_combine($keys,$values); // PHP 5
		
		// This code below is from the PEAR PHP_Compat package
		// It is licensed under the PHP license,
		// see http://www.php.net/license/3_01.txt
		// Copyright (c) 1999 - 2008 The PHP Group. All rights reserved.
		if ( ! is_array($keys) ||
			 ! is_array($values) ||
			count($keys) !== count($values) ||
			count($keys) === 0 || count($values) === 0)
		{
			return false;
		}
		
		$keys	= array_values($keys);
		$values = array_values($values);
		$combined = array();
		
		for ($i = 0, $cnt = count($values); $i < $cnt; $i++) {
			$combined[$keys[$i]] = $values[$i];
		}
		
		return $combined;
		// end code from PHP_Compat
	}
}
/**
 * @}
 */
/* End of file ignitedrecord.php */
/* Location: ./application/models/ignitedrecord/ignitedrecord.php */