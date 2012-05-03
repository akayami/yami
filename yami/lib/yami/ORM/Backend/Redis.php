<?php
namespace yami\ORM\Backend;

use yami\ORM\Entity;

use yami\Redis\Cluster;

use yami\ORM\Select;

use yami\ORM\Backend;

class Redis extends Backend {
	
	/**
	 * 
	 * @var Cluster
	 */
	private $backend;
	
	/**
	 * 
	 * @var \Redis
	 */
	private $connection;
	
	protected $key_concat = '|';
	protected $isTransaction = false;
	protected $d_transaction = null;
	protected $transaction = null;
	
	
	public function __construct(Cluster $cluster) {
		$this->backend = $cluster;
//		$this->backend->master()->flushDB();
//		echo "Flushing";
	}
	
	private function getMasterHandle($new = false) {
		return (isset($this->connection) ? ($this->connection->isMaster() ? $this->connection : $this->connection = $this->backend->master($new)) : $this->connection = $this->backend->master($new));
	}
	
	public function beginTransaction() {
		if(isset($this->childBackend)) {
			$this->childBackend->beginTransaction();
		}
		$this->connection = $this->backend->master(true);
		$this->connection->multi();
		$this->isTransaction = true;
	}
	
	public function rollbackTransaction() {
		if(isset($this->childBackend)) {
			$this->childBackend->rollbackTransaction();
		}
		$this->connection->discard();
		$this->isTransaction = false;
		unset($this->transaction_increment);
		unset($this->transaction);
	}
	
	public function commitTransaction() {
		if(isset($this->childBackend)) {
			$this->childBackend->commitTransaction();
		}
		$this->connection->exec();	
		$this->isTransaction = false;
	}
	
	/**
	 * 
	 * @param \Redis $connection
	 * @return Redis
	 */
	public function setConnection(\Redis $connection) {
		$this->connection = $connection;
		return $this;
	}
	
	/**
	 * @return Redis
	 */
	public function unsetConnection() {
		unset($this->connection);
		return $this;
	}
	
	/**
	 * 
	 * @return \Redis
	 */
	public function getConnection() {
		return $this->connection;
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
	
	private function serialize($mixed) {
		if(!is_string($mixed)) {
			$mixed == serialize($mixed);
		}
		return $mixed;
	}
	
	private function unserialize($string) {
		if(strtolower($string) === 'false') {
			return false;
		} else {
			if(($output = @unserialize($str)) === false) {
				return $str;
			} else {
				return $output;
			}
		}
	}
	
	protected function getSelectKey($query) {
		return hash('md5', $query);
	}
	
	private function flatternTableIdMap(array $tableIdMap) {
		$out = array();
		foreach($tableIdMap as $key => $val) {
			foreach($val as $id) {
				$out[] = $key.'.'.$id;
			}
		}
		return $out;
	}
	
	
	
	private function getEntityKey($cluster, array $keys, array $row) {
		$output = array();
		foreach(array_intersect_key($row, array_flip($keys)) as $key => $value) {
			$output[] = $key.'.'.$value;
		}
		return implode($this->key_concat, $output);
	}
	
	protected function prepareKey($key, $table, $ids, $cluster) {
		if(!is_array($ids)) {
			$ids = array($ids);
		}
		if(!is_array($key)) {
			$key = array($key);
		}		
		$keys = array();
		$row = array();
		for($i = 0; $i < count($key); $i++) {
			$row[$table.'.'.$ids[$i]] = (isset($key[$ids[$i]]) ? $key[$ids[$i]] : $key[$i]);
			$keys[] = $table.'.'.$ids[$i];
		}
		return $this->getEntityKey($cluster, $keys, $row);		
	}
	
	
	
	private function selectRefresh($query, $hash, array $tableIdMap, $cluster = 'default', $deepLookup = false) {
		$h = $this->backend->master(); // Better master/slave switcher
		$res = $this->childBackend->select($query, $tableIdMap, $cluster, $deepLookup = false);	
		try {
			$this->connection->multi();
			$t = $this->flatternTableIdMap($tableIdMap);
			foreach($res as $index => $row) {		
				$key = $this->getEntityKey($cluster, $t, $row);
				foreach($row as $field => $value) {					
					$this->connection->hSet($key, $field, $value);					
				}
				
				$this->connection->zAdd($hash, $index, $key);
			}
			if($out = $this->connection->exec() === false) {
				throw new \Exception('Failed to safe into Redis.:'.print_r($out, true));
			}
			foreach($tableIdMap as $table => $crap) {
				$this->addRelatedSets($table, $hash);
			}
		} catch(\Exception $e) {
			$this->connection->discard();
			throw $e;
		}		
		return $res;
	}
	
	public function _select($query, array $tableIdMap, $cluster = 'default', $deepLookup = false) {
		$this->connection = $this->backend->master(true);
		//$this->connection = (isset($this->connection) ? $this->connection : $this->backend->slave());
		if($query instanceof Select) {	
			$hash = md5($query->get(true));			
			if($this->connection->exists($hash)) {
				if($query->hasLimit()) {
					$limit = $query->getLimitValue();
					$offset = $query->getOffsetValue();
					$cachedRes = $this->connection->zRange($hash, $offset, $offset + $limit);
				} else {
					$cachedRes = $this->connection->zRange($hash, 0, -1);	
				}
				if($cachedRes !== false) {
					foreach($cachedRes as $i => $keys) {
						$cachedRes[$i] = $this->connection->hGetAll($keys);
					}
					return new Recordset($cachedRes);	// Found in cache and successfully retrived 
				}
			} else {
				$cachedRes = $this->connection->zRange($hash, 0, -1);
			}
			if(isset($this->childBackend)) {
				$val = $this->selectRefresh($query, $hash, $tableIdMap, $cluster = 'default', $deepLookup = false);
				return $val;
			} else {
				return null;
			}				
		} else {
			throw new Exception('Handling non-objectified query not implemented yet');
		}
	}
	
	
	public function _query($query, array $tables, $cluster = 'default', $deepLookup = false) {
		$this->connection = $this->backend->master(true);
		$hash = $this->getSelectKey($query);
		if($this->connection->exists($hash)) {
			$data = @unserialize($this->connection->get($hash));
			if(is_array($data)) {
				return new Recordset($data);
			}
		}
		if(isset($this->childBackend)) {
			$res = $this->childBackend->query($query, $tables, $cluster, $deepLookup);
			$data = serialize($res->getArrayCopy());
			$this->connection = $this->backend->master(true);
			if(($setResult = $this->connection->set($hash, $data)) !== true) {
				//error_log('Failed to add '.$hash.' to redis');
				var_dump($setResult);
				//throw new \Exception('Failed to add '.$hash.' to redis');
				throw new \Exception('Failed to add '.$hash.' to redis');
			}
			foreach($tables as $table) {
				$this->addRelatedSets($table, $hash);
			}
			return $res;
		} else {
			return new Recordset();
		}
	}
	
	public function addRelatedSets($table, $hash) {
		$this->connection = $this->backend->master(true);
		$this->connection->sAdd('keyHashList.'.$table, $hash);
	}
	
	public function clearRelatedSets($table) {
		$this->connection = $this->backend->master(true);
		foreach($this->connection->sGetMembers('keyHashList.'.$table) as $key) {
			$this->connection->delete($key);
		}
		$this->connection->delete('keyHashList.'.$table);
	}
		
	public function get($key, $table, $ids, $cluster, $deepLookup = false) {
		$this->connection = $this->backend->slave();
		$hash = $this->prepareKey($key, $table, $ids, $cluster);
		if($deepLookup === true && isset($this->childBackend)) {
			$this->connection = $this->backend->master();
			$res = $this->childBackend->get($key, $table, $ids, $cluster, $deepLookup);
			if($res !== false) {
				$this->connection->multi();
				foreach($res as $field => $value) {
					$this->connection->hSet($hash, $field, $value);
				}
				if(!$this->connection->exec()) {
					throw new \Exception('Failed to push into Redis');
				}
			} else {
				throw new \Exception('Item does not exist');
			}
		} else {			
			if(!$this->connection->exists($hash) || ($res = $this->connection->hGetAll($hash)) === false) {
				$this->connection = $this->backend->master();
				$res = $this->childBackend->get($key, $table, $ids, $cluster);				
				if($res === false) {
					throw new \Exception('Item does not exist');
				}
				$this->connection->multi();

				foreach($res as $field => $value) {
					$this->connection->hSet($hash, $field, $value);
				}
				if(!$this->connection->exec()) {
					throw new \Exception('Failed to push into Redis');
				}
			}
		}
		return $res;
			
	}
	
	protected function _update($key, Entity $subject, $table, array $ids, $cluster, $skipMaster = false) {
		$this->connection = $this->backend->master();
		$this->clearRelatedSets($table);
		$data = $subject->getArrayCopy();
		$hash = $this->prepareKey($key, $table, $ids, $cluster);
		
		$this->connection->multi();
		foreach($data as $field => $value) {
			$this->connection->hSet($hash, $field, $value);
		}
		if(!$this->connection->exec()) {
			throw new \Exception('Failed to push into Redis');
		}
		return $subject;
	}
	
	protected function _insert($key, Entity $subject, $table, array $ids, $cluster) {
		$this->connection = $this->backend->master();
		$this->clearRelatedSets($table);
		$data = $subject->getArrayCopy();
		$hash = $this->prepareKey($key, $table, $ids, $cluster);
		
		$this->connection->multi();
		foreach($data as $field => $value) {
			$this->connection->hSet($hash, $field, $value);
		}
		if(!$this->connection->exec()) {
			throw new \Exception('Failed to push into Redis');
		}
		return $subject;
	}
	
	protected function _delete($key, Entity $subject, $table, $ids, $cluster) {
		$hash = $this->prepareKey($key, $table, $ids, $cluster);
		$this->connection = $this->backend->master();
		try {
			$this->clearRelatedSets($table);
			$this->connection->delete($hash);
		} catch(\Exception $e) {
			
		}
		return $subject;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::_increment()
	 */
	protected function _increment($data, $key, Entity $subject, $table, $ids, $cluster) {
		
	}
}