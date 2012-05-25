<?php
namespace yami\ORM;

use yami\ORM\Backend\Db\UnbufferedRecordset;

use yami\ORM\Backend\Recordset;

use yami\Database\Result\CommonResult;

use yami\ORM\Backend\Exception;

use yami\ORM\Entity;

abstract class Backend {

	/**
	 * 
	 * Enter description here ...
	 * @var yami\ORM\Backend
	 */
	protected $childBackend;
	
	/**
	 * 
	 * Enter description here ...
	 * @param mixed $key
	 * @param string $table
	 * @param array $ids
	 * @param string $cluster
	 * @param bool $deepLookup
	 * @return array
	 */
	abstract public function get($key, $table, $ids, $cluster, $deepLookup = false);

	/**
	 * 
	 * Enter description here ...
	 * @param mixed $key
	 * @param Entity $subject
	 * @param string $table
	 * @param array $ids
	 * @param string $cluster
	 * @return Entity
	 */
	public function update($key, Entity $subject, $table, $ids, $cluster, $skipMaster = false) {
		if(isset($this->childBackend)) {
			try {
				$subject = $this->childBackend->update($key, $subject, $table, $ids, $cluster, $skipMaster);
			} catch(Exception $e) {
				if($e->getKey() != 'nochanges') {
					throw $e; // Catching only nochanges failuers
				} else {
					$subject = $subject::make($this->get($key, $table, $ids, $cluster, true));
				}
			}
		} elseif($skipMaster === true) {
			return $subject;
		}
		
		return $this->_update($key, $subject, $table, $ids, $cluster);
			
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function unbufferedSupported() {
		return false;
	}
	
		/**
	 * 
	 * @param Select $query
	 * @param array $tables
	 * @param string $cluster
	 * @param boolean $deepLookup
	 * @return UnbufferedRecordset
	 */
	public function unbufferedQuery(Select $query, array $tables, $cluster = 'default', $deepLookup = false) {
		throw new \Exception('This backend does not support unbuffered requests');
	}
	
	
	/**
	 * 
	 * @param Select $query
	 * @param array $ids
	 * @param string $cluster
	 * @param boolean $deepLookup
	 * @return UnbufferedRecordset
	 */
	public function unbufferedSelect(Select $query, array $ids, $cluster = 'default', $deepLookup = false) {
		throw new \Exception('This backend does not support unbuffered requests');
	}
	
	/**
	 * 
	 * @param Select $query
	 * @param array $ids
	 * @param mixed $count 			- possible values: false = no count, 1 = force count, 2 = try count (if cached already or if retrived less results than requested, count will be returned)
	 * @param string $cluster
	 * @param boolean $deepLookup
	 * @return Recordset
	 */
	public function query(Select $query, array $ids, $count = false, $cluster = 'default', $deepLookup = false) {
		if($deepLookup === true && isset($this->childBackend)) {
			$res = $this->childBackend->query($query, $ids, $count, $cluster, $deepLookup);
		} else {
			$res = $this->_query($query, $ids, $count, $cluster, $deepLookup);
		}
		return $res;
	}
	
	/**
	 * 
	 * @param Select $query
	 * @param array $ids
	 * @param mixed $count 			- possible values: false = no count, 1 = force count, 2 = try count (if cached already or if retrived less results than requested, count will be returned)
	 * @param string $cluster
	 * @param boolean $deepLookup
	 * @return Recordset
	 */
	public function select(Select $query, array $ids, $count = false, $cluster = 'default', $deepLookup = false) {
		if($deepLookup === true && isset($this->childBackend)) {
			$res = $this->childBackend->select($query, $ids, $count, $cluster, $deepLookup);
		} else {
			$res = $this->_select($query, $ids, $count, $cluster, $deepLookup);
		}
		return $res;
	}
	
	/**
	 * 
	 * @param Select $query
	 * @param array $ids
	 * @param mixed $count 			- possible values: false = no count, 1 = force count, 2 = try count (if cached already or if retrived less results than requested, count will be returned)
	 * @param string $cluster
	 * @param boolean $deepLookup
	 */
	abstract protected function _query(Select $query, array $ids, $count = false, $cluster = 'default', $deepLookup = false);
		
	
	/**
	 * 
	 * Enter description here ...
	 * @param Select $query
	 * @param array $ids
	 * @param mixed $count 			- possible values: false = no count, 1 = force count, 2 = try count (if cached already or if retrived less results than requested, count will be returned)
	 * @param string $cluster
	 * @param boolean $deepLookup
	 */
	abstract protected function _select(Select $query, array $ids, $count = false, $cluster = 'default', $deepLookup = false);
	
	/**
	 * 
	 * Enter description here ...
	 * @param mixed $key
	 * @param Entity $subject
	 * @param string $table
	 * @param array $ids
	 * @param string $cluster
	 * @param boolean $skipMaster
	 * @return Entity
	 */
	abstract protected function _update($key, Entity $subject, $table, array $ids, $cluster, $skipMaster = false);
	
	/**
	 * 
	 * Enter description here ...
	 * @param mixed $key
	 * @param Entity $subject
	 * @param string $table
	 * @param array $ids
	 * @param string $cluster
	 * @return Entity
	 */	
	public function insert($key, Entity $subject, $table, $ids, $cluster) {
//		error_log(get_class($this).'-'.print_r($subject, true));
		if(isset($this->childBackend)) {
			$subject = $this->childBackend->insert($key, $subject, $table, $ids, $cluster);
//			error_log(print_r($subject, true));
		}
		return $this->_insert($key, $subject, $table, $ids, $cluster);
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param mixed $key
	 * @param Entity $subject
	 * @param string $table
	 * @param array $ids
	 * @param string $cluster
	 */
	abstract protected function _insert($key, Entity $subject, $table, array $ids, $cluster);
	
	/**
	 * 
	 * Enter description here ...
	 * @param mixed $key
	 * @param Entity $subject
	 * @param string $table
	 * @param array $ids
	 * @param string $cluster
	 */
	public function delete($key, Entity $subject, $table, $ids, $cluster) {
		if(isset($this->childBackend)) {
			$subject = $this->childBackend->delete($key, $subject, $table, $ids, $cluster);
		}
		return $this->_delete($key, $subject, $table, $ids, $cluster);		
	}
	
	abstract protected function _delete($key, Entity $subject, $table, $ids, $cluster);
	
	/**
	 * 
	 * Enter description here ...
	 * @param Backend $backend
	 */
	public function setChildBackend(Backend $backend) {
		$this->childBackend = $backend;
	}
	
	abstract public function beginTransaction();
	
	abstract public function commitTransaction();
	
	abstract public function rollbackTransaction();
	
	/**
	 * 
	 * Enter description here ...
	 * $paran array $data
	 * @param mixed $key
	 * @param Entity $subject
	 * @param string $table
	 * @param array $ids
	 * @param string $cluster
	 */
	public function increment($data, $key, Entity $subject, $table, $ids, $cluster) {
		if(isset($this->childBackend)) {
			$subject = $this->childBackend->increment($data, $key, $subject, $table, $ids, $cluster);
			$this->_update($key, $subject, $table, $ids, $cluster); // Just set self to new values;
			return $subject;
		} else {
			return $this->_increment($data, $key, $subject, $table, $ids, $cluster);
		}
	}
	
	abstract protected function _increment($data, $key, Entity $subject, $table, $ids, $cluster);
	
}