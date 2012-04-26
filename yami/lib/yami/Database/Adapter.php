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
	
	
	public function quote($value);

}