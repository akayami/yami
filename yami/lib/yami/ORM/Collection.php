<?php
namespace yami\ORM;

use yami\Database\Sql\Operator;

use yami\Database\Sql\ConditionField;

use yami\Database\Sql\Condition;

use yami\Database\Sql\ConditionBlock;

use yami\ORM\Select;

use yami\Database\Sql\Table;

abstract class Collection extends \ArrayIterator {

	protected static $backendMap = array();			// model specific backend stack
	protected static $tableName;
	protected static $ids;
	protected static $cluster = 'default';	
	private $array = array();
	
	protected $select;

	/**
	 * 
	 * @return \yami\ORM\Select
	 */
	public static function select() {
		$select = new Select();
		$select->setCollectionName(get_called_class());
		$select->addTable(new Table(static::getTableName()));
		return $select;		
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
		return \yami\ORM\Backend\Manager::getInstance()->get(static::$backend);
	}

	
	public static function getTableName() {
		return static::$tableName;
	}
	
	protected static function getIds() {
		return static::$ids;
	}
	
	protected static function getCluster() {
		return static::$cluster;
	}
	
	/**
	 * 
	 * @param mixed $query
	 * @param array $placeholders
	 * @return \yami\Database\Result\CommonResult
	 */
	public static function fetch($query, $placeholders = array(), $deepLook = false) {
		if($query instanceof Select) {
			$q = $query;
		} else {
			$q = new Select($query);
		}
		$q->setCollectionName(get_called_class());
		return static::getBackend()->select($q, array(static::getTableName() => static::getIds()), null, $deepLook);
	}
	
	/**
	 * 
	 * @param mixed $query
	 * @param array $placeholders
	 * @return \yami\ORM\Collection
	 */
	public static function load($query, $placeholders = array()) {
		return new Static(static::fetch($query, $placeholders)->getArrayCopy());
	}	
		
	/**
	 * Factory to create a model related to this collection
	 * 
	 * @param array $data
	 */
	abstract public function getEntity(array $data);
	
 	public function current() {
 		return $this->getEntity(parent::current());
 	} 
 	
 	public function offsetGet($index) {
 		return $this->getEntity(parent::offsetGet($index));
 	}
}