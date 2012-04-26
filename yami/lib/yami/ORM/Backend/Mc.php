<?php
namespace yami\ORM\Backend;

use yami\ORM\Entity;

use yami\ORM\Backend;

class Mc extends Backend {
	
	/**
	 * @var Memcached
	 */
	protected $handle;
	
	protected $key_concat = '|';
	
	protected $transaction = null;
	
	protected $d_transaction = null;
	
	protected $isTransaction = false;
	
	protected $transaction_increment = null;
	
	public function __construct(\Memcached $handle) {
		$this->handle = $handle;
	}
	
	public function beginTransaction($cluster = 'default') {
				
		if(isset($this->childBackend)) {
			$this->childBackend->beginTransaction($cluster);
		}
		$this->isTransaction = true;
		
	}
	
	public function commitTransaction() {

		if(isset($this->childBackend)) {
			$this->childBackend->commitTransaction();
		}
		if(!is_null($this->transaction)) {
			if(!$this->handle->setMulti($this->transaction)) {
				throw new \Exception('Failed to commit transaction to MC');
			}
		}
		if(!is_null($this->d_transaction)) {
			foreach($this->d_transaction as $key => $val) {
				if($val) {
					$this->handle->delete($key);
				}
			}
		}
		if(isset($this->transaction_increment) && $this->incr($this->transaction_increment)) {
			throw new \Exception('Failed to transactionally push data into MC');
		}
		$this->isTransaction = false;
		unset($this->transaction);
		
	}
	
	public function rollbackTransaction() {
		
		if(isset($this->childBackend)) {
			$this->childBackend->rollbackTransaction();
		}		
		$this->isTransaction = false;
		unset($this->transaction_increment);
		unset($this->transaction);
		
	}
	
	protected function _query($query, array $tables, $cluster = 'default', $deepLookup = false) {
		$hash = $this->getSelectKey($query);
		$mcRes = $this->handle->get($hash);
		if($this->handle->getResultCode() != \Memcached::RES_SUCCESS) {
			if(isset($this->childBackend)) {
				$res = $this->childBackend->select($query, $tables, $cluster, $deepLookup);
				$this->handle->set($hash, $res->getArrayCopy());
				if($this->handle->getResultCode() != \Memcached::RES_SUCCESS) {
					error_log('Failed to add '.$hash);
				}
				foreach($tables as $table) {
					$this->addRelatedSets($table, $hash);
				}
				return $res;
			} else {
				return new Recordset();
			}
		} else {
			return new Recordset($mcRes);
		}
	}
	
	/**
	 * 
	 * @param string $query
	 * @param string $hash
	 * @param array $tableIdMap
	 * @param string $cluster
	 * @param boolean $deepLookup
	 * @throws Exception
	 * @return Recordset
	 */
	private function selectRefresh($query, $hash, array $tableIdMap, $cluster = 'default', $deepLookup = false) {
		$res = $this->childBackend->select($query, $tableIdMap, $cluster, $deepLookup = false); // Get Full Resultset from DB
		$explodedRes = $this->explodeFromResultSet($res);
		$sets = array();
		$keyRecordSet = array();
		foreach($explodedRes as $table => $data) {
			foreach($data as $index => $row) {
				$keyVals = array();
				if(isset($tableIdMap[$table])) {
					foreach($tableIdMap[$table] as $id) {
						if(isset($row[$id])) {
							$keyVals[] = $row[$id];
								
							if(!isset($keyRecordSet[$table])) {
								$keyRecordSet[$table] = array();
							}
							if(!isset($keyRecordSet[$table][$index])) {
								$keyRecordSet[$table][$index] = array();
							}
							$keyRecordSet[$table][$index][$id] = $row[$id];
		
						} else {
							throw new \Exception('Provided id '.$id.' is missing in the query output. Mismatched table structure?');
						}
					}
				}
				$sets[$this->prepareKey($keyVals, $table, (isset($tableIdMap[$table]) ? $tableIdMap[$table] : null), $cluster)] = $row;
			}
			$this->addRelatedSets($table, $hash);
		}
		foreach($tableIdMap as $table => $crap) {
			$this->addRelatedSets($table, $hash);
		}
		$sets[$hash] = $keyRecordSet;
		$this->handle->setMulti($sets);
		return $res;		
	}
		
	protected function _select($query, array $tableIdMap, $cluster = 'default', $deepLookup = false) {
		$hash = $this->getSelectKey($query);
		$mcRes = $this->handle->get($hash);
		if($this->handle->getResultCode() != \Memcached::RES_SUCCESS) {
			if(isset($this->childBackend)) {
				return $this->selectRefresh($query, $hash, $tableIdMap, $cluster, $deepLookup);
			} else {
				return null;
			}
		} else {
			$gets = array();
			$reassamble = array();
			foreach($mcRes as $table => $rows) {				
				foreach($rows as $index => $row) {
					$itemKey = $this->prepareKey(array_values($row), $table, array_keys($row), $cluster);
					$gets[] = $itemKey;
					$mcRes[$table][$index] = $itemKey;
				}
			}
			$cached = $this->handle->getMulti($gets);
		
			$missing = array_keys(array_diff_key(array_flip($gets), (is_array($cached) ? $cached : array())));			
			if($missing) {
				/**
				 * @todo Add a better way of cacheing missing items. Right now, one missing item triggers the whole query
				 */
				return $this->selectRefresh($query, $hash, $tableIdMap, $cluster, $deepLookup);
				//return $this->_select($query, $tableIdMap, $cluster, true);
			}
			
			foreach($mcRes as $table => $rows) {				
				foreach($rows as $index => $row) {
					$mcRes[$table][$index] = $cached[$mcRes[$table][$index]];					 		
				}
			}
			return $this->implodeIntoResultSet($mcRes);
		}
	}
		
	
	/**
	 * 
	 * Enter description here ...
	 * @param Recordset $res
	 * @return array
	 */
	private function explodeFromResultSet(Recordset $res) {
		$output = array();
		foreach($res as $index => $row) {
			foreach($row as $key => $value) {
				$aKey = explode('.', $key);
				if(count($aKey) == 2) {
					$t = $aKey[0];
					$f = $aKey[1];
				} else {
 					$t = '_NIL_';
 					$f = $key;
				}
				
				if(!isset($output[$t])) {
					$output[$t] = array();
				}
				if(!isset($output[$t][$index])) {
					$output[$t][$index] = array();
				}
				$output[$t][$index][$f] = $value;
			}
		}
		return $output;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param array $data
	 * @return Recordset
	 */
	private function implodeIntoResultSet(array $data) {
		$tmp = array();
		foreach($data as $table => $rows) {
			foreach($rows as $index => $row) {
				foreach($row as $key => $value) {
					if(!isset($tmp[$index])) {
						$tmp[$index] = array();
					}
					$tmp[$index][$table.'.'.$key] = $value;
				}
			}
		}
		return new Recordset($tmp);
	}
		
	protected function addResultSet($key, Recordset $res, array $tables, array $ids, $cluster) {
		$r = $res->getArrayCopy();
		$output = new Recordset();		
		foreach($r as $index => $row) {
			$keyVals = array();
			$tmpVals = array();
			foreach($ids as $id) {
				foreach($tables as $table) {
					if(isset($row[$table.'.'.$id])) {
						$keyVals[] = $row[$table.'.'.$id];
						$tmpVals[$table.'.'.$id] = $row[$table.'.'.$id];
					} else {
						throw new \Exception('Expected key missing from result. Table structure/model mismatch ?');
					}
				}
			}
			$output[$index] = $tmpVals;
			$this->handle->set($this->prepareKey($keyVals, $tables, $ids, $cluster), $row); // Saves each row with proper key
		}
		$this->handle->set($key, $output->getArrayCopy()); // Stores resultset keys
		foreach($tables as $table) {
			$this->addRelatedSets($table, $key);
		}
		return $output;
	}
	
	/**
	 * Removes all related sets from recordset
	 * 
	 * @param string $tblName
	 */
	protected function clearRelatedSets($tblName) {
		$keys = $this->handle->get('keys_'.$tblName);
		if(is_array($keys)) {
			foreach($keys as $key) {
				$this->handle->delete($key);
			}
		}
		$this->handle->set('keys_'.$tblName, array());	// Reset the relation map
	}

	/**
	 * Adds a key to a related set array for table
	 * 
	 * @param string $table
	 * @param string $key
	 */
	protected function addRelatedSets($table, $key) {
		$keys = array($key); // new key
		$this->handle->add('keys_'.$table, $keys);
		if($this->handle->getResultCode() != \Memcached::RES_SUCCESS) {
			$keys = $this->handle->get('keys_'.$table, null, $cas);
			$keys[] = $key;
			$keys = array_unique($keys);
			if(!$this->handle->cas($cas,'keys_'.$table, $keys)) {
				error_log('CAS Update Failed:'.$this->handle->getResultMessage());
				/**
				 * @todo Handle CAS Update Failure - Should repeat the update
				 */
			}
		}
	}
	
	protected function getSelectKey($query) {
		return hash('md5', $query);
	}
		
	public function get($key, $table, $ids, $cluster, $deepLookup = false) {
		if($deepLookup === true && isset($this->childBackend)) {
			$res = $this->childBackend->get($key, $table, $ids, $cluster, $deepLookup);
			if($res !== false) {
				try {
					$this->mc_set_simple($key, $res, $table, $ids, $cluster);
				} catch(Exception $e) {
					//error_log($e->getMessage());
				}
			} else {	
				throw new \Exception('Item does not exist');
			}
		} else {
			$hash = $this->prepareKey($key, $table, $ids, $cluster);
			if($this->isTransaction === true && isset($this->transaction[$hash])) {
				return $this->transaction[$hash];
			}
			$res = $this->handle->get($hash);
			if($this->handle->getResultCode() != \Memcached::RES_SUCCESS) {
				if(isset($this->childBackend)) {
					$res = $this->childBackend->get($key, $table, $ids, $cluster);
					if($res == false) {
						throw new \Exception('Item does not exist');
					}
					try {
						$this->mc_set_simple($key, $res, $table, $ids, $cluster);
					} catch(Exception $e) {
						//error_log($e->getMessage());
					}
				}
			}
		}
		return $res;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::_update()
	 */
	protected function _update($key, Entity $subject, $table, array $ids, $cluster, $skipMaster = false) {
		$this->clearRelatedSets($table);
		try {
			$this->mc_set($key, $subject, $table, $ids, $cluster);
		} catch(Exception $e) {
			// error_log($e->getMessage());
		}
		return $subject;		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::_insert()
	 */
	protected function _insert($key, Entity $subject, $table, array $ids, $cluster) {
		try {
			$this->clearRelatedSets($table);
			$this->mc_set($subject->getKeys(), $subject, $table, $ids, $cluster);
		} catch(Exception $e) {
			// error_log($e->getMessage());
		}
		return $subject;		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::_delete()
	 */
	protected function _delete($key, Entity $subject, $table, $ids, $cluster) {		
		try {
			$this->clearRelatedSets($table);
			$this->mc_delete($subject->getKeys(), $subject, $table, $ids, $cluster);
		} catch(Exception $e) {
			// error_log($e->getMessage());
		}
		return $subject;
	}
	
	private function mc_delete($key, Entity $subject, $table, $ids, $cluster) {
		$hash = $this->prepareKey($key, $table, $ids, $cluster);
		if($this->isTransaction) {
			$this->d_transaction[$hash] = true;
		} else {
			$res = $this->handle->delete($hash);
			if(!$res) {
				$error = $this->handle->getResultCode();
				throw new \Exception('Failed to delete key in MC:'.$error. ' - '.$hash);
			}
		}
	}
	
	private function mc_set($key, Entity $subject, $table, $ids, $cluster) {
		$this->mc_set_simple($key, $subject->getArrayCopy(), $table, $ids, $cluster);
	}
	
	private function mc_set_simple($key, array $data, $table, $ids, $cluster) {
		$hash = $this->prepareKey($key, $table, $ids, $cluster);
		if($this->isTransaction) {
			$this->transaction[$hash] = $data;
		} else {
			$res = $this->handle->set($hash, $data);
			if(!$res) {
				$error = $this->handle->getResultCode();
				throw new \Exception('Failed to set key in MC:'.$error. ' - '.$hash.' => '.$data);
			}
		}
	}
	
	protected function prepareKey($key, $table, $ids, $cluster) {
		if(!is_array($ids)) {
			$ids = array($ids);
		}
		if(!is_array($key)) {
			$key = array($key);
		}
		return $cluster.'.'.$table.'.'.implode($this->key_concat, $ids).'.'.implode($this->key_concat, $key);
	}
	
	protected function disassembleKey($key) {
		$pieces = explode('.', $key);
		$cluster = array_shift($pieces);
		$table = array_shift($pieces);
		
		$keys = array();
		$ids = array();
		for($i = 0; $i < count($pieces); $i++) {
			if($i % 2 == 0) {
				$keys[] = $pieces[$i];
			} else {
				$ids[] = $pieces[$i];
			}
		}
		return array('cluster' => $cluster, 'table' => $table, 'keys' => $keys, 'ids' => $ids);
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::_increment()
	 * @todo Implement pushing into MC. Tricky like hell
	 */
	protected function _increment($data, $key, Entity $subject, $table, $ids, $cluster) {

	}
	
	/**
	 * Handles acctuall increment/decrement
	 * 
	 * @param array $data
	 */
	private function incr($data) {
		foreach($data as $field => $amount) {
			if($amount > 0) {
				$this->handle->increment($field, $amount);
			} else {
				$this->handle->decrement($field, abs($amount));
			}
		}
	}
}