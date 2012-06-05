<?php
namespace yami\ORM\Backend;

use yami\ORM\Select;

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
	
	public function beginTransaction() {
				
		if(isset($this->childBackend)) {
			$this->childBackend->beginTransaction();
		}
		$this->isTransaction = true;
		
	}
	
	public function commitTransaction() {

		if(isset($this->childBackend)) {
			$this->childBackend->commitTransaction();
		}
		if(!is_null($this->transaction)) {
			if(!$this->handle->setMulti($this->transaction)) {
				if($this->handle->getResultCode() != 26) { // Happens when MC is completly down.
					throw new \Exception('Failed to commit transaction to MC:'.$this->handle->getResultMessage(). '['.$this->handle->getResultCode().']', $this->handle->getResultCode());
				}
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
				$res = $this->childBackend->query($query, $tables, $cluster, $deepLookup);
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
	
	private function selectRefresh(Select $query, $hash, array $ids, $count = false, $count_hash = null, $cluster = 'default', $deepLookup = false) {
		$tables = $query->getTableNamesList();
		$table = $tables[0];
		$keyRecordSet = array();
		$res = $this->childBackend->select($query, $ids, $count, $cluster, $deepLookup = false); // Get Full Resultset from DB
		$ids = array_flip($ids);		
		if($res) {
			$sets = array();						
			foreach($res as $key => $row) {
				$keyVals = array_intersect_key($row, $ids);
				$rowKey = $this->prepareKey($keyVals, $table, $ids, $cluster);
				//error_log($rowKey);				
				$sets[$rowKey] = $row;
				//$res[$key] = $rowKey;
				$keyRecordSet[$key] = $rowKey; 
			}
			$data = array_merge(array($hash => $keyRecordSet), $sets);
			if($count !== false) {
				$data[$count_hash] = $res->totalCount;
			}
			if(!$this->handle->setMulti($data)) {
				error_log('MC Failed:'.$this->handle->getResultMessage());
			}
			foreach(array_keys($data) as $key) {
				$this->addRelatedSets($table, $key);
			}
		}
		return $res;
	}
		
	protected function _select(Select $query, array $ids, $count = false, $cluster = 'default', $deepLookup = false) {
		$hash = $this->getSelectKey($query);
		$hash_c = $query->hash(true, true).'_count';
		$multiRes = $this->handle->getMulti(array($hash, $hash_c));
		if($this->handle->getResultCode() != \Memcached::RES_SUCCESS || !isset($multiRes[$hash]) || ($count !== false ? !isset($multiRes[$hash_c]) : false)) {
			if(isset($this->childBackend)) {
				return $this->selectRefresh($query, $hash, $ids, $count, $hash_c, $cluster, $deepLookup);
			} else {
				return null;
			}
		} else {
			//error_log('Full MC:'.$count);
			
			$mcRes = (isset($multiRes[$hash]) ? $multiRes[$hash] : array());
			$cachedCount = (isset($multiRes[$hash_c]) ? $multiRes[$hash_c] : null);
			$cached = $this->handle->getMulti($mcRes);
			$missing = array_keys(array_diff_key(array_flip($mcRes), (is_array($cached) ? $cached : array())));			
			if($missing || ($count !== false && is_null($cachedCount))) {
				error_log('Some Missing - Recaching');
				/**
				 * @todo Add a better way of cacheing missing items. Right now, one missing item triggers the whole query
				 */
				return $this->selectRefresh($query, $hash, $ids, (is_null($cachedCount) ? 1 : false), $hash_c, $cluster, $deepLookup);
				//return $this->_select($query, $ids, $cluster, true);
			}
			$res = new Recordset(array(), $cachedCount);
 			foreach($mcRes as $key => $p) {
 				$res[$key] = $cached[$p];
 				$res->idRecordMap[implode('.', array_intersect_key($cached[$p], array_flip($ids)))] = $key;
 			}			
			return $res;
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
		if(is_object($query)) {
			return $query->hash();
		} else {
			return hash('md5', $query);
		}
	}
		
	public function get($key, $table, $ids, $cluster, $deepLookup = false) {
		if($deepLookup === true && isset($this->childBackend)) {
			$res = $this->childBackend->get($key, $table, $ids, $cluster, $deepLookup);
			if($res !== false) {
				try {
					$this->mc_set_simple($key, $res, $table, $ids, $cluster);
				} catch(\Exception $e) {
					error_log($e->getMessage());
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
					} catch(\Exception $e) {
						error_log($e->getMessage());
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
		} catch(\Exception $e) {
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
		} catch(\Exception $e) {
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
		} catch(\Exception $e) {
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
	
	protected function prepareKey($key, $table, $ids, $cluster = 'default') {
// 		if(!is_array($ids)) {
// 			$ids = array($ids);
// 		}
		if(!is_array($key)) {
			$key = array($key);
		}
		return $cluster.'.'.$table.'.'.implode($this->key_concat, array_flip($ids)).'.'.implode($this->key_concat, $key);
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