<?php
namespace yami\ORM;

use yami\Database\Result;

use yami\ORM\Backend\Recordset;

abstract class Entity extends \ArrayObject {
	
	protected static $defaultBackend; 				// The default backend stack
	protected static $backendMap = array();			// model specific backend stack
	protected static $tableName;	
	protected static $ids;
	protected static $cluster = 'default';
	protected static $backendConfig;
	protected static $backend = 'default';
//	protected static $structure;
	public $counterFields = array();
	
	public function __construct($data = null, $masterValues = false) {
		
		if(is_object($data)) {
			$this->setData($data->getArrayCopy());
		} elseif (is_array($data)) {
			$this->setData($data);
		} else {
			if(!is_null($data)) {
				$this->_byId($data);
			}
		}
		if($masterValues === true) {
			$this->update(true);
		}
	}
	
	protected function setData(array $data) {
		foreach($data as $column => $val) {
			$data[str_replace($this::$tableName.".", '', $column, &$c)] = $val;
			if($c > 0) {
				unset($data[$column]);
			}
		}
		parent::__construct($data);
	}
		
	public static function getStructure() {
		return static::getBackend()->query('SHOW COLUMNS FROM '.static::getTableName(), array('_structure_'.static::getTableName()));
	}
	
	public static function getAll() {
		return static::fromRecordset(static::getBackend()->select('SELECT * FROM '.static::getTableName(), array(static::getTableName() => static::getIds())));
	}
	
	public static function make(array $modelData) {
		return new static($modelData);
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param array $data
	 * @return array
	 */
	public static function fromArray(array $data) {
		foreach($data as $index => $vals) {
			$data[$index] = new static($vals);
		}
		return $data;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param Recordset $recordset
	 */
	public static function fromRecordset(Recordset $recordset) {
		$tmp = array();
		foreach($recordset as $index => $row) {
			$t = array();
			foreach($row as $key => $value) {
				$aKey = explode(".", $key);
				if($aKey[0] == static::getTableName()) {
					$t[$aKey[1]] = $value;		
				}
			}
			$tmp[$index] = new static($t);
		}
		return $tmp;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param Result $result
	 * @return array
	 */
	public static function fromResult(Result $result) {
		$fields = $result->fields();
		$map = array();
		foreach($fields as $index => $field) {
			 if($field->table() == static::getTableName()) {
			 	$map[] = $index;
			 }
		}
		$result->fetchMode($result::FETCH_NUM);
		$output = array();
		while($row = $result->fetch()) {
			$tmp = array();
			foreach($row as $key => $val) {
				if(in_array($key, $map)) {
					$tmp[$fields[$key]->name] = $val;
				}
			}
			$output[] = new static($tmp, true);
		}		
		return $output;
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
	 * 
	 * 
	 * @return Backend
	 */
	protected static function provisionBackend() {
		return \yami\ORM\Backend\Manager::getInstance()->get(static::$backend);
	}
	
	public function delete() {
		$ids = $this->getIds();
		$keys = array();
		foreach($ids as $id) {
			if(isset($this[$id])) {
				$keys[$id] = $this[$id];
			} elseif(isset($this[static::$tableName.'.'.$id])) {
				$keys[$id] = $this[static::$tableName.'.'.$id];
			} else {
				throw new \Exception('Missing key :'.$id);
			}
		}
		static::getBackend()->delete($keys, $this, $this->getTableName(), $this->getIds(), $this->getCluster());
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param array $data
	 * @param float $amount
	 */
	public function increment(array $data) {
		$ids = $this->getIds();
		$keys = array();
		foreach($ids as $id) {
			if(isset($this[$id])) {
				$keys[$id] = $this[$id];
			} else {
				throw new \Exception('Missing key :'.$id);
			}
		}
		return static::getBackend()->increment($data, $keys, $this, $this->getTableName(), $ids, $this->getCluster());	
	}	
	
	
	/**
	 * Retrives data by id
	 * 
	 * @param mixed $id
	 * @throws Exception
	 */
	protected function _byId($id) {
		$this->exchangeArray(array());
		$this->setData(static::_byId_Routine($id));
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param mixed $id
	 * @return Entity
	 */
	
	public static function byId($id) {
		return new static(static::_byId_Routine($id));
	}
	
	/**
	 * Main byId logic, which first attemts to get data from to layer, and the, falls back to the lowest layer if data of wrong type (ie, corrupted Memcached ?)
	 * 
	 * @param $id
	 * @throws Exception
	 */
	private static function _byId_Routine($id) {
		try {
			$val = static::getBackend()->get($id, static::getTableName(), static::getIds(), static::getCluster(), false); // First trying to get data from "shallow" sources
			if(!is_array($val)) {
				throw new \Exception('Wrong data type returned');
			}
		} catch(Exception $e) {
			try {
				$val = static::getBackend()->get($id, static::getTableName(), static::getIds(), static::getCluster(), true); // If data from shallow sources is invalid, look deeper.
			} catch (Exception $e) {
				throw new \Exception('Item not found');
			}
		}
		return $val;
	}
	
	public function save() {
		$keys = $this->getIds();
		$hasKeys = false;
		foreach($keys as $key) {
			if(isset($this[$key])) {
				$hasKeys = true;
			} else {
				$hasKeys = false;
			}
		} 
		if($hasKeys) {
			return $this->update();
		} else {
			return $this->insert();
		}
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param boolean $skipMaster
	 * @throws Exception
	 */
	public function update($skipMaster = false) {
		$ids = $this->getIds();
		$keys = array();
		foreach($ids as $id) {
			if(isset($this[$id])) {
				$keys[$id] = $this[$id];
			} else {
				throw new \Exception('Missing key :'.$id);
			}
		}
		return static::getBackend()->update($keys, $this, $this->getTableName(), $ids, $this->getCluster(), $skipMaster);
	}
	
	public function insert() {
		$ids = $this->getIds();
		$keys = array();
		foreach($ids as $id) {
			if($id !== $this->getAutoIncrement()) {
				if(isset($this[$id])) {
					$keys[$id] = $this[$id];
				} else {
					throw new \Exception('Missing key: '.$id);
				}
			}
		}
		return static::getBackend()->insert($keys, $this, $this->getTableName(), $this->getIds(), $this->getCluster());
	}

	/**
	 * Returns the array of keys
	 * 
	 * @return array
	 */
	public function getKeys() {
		$ids = $this->getIds();
		$keys = array();
		foreach($ids as $id) {
			if($id === $this->getAutoIncrement()) {
				$keys[$id] = $this[$id];
			}
		}
		return $keys;
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
	
	public function getAutoIncrement() {
		return 'id';
	}
	
	public function offsetSet($index, $newval) {
		parent::offsetSet(str_replace($this::$tableName.".", '', $index), $newval);
	}
	
	
	public function __set($key, $val) {
		$this[$key] = $val;
	}
	
	public function __get($key) {
		return $this[$key];
	}
		
	public function __isset($key) {
		return isset($this[$key]);
	}
	
	public function __unset($key) {
		unset($this[$key]);
	}
	
	public function get($index, $default = '') {
		if(isset($this[$index])) {
			return $this[$index];
		} else {
			return $default;
		}
	}
	
	public function set($index, $value) {
		$this[$index] = $value;
	}
}
