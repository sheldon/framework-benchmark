<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * Created on 2008 Jun 28
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
 * A wrapper for the CodeIgniter ActiveRecord class.
 */
class IR_base{
	/**
	 * Constructor.
	 */
	function IR_base()
	{
		$CI =& get_instance();
		$this->db =& $CI->db;
	}
	
	
	// --------------------------------------------------------------------
		
	/**
	 * Resets the current query, enabling you to restart the building process
	 * @return void
	 */
	function reset()
	{
		$this->db->_reset_select();
	}
	
	// --------------------------------------------------------------------
	
	function &dbprefix($table = ''){
		return $this->db->dbprefix($table);
	}
	function &select($select = '*', $protect_identifiers = TRUE){
		$this->db->select($select, $protect_identifiers);
		return $this;
	}
	function &select_max($select = '', $alias=''){
		$this->db->select_max($select, $alias);
		return $this;
	}
	function &select_min($select = '', $alias=''){
		$this->db->select_min($select, $alias);
		return $this;
	}
	function &select_avg($select = '', $alias=''){
		$this->db->select_avg($select, $alias);
		return $this;
	}
	function &select_sum($select = '', $alias=''){
		$this->db->select_sum($select, $alias);
		return $this;
	}
	function &distinct($val = TRUE){
		$this->db->distinct($val);
		return $this;
	}
	function &from($from){
		$this->db->from($from);
		return $this;
	}
	function &join($table, $cond, $type = ''){
		$this->db->join($table, $cond, $type);
		return $this;
	}
	function &where($key, $value = NULL, $escape = TRUE){
		$this->db->where($key, $value, $escape);
		return $this;
	}
	function &or_where($key, $value = NULL, $escape = TRUE){
		$this->db->or_where($key, $value, $escape);
		return $this;
	}
	function &orwhere($key, $value = NULL, $escape = TRUE){
		$this->db->orwhere($key, $value, $escape);
		return $this;
	}
	function &where_in($key = NULL, $values = NULL){
		$this->db->where_in($key, $values);
		return $this;
	}
	function &or_where_in($key = NULL, $values = NULL){
		$this->db->or_where_in($key, $values);
		return $this;
	}
	function &where_not_in($key = NULL, $values = NULL){
		$this->db->where_not_in($key, $values);
		return $this;
	}
	function &or_where_not_in($key = NULL, $values = NULL){
		$this->db->or_where_not_in($key, $values);
		return $this;
	}
	function &like($field, $match = '', $side = 'both'){
		$this->db->like($field, $match, $side);
		return $this;
	}
	function &not_like($field, $match = '', $side = 'both'){
		$this->db->not_like($field, $match, $side);
		return $this;
	}
	function &or_like($field, $match = '', $side = 'both'){
		$this->db->or_like($field, $match, $side);
		return $this;
	}
	function &or_not_like($field, $match = '', $side = 'both'){
		$this->db->or_not_like($field, $match, $side);
		return $this;
	}
	function &orlike($field, $match = '', $side = 'both'){
		$this->db->orlike($field, $match, $side);
		return $this;
	}
	function &group_by($by){
		$this->db->group_by($by);
		return $this;
	}
	function &groupby($by){
		$this->db->groupby($by);
		return $this;
	}
	function &having($key, $value = '', $escape = TRUE){
		$this->db->having($key, $value, $escape);
		return $this;
	}
	function &orhaving($key, $value = '', $escape = TRUE){
		$this->db->orhaving($key, $value, $escape);
		return $this;
	}
	function &or_having($key, $value = '', $escape = TRUE){
		$this->db->or_having($key, $value, $escape);
		return $this;
	}
	function &order_by($orderby, $direction = ''){
		$this->db->order_by($orderby, $direction);
		return $this;
	}
	function &orderby($orderby, $direction = ''){
		$this->db->orderby($orderby, $direction);
		return $this;
	}
	function &limit($value, $offset = ''){
		$this->db->limit($value, $offset);
		return $this;
	}
	function &offset($offset){
		$this->db->offset($offset);
		return $this;
	}
	function db_set($key, $value = '', $escape = TRUE){
		return $this->db->set($key, $value, $escape);
	}
	function db_get($table = '', $limit = null, $offset = null){
		return $this->db->get($table, $limit, $offset);
	}
	function count_all_results($table = ''){
		return $this->db->count_all_results($table);
	}
	function db_get_where($table = '', $where = null, $limit = null, $offset = null){
		return $this->db->get_where($table, $where, $limit, $offset);
	}
	function db_insert($table = '', $set = NULL){
		return $this->db->insert($table, $set);
	}
	function db_update($table = '', $set = NULL, $where = NULL, $limit = NULL){
		return $this->db->update($table, $set, $where, $limit);
	}
	function db_empty_table($table = ''){
		return $this->db->empty_table($table);
	}
	function db_truncate($table = ''){
		return $this->db->truncate($table);
	}
	function db_delete($table = '', $where = '', $limit = NULL, $reset_data = TRUE){
		return $this->db->delete($table, $where, $limit, $reset_data);
	}
	function db_use_table($table){
		return $this->db->use_table($table);
	}
	function &start_cache(){
		$this->db->start_cache();
		return $this;
	}
	function &stop_cache(){
		$this->db->stop_cache();
		return $this;
	}
	function &flush_cache(){
		$this->db->flush_cache();
		return $this;
	}
}
/**
 * @}
 */

/* End of file base.php */
/* Location: ./application/models/ignitedrecord/base.php */