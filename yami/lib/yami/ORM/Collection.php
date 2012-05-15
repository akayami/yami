<?php
namespace yami\ORM;

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
	
	/**
	 * 
	 * @param array $key Hash of keys
	 */
	public function containsKey(array $key) {
		if(!isset($this->map)) $this->mapById();
		ksort($tmp);
		$key = implode('.', $tmp);
		return isset($this->map[$key]);	
	}
	
	/**
	 * 
	 * @param array $key Hash of keys
	 * @throws \Exception
	 */
	public function fetchByKey(array $key) {
		if(!isset($this->map)) $this->mapById();
		ksort($key);
		$key = implode('.', $key);
		if(!isset($this->map[$key])) {
			return false;
			//throw new \Exception('Item '.$key.' not available');
		} else {
			return $this[$this->map[$key]];
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
	 * @return Recordset
	 */
	public static function fetch($query, $placeholders = array(), $deepLook = false) {
		if($query instanceof Select) {
			$q = $query;
		} else {
			$q = new Select($query);
			$q->setPlaceholders($placeholders);
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
	public static function load($query, $placeholders = array(), $deepLook = false) {
		$recordset = static::fetch($query, $placeholders);
		$n = new Static($recordset->getArrayCopy());
		$n->setCount($recordset->count);
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
 			if(!is_array($data)) {
 				var_dump($data);exit;
 			}
 			$this[parent::key()] = $this->getEntity($data);
 		}
 		return $this[parent::key()];
 	} 
 	
 	public function offsetGet($index) {
 		$data = parent::offsetGet($index);
 		if($data instanceof Entity) {
 			return $data;
 		} else {
 			if(!is_array($data)) {
 				var_dump($data);exit;
 			}
 			$this[$index] = $this->getEntity($data);
 		}
 		return $this[$index];
 	}
}