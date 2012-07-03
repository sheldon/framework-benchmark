<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * Created on 2008 Jun 26
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
 * A SQL query builder / executor.
 * 
 * @version Alpha
 * @author Martin Wernstahl <m4rw3r@gmail.com>
 * @par Copyright
 * Copyright (c) 2008, Martin Wernstahl <m4rw3r@gmail.com>
 * 
 * @todo HAVING
 */
class IgnitedQuery{
	
	// =============================
	// = ========== DATA ========= =
	// =============================
	
	var $q_select = '*';
	var $q_distinct = false;
	var $q_from = array();
	var $q_join = array();
	var $q_where = array();
	var $q_group_by = array();
	var $q_order_by = array();
	var $q_offset = false;
	var $q_limit = false;
	
	/**
	 * The alias of this subquery (if this is one).
	 * 
	 * @access private
	 * 
	 * If set to false, the alias cannot be set
	 * (that setting is used for subqueries which shouldn't have aliases)
	 */
	var $q_as = null;
	
	/**
	 * The data used by insert() and update().
	 * 
	 * @access private
	 */
	var $q_set_data = array();
	
	/**
	 * A query cache.
	 * 
	 * Caches the query settings.
	 */
	var $q_cache = array();
	
	/**
	 * The database prefix, fetched from the DB class.
	 * 
	 * @access private
	 */
	var $q_prefix = '';
	
	/**
	 * Contains an array of all tablenames to prefix.
	 * 
	 * @access private
	 * 
	 * Used when building the query.
	 */
	var $q_to_prefix = array();
	
	// =============================
	// = ========= FLAGS ========= =
	// =============================
	
	/**
	 * The parent node of the subquery (if this is one).
	 * 
	 * If not null or false
	 */
	var $q_parent = null;
	
	/**
	 * Determines if only the WHERE part of the query should be retuned by _build_get_query().
	 */
	var $q_only_where = false;
	
	// --------------------------------------------------------------------
		
	/**
	 * Constructor.
	 */
	function IgnitedQuery()
	{
		$CI =& get_instance();
		$this->q_prefix = $CI->db->dbprefix;
	}
	
	// =============================
	// = == OBJECT DATA METHODS == =
	// =============================
	
	/**
	 * Resets the state of this object.
	 * 
	 * @access public
	 */
	function reset()
	{
		if(floor(phpversion()) < 5)
		{
			// need to reset references for this one,
			// otherwise the child will kill the parent before the child can take care of itself
			unset($this->q_parent);
			unset($this->q_to_prefix);
		}
		
		$this->q_select = '*';
		$this->q_distinct = false;
		$this->q_from = array();
		$this->q_join = array();
		$this->q_where = array();
		$this->q_group_by = array();
		$this->q_order_by = array();
		$this->q_offset = false;
		$this->q_limit = false;
		$this->q_as = null;
		$this->q_parent = null;
		$this->q_only_where = false;
		$this->q_set_data = array();
		$this->q_to_prefix = array();
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Saves the state of this object.
	 * 
	 * @access public
	 */
	function save_cache(){
		$this->q_cache = array();
		
		foreach(array('q_select', 'q_distinct', 'q_from', 'q_join', 'q_where', 'q_group_by', 'q_order_by', 'q_offset', 'q_limit', 'q_as') as $prop)
		{
			$this->q_cache[$prop] = $this->$prop;
		}
		
		return $this->q_cache;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Saves the state of this object.
	 * 
	 * @access public
	 */
	function load_cache($data = false){
		if( ! $data)
			$data =& $this->q_cache;
		
		$this->reset();
		
		foreach(array('q_select', 'q_distinct', 'q_from', 'q_join', 'q_where', 'q_group_by', 'q_order_by', 'q_offset', 'q_limit', 'q_as') as $prop)
		{
			$this->$prop = $data[$prop];
		}
	}
	
	// =============================
	// = ========= SELECT ======== =
	// =============================
	
	/**
	 * Specifies the SELECT part of the query.
	 * 
	 * @access public
	 * @param $cols		The column(s) to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param $protect_identifiers If to protect identifiers
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &select($cols = false, $protect_identifiers = true)
	{
		if($cols === false)
		{
			$obj = new IgnitedQuery();
			$obj->q_parent =& $this;
			$this->q_select[] =& $obj;
			return $obj;
		}
		
		// reset select if it is the default "*"
		if(is_string($this->q_select))
		{
			$this->q_select = array();
		}
		
		if(is_array($cols))
		{
			// many columns in array
			if($protect_identifiers)
				array_walk($cols,array($this,'_protect_identifiers_walk'));
			
			$this->q_select = array_merge($this->q_select,$cols);
		}
		elseif(is_object($cols) && is_a($cols,'IgnitedQuery'))
		{
			// subquery
			$cols->q_parent =& $this;
			$this->q_select[] =& $cols;
		}
		else
		{
			if($protect_identifiers)
				$cols = $this->_protect_identifiers($cols);
			
			$this->q_select[] = $cols;
		}
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies a MAX select function.
	 * 
	 * @access public
	 * @param $col		The column to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param $alias	The alias of the query, if empty, alias will be $col
	 * @param $protect_identifiers If to protect identifiers
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &select_max($col = false,$alias = '', $protect_identifiers = true)
	{
		return $this->_select_func($col, $alias, 'MAX', $protect_identifiers);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies a MIN select function.
	 * 
	 * @access public
	 * @param $col		The column to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param $alias	The alias of the query, if empty, alias will be $col
	 * @param $protect_identifiers If to protect identifiers
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &select_min($col = false,$alias = '', $protect_identifiers = true)
	{
		return $this->_select_func($col, $alias, 'MIN', $protect_identifiers);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies an AVG select function.
	 * 
	 * @access public
	 * @param $col		The column to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param $alias	The alias of the query, if empty, alias will be $col
	 * @param $protect_identifiers If to protect identifiers
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &select_avg($col = false,$alias = '', $protect_identifiers = true)
	{
		return $this->_select_func($col, $alias, 'AVG', $protect_identifiers);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies a SUM select function.
	 * 
	 * @access public
	 * @param $col		The column to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param $alias	The alias of the query, if empty, alias will be $col
	 * @param $protect_identifiers If to protect identifiers
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &select_sum($col = false,$alias = '', $protect_identifiers = true)
	{
		return $this->_select_func($col, $alias, 'SUM', $protect_identifiers);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies a COUNT select function.
	 * 
	 * @access public
	 * @param $col 		The column to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param $alias 	The alias of the query, if empty, alias will be $col
	 * @param $protect_identifiers If to protect identifiers
	 * 
	 * @return $this or a new IgnitedQuery
	 */	
	function &select_count($col = false, $alias = '', $protect_identifiers = true)
	{
		return $this->_select_func($col, $alias, 'COUNT', $protect_identifiers);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies a select function (eg. MIN, MAX, AVG).
	 * 
	 * @access private
	 * @param $col 		The column to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param $alias 	The alias of the query, if empty, alias will be $col
	 * @param $func 	The function to use
	 * @param $protect_identifiers If to protect identifiers
	 * 
	 * @return $this or a new IgnitedQuery
	 * 
	 * @todo Check the subquery for SELECT FUNC(foo) AS bar
	 */
	function &_select_func($col, $alias, $func, $protect_identifiers)
	{
		if(is_string($this->q_select))
		{
			$this->q_select = array();
		}
		
		$this->q_select[] = $func;
		
		if($col == false)
		{
			$obj = new IgnitedQuery();
			$obj->q_as = is_empty($alias) ? false : $alias;
			$obj->q_parent =& $this;
			$this->q_select[] =& $obj;
			return $obj;
		}
		
		if(is_object($col) && is_a($col, 'IgnitedRecord'))
		{
			$col->q_as = is_empty($alias) ? false : $alias;
			$this->q_select[] =& $col;
			$col->q_parent =& $this;
			return $this;
		}
		
		if($protect_identifiers)
			$col = $this->_protect_identifiers($col);
		
		$this->q_select[] = '('.$col.') AS '.
						  ($alias != ''
						  	? $this->_protect_identifiers($alias)
						  	: $this->_protect_identifiers($col)
						  );
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Sets the flag for the use of the DISTINCT modifier.
	 * 
	 * @param $val The state of the flag
	 *
	 * @return $this
	 */
	function &distinct($val = true)
	{
		$this->q_distinct = $val;
		return $this;
	}
	
	// =============================
	// = ========== FROM ========= =
	// =============================
	
	/**
	 * Specifies the FROM part of the query.
	 * 
	 * @access public
	 * @param $tables The table(s) to be selected, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &from($tables = false)
	{
		if($tables === false)
		{
			$obj = new IgnitedQuery();
			$obj->q_parent =& $this;
			$this->q_from[] =& $obj;
			return $obj;
		}
		
		if(is_array($tables))
		{
			// list of tables, or table => alias
			$this->q_from = array_merge($this->q_from, $tables);
		}
		elseif(is_object($tables) && is_a($tables,'IgnitedQuery'))
		{
			// subquery
			$tables->q_parent =& $this;
			$this->q_from[] =& $tables;
		}
		else
		{
			$this->q_from[] = $tables;
		}
		
		return $this;
	}
	
	// =============================
	// = ========== JOIN ========= =
	// =============================
	
	/**
	 * Specifies a JOIN.
	 * 
	 * @access public
	 * @param $table	The table to join with
	 * @param $cond		The join condition (Could be an IgnitedQuery (for multiple where))
	 * If set to false, a new IgnitedQuery is returned which acts like a multiple where
	 * @param $type		The type of join
	 * 
	 * @return $this, or a new IgniedQuery
	 */
	function &join($table, $cond = false, $type = '')
	{
		if($cond == false)
		{
			$obj = new IgnitedQuery();
			$obj->q_parent =& $this;
			$obj->q_only_where = true;
			
			// let the nested WHERE statement use the same tables to prefix
			$obj->q_to_prefix =& $this->q_to_prefix;
			
			$this->q_join[$table] = array('type' => $type, 'cond' => &$obj);
			return $obj;
		}
		elseif(is_a($cond,'IgnitedQuery'))
		{
			$cond->q_parent =& $this;
			$cond->q_only_where = true;
			
			// let the nested WHERE statement use the same tables to prefix
			$cond->q_to_prefix =& $this->q_to_prefix;
			
			$this->q_join[$table] = array('type' => $type, 'cond' => &$cond);
		}
		else
		{
			$this->q_join[$table] = array('type' => $type, 'cond' => $cond);
		}
		
		return $this;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies a LEFT JOIN.
	 * 
	 * @access public
	 * @param $table	The table to join with
	 * @param $cond		The join condition (Could be an IgnitedQuery (for multiple where))
	 * If set to false, a new IgnitedQuery is returned which acts like a multiple where
	 * 
	 * @return $this, or a new IgniedQuery
	 */
	function &left_join($table, $cond = false)
	{
		return $this->join($table, $cond, 'LEFT');
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies a LEFT OUTER JOIN.
	 * 
	 * @access public
	 * @param $table	The table to join with
	 * @param $cond		The join condition (Could be an IgnitedQuery (for multiple where))
	 * If set to false, a new IgnitedQuery is returned which acts like a multiple where
	 * 
	 * @return $this, or a new IgniedQuery
	 */
	function &left_outer_join($table, $cond = false)
	{
		return $this->join($table, $cond, 'LEFT OUTER');
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies a RIGHT JOIN.
	 * 
	 * @access public
	 * @param $table	The table to join with
	 * @param $cond		The join condition (Could be an IgnitedQuery (for multiple where))
	 * If set to false, a new IgnitedQuery is returned which acts like a multiple where
	 * 
	 * @return $this, or a new IgniedQuery
	 */
	function &right_join($table, $cond = false)
	{
		return $this->join($table, $cond, 'RIGHT');
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies a RIGHT OUTER JOIN.
	 * 
	 * @access public
	 * @param $table	The table to join with
	 * @param $cond		The join condition (Could be an IgnitedQuery (for multiple where))
	 * If set to false, a new IgnitedQuery is returned which acts like a multiple where
	 * 
	 * @return $this, or a new IgniedQuery
	 */
	function &right_outer_join($table, $cond = false)
	{
		return $this->join($table, $cond, 'RIGHT OUTER');
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies an INNER JOIN.
	 * 
	 * @access public
	 * @param $table	The table to join with
	 * @param $cond		The join condition (Could be an IgnitedQuery (for multiple where))
	 * If set to false, a new IgnitedQuery is returned which acts like a multiple where
	 * 
	 * @return $this, or a new IgniedQuery
	 */
	function &inner_join($table, $cond = false)
	{
		return $this->join($table, $cond, 'INNER');
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Specifies an OUTER JOIN.
	 * 
	 * @access public
	 * @param $table	The table to join with
	 * @param $cond		The join condition (Could be an IgnitedQuery (for multiple where))
	 * If set to false, a new IgnitedQuery is returned which acts like a multiple where
	 *
	 * @return $this, or a new IgniedQuery
	 */
	function &outer_join($table, $cond = false)
	{
		return $this->join($table, $cond, 'OUTER');
	}
	
	// =============================
	// = ========= WHERE ========= =
	// =============================
	
	/**
	 * Specifies the WHERE part of the query.
	 * 
	 * @access public
	 * @param $where	The where data, can be an IgnitedQuery
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param $value	The value to match with
	 * @param $escape	If to escape, default: true
	 * @param $not		If to prepend all predicates in this call with NOT
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &where($where = false,$value = null,$escape = true, $or = false, $not = false)
	{
		if($where === false)
		{
			if($or && count($this->q_where))
				$this->q_where[] = 'OR';
			
			if($not)
				$this->q_where[] = 'NOT';
			
			$obj = new IgnitedQuery();
			$obj->q_parent =& $this;
			$obj->q_only_where = true;
			
			// let the nested WHERE statement use the same tables to prefix
			$obj->q_to_prefix =& $this->q_to_prefix;
			
			$this->q_where[] =& $obj;
			return $obj;
		}
		
		if(is_object($where) && is_a($where,'IgnitedQuery'))
		{
			// sub where
			
			if($or && count($this->q_where))
				$this->q_where[] = 'OR';
			
			if($not)
				$this->q_where[] = 'NOT';
			
			$where->q_parent =& $this;
			$where->q_only_where = true;
			
			// let the nested WHERE statement use the same tables to prefix
			$where->q_to_prefix =& $this->q_to_prefix;
			
			$this->q_where[] =& $where;
			return $this;
		}
		
		if( ! is_array($where) && ($escape OR $value != null))
		{
			$where = array($where => $value);
		}
		
		foreach((Array)$where as $k => $val)
		{
			if($or && count($this->q_where))
				$this->q_where[] = 'OR';
			
			if($not)
				$this->q_where[] = 'NOT';
			
			// numeric tells us that we have a finished where statement
			if(is_numeric($k))
			{
				$this->q_where[] = $escape == true
									? $this->_protect_identifiers($val)
									: $val;
			}
			elseif(is_null($val))
			{
				// only protect the first, the rest is probably "IS (NOT) NULL"
				$this->q_where[] = $this->_protect_identifiers($k, true);
			}
			else
			{
				// process key => value where
				$k = $this->_protect_identifiers($k);
				
				if(is_object($val) && is_a($val,'IgnitedQuery'))
				{
					// subquery
					$val->q_as = false;
					$val->q_parent =& $this;
					$this->q_where[] = array($k => $val);
				}
				elseif($escape)
				{
					$this->q_where[] = array($k => $this->escape($val));
				}
				else
				{
					$this->q_where[] = array($k => $val);
				}
			}
		}
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies an OR part in the WHERE part of the query.
	 * 
	 * Works like where()
	 * 
	 * @access public
	 * @param $where	The where data
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param $value	The value to (NOT) match with
	 * @param $escape	If to escape, default: true
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &or_where($where = false,$value = null,$escape = true)
	{
		return $this->where($where,$value,$escape,true);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Produces a nested where with the keyword NOT in front of it.
	 * 
	 * @access public
	 * @param $where 	The where data
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param $value 	The value to match with
	 * @param $escape 	If to escape, default: true
	 * 
	 * @return $this, or an IgnitedQuery
	 */
	function &not_where($where = false, $value = null, $escape = true, $or = false)
	{
		return $this->where($where, $value, $escape, $or, true);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Produces a nested where with the keywords OR NOT in front of it.
	 * 
	 * @access public
	 * @param $where 	The where data
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param $value 	The value to match with
	 * @param $escape 	If to escape, default: true
	 * 
	 * @return $this, or an IgnitedQuery
	 */
	function &or_not_where($where = false, $value = null, $escape = true)
	{
		return $this->not_where($where, $value, $escape, true);
	}
	
	// =============================
	// = ======= WHERE IN ======== =
	// =============================
	
	/**
	 * Generates a WHERE IN in the WHERE part of the query.
	 * 
	 * @access public
	 * @param $key 		The column to check
	 * @param $values 	The values to check against
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * @param $not 		If this should be a WHERE NOT IN query
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &where_in($key, $values = false, $not = false)
	{
		$this->q_where[] = $this->_protect_identifiers($key);
		
		if($not)
			$this->q_where[] = 'NOT';
		
		$this->q_where[] = 'IN';
		
		if($values === false)
		{
			$obj = new IgnitedQuery();
			$obj->q_parent =& $this;
			$obj->q_as = false;
			
			$this->q_where[] =& $obj;
			
			return $obj;
		}
		
		if(is_object($values) && is_a($values,'IgnitedQuery'))
		{
			$values->q_parent =& $this;
			$values->q_as = false;
			
			$this->q_where[] =& $values;
			
			return $this;
		}
		
		$str = '';
		
		foreach((Array)$values as $key => $col)
		{
			$str .= $key > 0 ? ', ' : '';
			$str .= $this->escape($col);
		}
		
		$this->q_where[] = '('.$str.')';
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies an OR WHERE IN in the WHERE part of the query.
	 * 
	 * Works like where_in()
	 * 
	 * @access public
	 * @param $key 		The column to check
	 * @param $values 	The values to check against
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &or_where_in($key, $values = false)
	{
		if(count($this->q_where))
			$this->q_where[] = 'OR';
		
		return $this->where_in($key,$values);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies an WHERE NOT IN in the WHERE part of the query.
	 * 
	 * @access public
	 * @param $key 		The column to check
	 * @param $values 	The values to check against
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &where_not_in($key, $values = false){
		return $this->where_in($key,$values,true);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies an OR WHERE NOT IN in the WHERE part of the query.
	 * 
	 * Works like where_not_in()
	 * 
	 * @access public
	 * @param $key 		The column to check
	 * @param $values 	The values to check against
	 * If set to false, a new IgnitedQuery is returned which acts like a subquery
	 * 
	 * @return $this or a new IgnitedQuery
	 */
	function &or_where_not_in($key, $values = false)
	{
		if(count($this->q_where))
			$this->q_where[] = 'OR';
		
		return $this->where_in($key,$values,true);
	}
	
	// =============================
	// = ========== LIKE ========= =
	// =============================
	
	/**
	 * Produces a LIKE clause in the WHERE art of the query.
	 * 
	 * @access public
	 * @param $col 		The column to match
	 * @param $match 	The match string
	 * @param $side 	Which side the '%' sign should be on,
	 * 					Accepts 'both', 'before' and 'after' (default: 'both')
	 * @param $not 		If to add NOT to the statement (default: false)
	 * 
	 * @todo Check if subqueries are suported 
	 */
	function &like($col, $match = '', $side = 'both', $or = false, $not = false)
	{
	/*	if(!is_array($col) && $match == ''){
			$this->q_where[] = $this->_protect_identifiers($col);
			if($not)
				$this->q_where[] = 'NOT';
			$this->q_where[] = 'LIKE';
			$obj = new IgnitedQuery($this);
			$obj->q_as = false;
			$this->q_where[] = $obj;
			return $obj;
		}*/
		if(!is_array($col))
			$col = array($col => $match);
		
		foreach($col as $k => $v)
		{
			$this->q_where[] = $this->_protect_identifiers($k);
			
			if($or)
				$this->q_where[] = 'OR';
			
			if($not)
				$this->q_where[] = 'NOT';
			
			$this->q_where[] = 'LIKE';
			$v = $this->escape($v);
			
			if($side == 'before')
				$this->q_where[] = "%$v";
			elseif($side == 'after')
				$this->q_where[] = "$v%";
			else
				$this->q_where[] = "%$v%";
		}
		
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Produces a NOT LIKE sql statement.
	 * 
	 * @access public
	 * @param $col 		The column to match
	 * @param $match 	The match string
	 * @param $side 	Which side the '%' sign should be on,
	 * 					Accepts 'both', 'before' and 'after' (default: 'both')
	 * 
	 * @return $this
	 */
	function not_like($col, $match = '', $side = 'both')
	{
		return $this->like($col, $match, $side, true);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Produces an OR LIKE sql statement.
	 * 
	 * @access public
	 * @param $col 		The column to match
	 * @param $match 	The match string
	 * @param $side 	Which side the '%' sign should be on,
	 * 					Accepts 'both', 'before' and 'after' (default: 'both')
	 * @param $not 		If to add NOT to the statement (default: false)
	 * 
	 * @return $this
	 */
	function or_like($col, $match = '', $side = 'both', $not = false)
	{
		return $this->like($col, $match, $side, true, $not);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Produces an OR NOT LIKE sql statement.
	 * 
	 * @access public
	 * @param $col 		The column to match
	 * @param $match 	The match string
	 * @param $side 	Which side the '%' sign should be on,
	 * 					Accepts 'both', 'before' and 'after' (default: 'both')
	 * 
	 * @return $this
	 */
	function or_not_like($col, $match = '', $side = 'both')
	{
		return $this->or_like($col, $match, $side, true);
	}
	
	// =============================
	// = ===== SQL MODIFIERS ===== =
	// =============================
	
	/**
	 * Specifies the GROUP BY part of the query.
	 * 
	 * @access public
	 * @param $by The column(s) to group by
	 * 
	 * @return $this
	 */
	function &group_by($by)
	{
		if(is_string($by))
		{
			$by = explode(',',$by);
		}
		foreach((Array)$by as $val)
		{
			$val = trim($val);
			$this->q_group_by[] = $this->_protect_identifiers($val);
		}
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies the ORDER BY part of the query.
	 * 
	 * @access public
	 * @param $by The column(s) to order by
	 * 
	 * @return $this
	 */
	function &order_by($by, $direction = '')
	{
		if (strtolower($direction) == 'random')
		{
			$orderby = ''; // Random results want or don't need a field name
			$CI =& get_instance();
			$this->orderby = $CI->db->_random_keyword;
			return $this;
		}
		elseif (trim($direction) != '')
		{
			$direction = (in_array(strtoupper(trim($direction)), array('ASC', 'DESC'), TRUE)) ? ' '.$direction : ' ASC';
		}
		
		if(is_string($by))
		{
			$by = explode(',',$by);
		}
		
		foreach((Array)$by as $val)
		{
			$val = trim($val);
			$this->q_order_by[] = $this->_protect_identifiers($val). ' '.$direction;
		}
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies if and what the LIMIT part of the query should be.
	 *
	 * @access public
	 * @param $val The number
	 * 
	 * @return $this
	 */
	function &limit($val)
	{
		$this->q_limit = $val;
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies if and what the OFFSET part of the query should be.
	 *
	 * @access public
	 * @param $val The number
	 * 
	 * @return $this
	 */
	function &offset($val)
	{
		$this->q_offset = $val;
		return $this;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Specifies an alias for this subquery.
	 * 
	 * @note This only applies if this object is a subquery
	 * 
	 * @access public
	 * @param $alias The alias
	 * 
	 * @return $this
	 */
	function &alias($alias)
	{
		if($this->q_as !== false)
			$this->q_as = $this->_protect_identifiers($alias);
		
		return $this;
	}
	
	// =============================
	// = ========== SET ========== =
	// =============================
	
	/**
	 * This function saves the data to be INSERTed/UPDATEd.
	 * 
	 * @access public
	 * @param $key		The name of the field to set, or an asociative array with field => value
	 * @param $value	The value to assign to $key, can be a subquery returning a SINGLE value
	 * @param $escape	If to escape the values (default: true), subqueries are not escaped
	 * 
	 * @return $this
	 */
	function set($key, $value = '', $escape = true)
	{
		if( ! is_array($key))
		{
			$key = array($key => $value);
		}
		
		foreach($key as $key => $value)
		{
			if($escape === true && ! is_object($value))
			{
				$value = $this->escape($value);
			}
			
			if(is_object($value) && is_a($value,'IgnitedQuery'))
			{
				$value->q_parent =& $this; // subquery
				$value->q_as = false; // no aliases
				$value->limit(1); // set limit to 1, because only one row can be returned and assigned
			}
			
			$this->q_set_data[$key] = $value;
		}
		
		return $this;
	}
	
	// =============================
	// = === END QUERY METHODS === =
	// =============================
	
	/**
	 * Ends a subquery block, returning the parent query object.
	 * 
	 * If this is the root query, null is returned.
	 * 
	 * @access public
	 * @return The parent IgnitedQuery
	 */
	function &end()
	{
		return $this->q_parent;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Runs the SELECT query.
	 * 
	 * Resets this object after the query has been assembled.
	 * 
	 * @access public
	 * @param $table	A table which is forwarded to from()
	 * @param $limit	The limit
	 * @param $offset	The offset
	 * 
	 * @return A DB_result object
	 */
	function get($table = '', $limit = null, $offset = null)
	{
		$this->limit($limit);
		$this->offset($offset);
		
		if($table != '')
			$this->from($table);
		
		$CI =& get_instance();
		return $CI->db->query($this->_build_get_query());
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Runs a SELECT query, but returns only the first matc.
	 * 
	 * Resets this object after the query has been assembled.
	 * Limit is always 1
	 * 
	 * @access public
	 * @param $table	A table which is forwarded to from()
	 * 
	 * @return A std_class object, false if nothing was found
	 */
	function get_one($table = '', $offset = null)
	{
		$this->limit(1);
		
		if($table != '')
			$this->from($table);
		
		$CI =& get_instance();
		$q = $CI->db->query($this->_build_get_query());
		
		if( ! $q->num_rows())
		{
			$q->free_result();
			return false;
		}
		else
		{
			// get the row
			$data = $q->row();
			$q->free_result();
			return $data;
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Like get_one(), with the exception that it returns arrays.
	 * 
	 * @access public
	 * @param $table	A table which is forwarded to from()
	 * 
	 * @return An array, false if nothing was found
	 */
	function get_one_array($table = '')
	{
		$this->limit(1);
		
		if($table != '')
			$this->from($table);
		
		$CI =& get_instance();
		$q = $CI->db->query($this->_build_get_query());
		
		if( ! $q->num_rows())
		{
			$q->free_result();
			return false;
		}
		else
		{
			// get the row
			$data = $q->row_array();
			$q->free_result();
			return $data;
		}
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Returns a single variable from a SELECT query.
	 * 
	 * Useful if you have a select like SELECT count(1) FROM ...
	 * 
	 * LIMIT is always 1
	 * Always returns the first value in the SELECT
	 * 
	 * @access public
	 * @param $table A table which is forwarded to from()
	 * 
	 * @return The value from the query, false if nothing was found
	 */
	function get_var($table = '')
	{
		$this->limit(1);
		
		if($table != '')
			$this->from($table);
		
		$CI =& get_instance();
		$q = $CI->db->query($this->_build_get_query());
		
		if( ! $q->num_rows())
		{
			$q->free_result();
			return false;
		}
		else
		{
			// get the row
			$data = $q->row_array();
			$q->free_result();
			
			// get the first value
			return array_shift($data);
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Runs the SELECT query, and specifies a where clause.
	 * 
	 * Resets this object after the query has been assembled.
	 *
	 * @access public
	 * @param $table	A table which is forwarded to from()
	 * @param $where	Forwarded to where()
	 * @param $limit	The limit
	 * @param $offset	The offset
	 * 
	 * @return A DB_result object
	 */
	function get_where($table, $where, $limit = null, $offset = null)
	{
		$this->where($where);
		$this->limit($limit);
		$this->offset($offset);
		
		if($table != '')
			$this->from($table);
		
		$CI =& get_instance();
		return $CI->db->query($this->_build_get_query());
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Performs an INSERT query
	 * 
	 * @access public
	 * @param $table The table
	 * @param $set	 The data to insert
	 */
	function insert($table = '', $set = null)
	{
		if( ! is_null($set))
			$this->set($set);
		
		if($table == '')
		{
			if( ! isset($this->q_from[0]))
				return false;
			
			$table = $this->q_from[0];
		}
		
		$data = array();
		
		foreach($this->q_set_data as $field => $value)
		{
			$data[$this->_protect_identifiers($field)] =
				is_a($value, 'IgnitedQuery')
					? $value->_build_get_query()
					: $value;
		}
		
		$CI =& get_instance();
		$sql = $CI->db->_insert($this->_protect_identifiers($this->dbprefix.$table), array_keys($this->ar_set), array_values($this->ar_set));
		
		$this->reset();
		return $CI->db->query($sql);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Performs an UPDATE query
	 * 
	 * @access public
	 * @param $table The table
	 * @param $set	 The data to update to
	 * @param $where The where part of the query
	 * @param $limit A LIMIT for the query
	 */
	function update($table = '', $set = null, $where = null, $limit = null)
	{
		if( ! is_null($set))
			$this->set($set);
		
		if( ! count($this->set))
			return false;
		
		if($table == '')
		{
			if( ! isset($this->q_from[0]))
				return false;
			
			$table = $this->q_from[0];
		}
		
		if ($where != NULL)
		{
			$this->where($where);
		}

		if ($limit != NULL)
		{
			$this->limit($limit);
		}
		
		$set = array();
		
		foreach(array_keys($this->set) as $key)
		{
			if(is_a($this->set[$key], 'IgnitedQuery'))
			{
				$set[$key] = $this->set[$key]->_build_get_query();
			}
			else{
				$set[$key] = $this->set[$key];
			}
		}
		
		$CI =& get_instance();
		$sql = $CI->db->_update($table, $set, $this->_build_where($this->q_where), $this->orderby, $this->q_limit);
		
		$this->reset();
		return $CI->db->query($sql);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Performs a DELETE query.
	 * 
	 * @access public
	 * @param $table		The table to delete rows from
	 * @param $where		The WHERE part of the query
	 * @param $limit		The LIMIT of the query
	 * @param $reset_data	If to reset this object after delete, default: true
	 * 
	 * @return Query result
	 */
	function delete($table = '', $where = '', $limit = NULL, $reset_data = TRUE)
	{
		if($table == '')
		{
			if( ! isset($this->q_from[0]))
				return false;
			
			$table = $this->q_from[0];
		}
		elseif (is_array($table))
		{
			foreach($table as $single_table)
			{
				$this->delete($single_table, $where, $limit, FALSE);
			}

			$this->_reset_write();
			return;
		}
		
		if (count($this->q_where) == 0 && count($this->like) == 0)
		{
			return false;
		}
		
		if ($where != NULL)
		{
			$this->where($where);
		}

		if ($limit != NULL)
		{
			$this->limit($limit);
		}
		
		$CI =& get_instance();
		$sql = $CI->db->_delete($table, $this->_build_where($this->q_where), array(), $this->q_limit);

		if ($reset_data)
		{
			$this->reset();
		}
		
		return $CI->db->query($sql);
	}
	
	// =============================
	// = ==== PRIVATE METHODS ==== =
	// =============================
	
	/**
	 * Builds a SELECT query from the internal variables.
	 *
	 * Resets the internal variables before returning the query.
	 * 
	 * @access private
	 * (called by parent queries)
	 * @return a SQL string, or false if no from() call was made.
	 */
	function _build_get_query()
	{
		$query = $this->get_select_sql();
		
		$this->reset();
		
		return $query;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Previews the generated SQL.
	 * 
	 * Builds the SELECT as query, but does not clear internals.
	 * 
	 * @return string
	 */
	function get_select_sql()
	{
		if($this->q_only_where)
			return '('.$this->_build_where($this->q_where).')';
		
		if(!count($this->q_from))
			return false;
		
		$query = 'SELECT '. ($this->q_distinct
							 ? 'DISTINCT '
							 : '');
		
		// Wait, we need to do this first...
		$from_clause = $this->_build_from($this->q_from);
		
		// And this...
		$join_clause = count($this->q_join)
					? $this->_build_join($this->q_join)
					: '';
		
		// Then we'll do everything else
		$query .= $this->_build_select($this->q_select);
		
		$query .= "\nFROM ".$from_clause;
		
		$query .= $join_clause;
		
		$query .= count($this->q_where)
					? "\nWHERE ".$this->_build_where($this->q_where)
					: '';
		
		$query .= count($this->q_group_by)
					? "\nGROUP BY ".implode($this->q_group_by,', ')
					: '';
		
		$query .= count($this->q_order_by)
					? "\nORDER BY ".implode($this->q_order_by,', ')
					: '';
		
		if(is_numeric($this->q_limit))
		{
			// use CI's database specific limit
			$CI =& get_instance();
			$query .= "\n";
			$query = $CI->db->_limit($query, $this->q_limit, $this->q_offset);
		}
		
		if(isset($this->q_parent))
		{
			// this is a subquery
			$query = '('.$query.')'.
				(isset($this->q_as) && $this->q_as != false
					? ' AS '.$this->_protect_identifiers($this->q_as).' '
					: ''
				);
		}
		
		return $query;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Builds the SELECT part of the query.
	 * 
	 * @access private
	 * @param $select The select data
	 * 
	 * @return A string
	 */
	function _build_select($select)
	{
		if(is_object($select))
		{
			return $select->_build_get_query();
		}
		elseif(is_array($select))
		{
			$str = '';
			$i = 0;
			
			foreach($select as $key => $col)
			{
				// add commas if needed
				$str .= $i++ > 0 ? ', ' : '';
				
				// these are db functions, just echo them
				if(in_array($col,array('MIN','MAX','AVG','SUM','COUNT')))
				{
					$str .= $col;
					$i = 0;
					continue;
				}
				
				if( ! is_numeric($key))
					$str .= $this->_build_select($key) . ' AS ' . $this->_protect_identifiers($col);
				else
					$str .= $this->_build_select($col);
			}
			
			return $str;
		}
		
		return $this->_prefix($select);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Builds the FROM part of the query.
	 * 
	 * @access private
	 * @param $from The from data
	 * 
	 * @return A string
	 */
	function _build_from($from)
	{
		if(is_object($from))
		{
			return $from->_build_get_query();
		}
		elseif(is_array($from))
		{
			$str = '';
			$i = 0;
			
			foreach($from as $key => $col)
			{
				// add commas if needed
				$str .= $i++ > 0 ? ', ' : '';
				
				// not numeric tells us that we have "tablename => alias"
				if(!is_numeric($key))
					$str .= $this->_build_from($key) . ' AS ' . $this->_protect_identifiers($col);
				else
					$str .= $this->_build_from($col);
			}
			
			return $str;
		}
		
		return $this->_protect_identifiers($this->_add_prefix($from));
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Builds the join part of the query,
	 * 
	 * @param $join_data The data to utilize when building the join
	 * @return string
	 */
	function _build_join($join_data)
	{
		$str = '';
		
		foreach($join_data as $table => $opts)
		{
			if($opts['type'] != '')
			{
				$type = strtoupper(trim($opts['type']));

				if ( ! in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'), TRUE))
				{
					$type = '';
				}
				else
				{
					$str .= "$type ";
				}
			}
			
			$str .= 'JOIN ' . $this->_protect_identifiers($this->_add_prefix($table));
			
			if(isset($opts['cond']))
			{
				$str .= ' ON ';
				
				if(is_object($opts['cond']))
					$str .= $opts['cond']->_build_get_query();
				else
					$str .= $this->_prefix($opts['cond']);
			}
			
			
			$str .= "\n";
		}
		
		return $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Builds the WHERE part of the query.
	 * 
	 * @access private
	 * @param $select The where data
	 * 
	 * @return A string
	 */
	function _build_where($where)
	{
		if(is_object($where))
		{
			return $where->_build_get_query();
		}
		elseif(is_array($where))
		{
			$str = '';
			$i = 0;
			
			foreach($where as $key => $col)
			{
				if(in_array($col,array('OR','IN')))
				{
					$str .= " $col ";
					$i = 0;
					continue;
				}
				
				// add AND:s if needed
				$str .= $i++ > 0 ? ' AND ' : '';
				
				// these are modifiers that should be preserved
				if(in_array($col,array('NOT','LIKE')))
				{
					$str .= " $col ";
					$i = 0;
					continue;
				}
				
				// not numeric tells us that we have a column name
				if( ! is_numeric($key))
				{
					// do we need to add an equal sign?
					if( ! $this->_has_operator($key))
					{
						if(is_null($col))
							$str .= $this->_prefix($key) . ' IS NULL';
						else
							$str .= $this->_prefix($key) . ' = ' . $this->_build_where($col);
					}
					else
					{
						$str .= $this->_prefix($key) . ' ' . $this->_build_where($col);
					}
				}
				else
				{
					$str .= $this->_build_where($col);
				}
			} // endforeach
			
			return $str;
		}
		
		return $this->_prefix($where);
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Prefixes all tables in the supplied string,
	 * 
	 * @access private
	 * @param $str The string containing the tablenames
	 * @return string
	 */
	function _prefix($str)
	{
		if(strpos($str, '.')) // cannot start with a dot, just ignore false and zero
		{
			foreach($this->q_to_prefix as $table)
			{
				if(strpos($str, $table) !== false)
				{
					$str = preg_replace('@(?<=^|\s|\.|`|")'.$table.'(?=$|\s|\.|`|")@i', $this->q_prefix . $table, $str);
				}
			}
		}
		
		return $str;
	}
	
	// --------------------------------------------------------------------
		
	/**
	 * Prefixes the string supplied, and adds the table name to the to prefix list.
	 * 
	 * @access private
	 * @param $str The string to prefix
	 * @return string
	 */
	function _add_prefix($str)
	{
		$this->q_to_prefix[] = $str;
		
		return $this->q_prefix .  $str;
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Determines if a string has an operator.
	 * 
	 * From CI_active_rec.
	 * 
	 * @access private
	 * @param $str The string to be filtered
	 * 
	 * @return string
	 */
	function _has_operator($str)
	{
		$str = trim($str);
		if ( ! preg_match("/(\s|<|>|!|=|is null|is not null)/i", $str)){
			return FALSE;
		}
		return true;
	}
	
	// --------------------------------------------------------------------
	
	function escape($str)
	{
		$CI =& get_instance();
		return $CI->db->escape($str);
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Own implementation of the _protect identifiers.
	 * 
	 * The CI _protect_identifiers is too tangled with the db prefixing to use.
	 * 
	 * Can be called statically.
	 * 
	 * @access private
	 * @param $item The string to protect
	 * @param $first_word_only If to protect the first word only
	 * @return string
	 */
	function _protect_identifiers($item, $first_word_only = FALSE)
	{
		$CI =& get_instance();
		
		switch($CI->db->dbdriver)
		{
			case 'mssql':
			case 'oc8':
			case 'postgre':
				$quot = '"';
				break;
			
			case 'sqlite':
				$quot = ''; // hmmm, strange
				break;
			
			default:
				$quot = '`';
				break;
		}
		
		/*
		 * Some of the code below has been taken from the CI db::_protect_identifiers()
		 */
		if(ctype_alnum($item) === FALSE)
		{
			$lbound = $first_word_only === TRUE ? '' : '|\s|\(|\.';
			
			$item = preg_replace('/(?<=^'.$lbound.')([\w\d\-\_]+?)(?=\.|\s|\)|$)/iS', $quot.'$1'.$quot, $item);
		}
		else
		{
			return $quot.$item.$quot;
		}
		
		$exceptions = array('AS', '/', '-', '%', '+', '*', 'OR', 'IS');
		
		foreach($exceptions as $exception)
		{
		
			if(stristr($item, ' '.$quot.$exception.$quot.' ') !== FALSE)
			{
				$item = preg_replace('/ '.$quot.'('.preg_quote($exception).')'.$quot.' /i', ' $1 ', $item);
			}
		}
		
		return $item;
		/*
		 * End code from CI
		 */
	}
	
	// --------------------------------------------------------------------
	
	function _protect_identifiers_walk(&$str)
	{
		$str = $this->_protect_identifiers($str);
	}
}

/**
 * Some sugar for all those who are lazy.
 */
class Query extends IgnitedQuery{}

/**
 * @}
 */

/* End of file ignitedquery.php */