<?php
namespace yami\ORM;

use yami\Database\Adapter\Mysqli\Field;

use yami\ORM\Backend\Recordset;

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
	public $count;	
	private $array = array();
	protected $map;
	
	protected $select;
	
	
	public function __construct(array $array = null) {
		parent::__construct((is_array($array) ? $array : array()));		
	}
	
	public static function getAll() {
		return static::select()->execute();
	}
	
	/**
	 * 
	 * @param array $key Hash of keys
	 * @return boolean
	 */
	public function containsKey(array $key) {
		return isset($this->map[implode('.', $key)]);
	}
	
	/**
	 * 
	 * @param array $key Hash of keys
	 * @return array
	 * @throws \Exception
	 */
	public function fetchByKey(array $key) {
		if(isset($this->map[implode('.', $key)])) {
			return $this[$this->map[implode('.', $key)]];
		} else {
			throw new \Exception('Item not found:'.implode('.', $key));			
		}
	}
	
	public function getIndexByKey(array $key) {
		if(isset($this->map[implode('.', $key)])) {
			return $this->map[implode('.', $key)];
		} else {
			throw new \Exception('Item not found:'.implode('.', $key));
		}
	}
	
	protected function mapById() {
		$this->map = array();
		$ids = static::$ids;
		foreach($this as $index => $item) {
			$tmp = array();
			foreach($ids as $id) {
				$tmp[$id] = $item[$id];
			}
			ksort($tmp);
			$this->map[implode('.', $tmp)] = $index;
		}
//		print_r($this->map);exit;
	}
	
	public function setCount($count) {
		if(!is_null($count)) {
			if(is_numeric($count) && (round($count) == $count)) {
				$this->count = $count;
			} else {
				throw new \Exception('Count needs to be an Integer:'.$count);
			}
		}
	}
	
	/**
	 * 
	 * @return \yami\ORM\Select
	 */
	public static function select($string = null) {
		if(is_null($string)) {
			$select = new Select();
			$select->setCollectionName(get_called_class());
			$select->addTable(new Table(static::getTableName()));
			$select->addField(new \yami\Database\Sql\Field('*', null, static::getTableName()));
		} else {
			$select = new Select($string);
			$select->setCollectionName(get_called_class());
		}
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
	 * @param mixed $count 			- possible values: false = no count, 1 = force count, 2 = try count (if cached already or if retrived less results than requested, count will be returned) 
	 * @return Recordset
	 */
	public static function fetch($query, $placeholders = null, $count = false, $deepLook = false) {
		if($query instanceof Select) {
			$q = $query;
		} else {
			$q = new Select($query);
		}		
		if(!is_null($placeholders)) {
			$q->setPlaceholders($placeholders);
		}
		$q->setCollectionName(get_called_class());
//		print_r($q->getTableNamesList());
		return static::getBackend()->select($q, static::getIds(), $count, 'default', $deepLook);
		//return static::getBackend()->select($q, array(static::getTableName() => static::getIds()), 'default', $deepLook);
	}
	
	/**
	 * 
	 * @param mixed $query
	 * @param array $placeholders
	 * @param mixed $count		- possible values: false = no count, 1 = force count, 2 = try count (if cached already or if retrived less results than requested, count will be returned)
	 * @return \yami\ORM\Collection
	 */
	public static function load($query, $placeholders = null, $count = false, $deepLook = false) {
		$recordset = static::fetch($query, $placeholders, $count, $deepLook);
		$n = new Static($recordset->getArrayCopy());
		$n->map = $recordset->idRecordMap;
		$n->setCount($recordset->totalCount);
		return $n;
	}	
		
	/**
	 * Factory to create a model related to this collection
	 * 
	 * @param array $data
	 */
	abstract public function getEntity(array $data);
	
	public function current() {
		$data = parent::current();
		if($data instanceof Entity) {
			return $data;
		} else {
			$this[parent::key()] = $this->getEntity($data);
		}
		return $this[parent::key()];
	}
	 	
 	public function offsetGet($index) {
 		$data = parent::offsetGet($index);
 		if($data instanceof Entity) {
 			return $data;
 		} else {
 			$this[$index] = $this->getEntity($data);
 		}
 		return $this[$index];
 	}

 	
 	public function fetchIds() {
 		$ids = static::$ids;
 		parent::rewind();
 		$out = array();
 		while(parent::valid()) {
 			$c = parent::current();
 			foreach($ids as $id) {
 				$out[$id][] = $c[$id];
 			}
 			parent::next();
 		}
 		return $out;
 	}
 	
 	
 	public function hasPosition($position) {
 		return ($this->count() - 1 >= $position);
 	}
 	
 	public function getByIndexPosition($position) {
 		$keys = array_keys($this->getArrayCopy());
 		return $this[$keys[0]];
 	}
}