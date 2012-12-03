<?php
namespace yami\Database;

interface Result {

	const FETCH_ASSOC = 1;
	const FETCH_NUM = 2;
	const FETCH_BOTH = 3;

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


	/**
	 * Sets the fetch mode
	 */
	public function fetchMode($arg);


	/**
	 * Fetch a single column as a one dimentional array
	 *
	 * @param string $columnName
	 */
	public function fetchCol($columnName);


	/**
	 * Returns a mapped result. Useful in case of join queries
	 *
	 * @return array
	 */
	public function fetchMapped();

}