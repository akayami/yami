<?php
namespace yami\ORM;

abstract class Collection extends \ArrayIterator {

	protected static $backendMap = array();			// model specific backend stack
	protected static $tableName;
	protected static $ids;
	protected static $cluster = 'default';	
	private $array = array();

	
	public function __construct($array) {
		foreach($array as $index => $row) {
			foreach($row as $key => $val) {
				$aKey = explode('.', $key);
				$array[$index][$aKey[1]] = $val;
				unset($array[$index][$key]);
			}
		}
		parent::__construct($array);
	} 
	
	
	/**
	 * Enter description here ...
	 * @param Backend $backend
	 */
	public static function setBackend(Backend $backend) {
		static::$backendMap[get_called_class()] = $backend;
	}

	/**
	 * Returns a backend
	 * 
	 * @return Backend
	 */
	public static function getBackend() {
		if(!isset(static::$backendMap[get_called_class()])) {
			static::setBackend(static::provisionBackend()); 		
		}
		return static::$backendMap[get_called_class()];
	}
	
	/**
	 * @return Backend
	 */
	protected static function provisionBackend() {
		throw new Exception('You need to implement provisionBackend in your collection');
	}

	
	protected static function getTableName() {
		return static::$tableName;
	}
	
	protected static function getIds() {
		return static::$ids;
	}
	
	protected static function getCluster() {
		return static::$cluster;
	}
	
	
	public static function getAll($limit = null, $offset = null) {
		return new static(static::getBackend()->select(
			self::getQuery('SELECT * FROM '.static::getTableName(), $limit, $offset), 
			array(static::getTableName() => static::getIds())
		)->getArrayCopy());
	}

	public static function get($params) {
		$q = 'SELECT * FROM '.static::getTableName();
		if(isset($params['where'])) {
			$q .= ' WHERE '.implode(' AND ', $params['where']);
		}
		if(isset($params['order'])) {
			$q .= ' ORDER BY '.implode(', ',$params['order']);
		}
		if(isset($params['limit'])) {
			$q .= ' LIMIT '.implode(', ', $params['limit']);
		}
		return new static(static::getBackend()->select(
			$q, array(static::getTableName() => static::getIds())
		)->getArrayCopy());
	}	
	
	public static function search($argument = null, $limit = null, $offset = null) {
		$argument = 123;
		return new static(static::getBackend()->select($query, $tableIdMap));
	}
	
	private static function getQuery($query, $limit = null, $offset = null) {
		if($limit != null) {
			$query .= ' LIMIT '.$limit;
		}
		
		if($offset != null) {
			$query .= ' OFFSET '.$offset;
		}
		return $query;
	}
	
	/**
	 * Factory to create a model related to this collection
	 * 
	 * @param array $data
	 */
	abstract public function getModel(array $data);
	
 	public function current() {
 		return $this->getModel(parent::current());
 	} 	
}