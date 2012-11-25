<?php
namespace yami\Database;

use yami\Database\Result;

interface Adapter {

	public function __construct(array $config);

	/**
	 * Executes a query with placeholders
	 *
	 * @param string $query
	 * @param array $phs
	 * @return string
	 */
	public function pquery_sql($query, array $phs = null);

	/**
	 * Executes a regular query
	 *
	 * @param $query
	 * @return Result
	 */
	public function query($query);

	/**
	 *
	 * Enter description here ...
	 * @param string $query
	 * @param array $phs
	 * @return Result
	 */
	public function pquery($query, array $phs = null);


	/**
	 *
	 * Enter description here ...
	 */
	public function transaction();

	/**
	 *
	 * Enter description here ...
	 */
	public function commit();

	/**
	 *
	 * Enter description here ...
	 */
	public function rollback();

	/**
	 *
	 * Enter description here ...
	 */
	public function getLastInsertID();


	/**
	 * Enter description here ...
	 * @return int
	 */
	public function affectedRows();


	/**
	 * Returns Type
	 *
	 * @return string
	 */
	public function getType();


	/**
	 * Return true if connection is a master (write)
	 * @return boolean
	 */
	public function isMaster();

	/**
	 *
	 * @param string $value 	// Quote a value like a string
	 * @param boolena $escape	// Also escape the value
	 * @return $string
	 */
	public function quote($value, $escape = true);

	/**
	 *
	 * @param string $value
	 * @return $string
	 */
	public function escape($value);

	/**
	 *
	 * @param string $table
	 * @param array $records
	 * @param array $filter
	 */
	public function insert($table, array $data = array(), array $filter = array());

	/**
	 *
	 * @param string $table
	 * @param array $where
	 * @param array $records
	 * @param array $filter
	 */
	public function update($table, array $where, array $data = array(), array $filter = array());

	/**
	 *
	 * @param string $table
	 * @param array $where
	 */
	public function delete($table, array $where);

}