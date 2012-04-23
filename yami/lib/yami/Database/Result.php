<?php
namespace yami\Database;

interface Result {
	
	/**
	 * 
	 * Enter description here ...
	 * @return array
	 */
	public function fetchAll();
	
	/**
	 *  
	 * @return array
	 */
	public function fetch();

	/**
	 * 
	 * @return int
	 */
	public function length();
	
	
	/**
	 * Returns the list of fields
	 * 
	 * @return array
	 */
	public function fields();
	
	
	/**
	 * Number of affected rows
	 * 
	 * @return int
	 */
	public function affectedRows();
	
}