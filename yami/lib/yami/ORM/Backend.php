<?php
namespace yami\ORM;
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
	
	public function query($query, array $tables, $cluster = 'default', $deepLookup = false) {
		if($deepLookup === true && isset($this->childBackend)) {
			$res = $this->childBackend->query($query, $tables ,$cluster, $deepLookup);
		} else {
			$res = $this->_query($query, $tables, $cluster, $deepLookup);
		}
		return $res;
	}
	
	protected abstract function _query($query, array $tables, $cluster = 'default', $deepLookup = false);
	
	/**
	 * 
	 * Enter description here ...
	 * @param string $query
	 * @param array $tableIdMap
	 * @param string $cluster
	 * @param boolean $deepLookup
	 */
	public function select($query, array $tableIdMap, $cluster = 'default', $deepLookup = false) {
		if($deepLookup === true && isset($this->childBackend)) {
			$res = $this->childBackend->select($query, $tableIdMap, $cluster, $deepLookup = false);
		} else {
			$res = $this->_select($query, $tableIdMap, $cluster, $deepLookup = false);
		}
		return $res;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param string $query
	 * @param array $tableIdMap
	 * @param string $cluster
	 * @param boolean $deepLookup
	 */
	abstract protected function _select($query, array $tableIdMap, $cluster = 'default', $deepLookup = false);
	
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
	
	abstract public function beginTransaction($cluster = 'default');
	
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