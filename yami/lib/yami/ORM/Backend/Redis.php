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
	
	public function __construct(Cluster $cluster) {
		$this->backend = $cluster;
		$this->backend->get();
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
	}
	
	public function rollbackTransaction() {
		if(isset($this->childBackend)) {
			$this->childBackend->rollbackTransaction();
		}
		$this->connection->discard();
	}
	
	public function commitTransaction() {
		if(isset($this->childBackend)) {
			$this->childBackend->commitTransaction();
		}
		$this->connection->exec();	
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
	
	private function selectRefresh($query, $hash, array $tableIdMap, $cluster = 'default', $deepLookup = false) {
		$res = $this->childBackend->select($query, $tableIdMap, $cluster, $deepLookup = false);
		$explodedRes = $this->explodeFromResultSet($res);
		$sets = array();
		$keyRecordSet = array();
		$this->connection->multi();
		foreach($explodedRes as $table => $data) {
			foreach($data as $index => $row) {
				$keyVals = array();
				$key_serial = array();
				if(isset($tableIdMap[$table])) {
					foreach($tableIdMap[$table] as $id) {
						if(isset($row[$id])) {
							$keyVals[] = $row[$id];
							$key_serial[] = $table.'.'.$id.'='.$row[$id];
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
				// Updating the entity record
				$entityRedKey = $this->generateEntityKey($keyVals, $table, (isset($tableIdMap[$table]) ? $tableIdMap[$table] : null), $cluster);
				if(!($out = $this->connection->set($entityRedKey, $this->serialize($row)))) {
					throw new \Exception('Failed to set key:'.$entityRedKey);
				}
				// Updating the record set
				echo $hash.'<='.implode($this->key_concat,$key_serial);
				if(!$this->connection->rPush($hash, implode($this->key_concat,$key_serial))) {
					echo "Failed to rpush into $hash ".implode($this->key_concat,$key_serial);
				}
			}
		}
		
		if(!($out = $this->connection->exec())) {
			echo "Failed exec:".print_r($out);
		} else {
			print_r($out);
		}
		return $res;
	}
	
	public function _select($query, array $tableIdMap, $cluster = 'default', $deepLookup = false) {
		$h = (isset($this->connection) ? $this->connection : $this->connection = $this->backend->slave());
		
		if($query instanceof Select) {	
			$hash = md5($query->get(true));
			if($query->hasLimit()) {
				$limit = $query->getLimitValue();
				$offset = $query->getOffsetValue();
				$cachedRes = $h->lRange($hash, $offset, $offset + $limit);
			} else {
				echo "Getting !";
				$cachedRes = $h->get($hash);
				var_dump($cachedRes);

			}
		} else {
			throw new Exception('Handling non-objectified query not implemented yet');
		}
		if($cachedRes === false) {
			echo "not cached [{$hash}]";
			if(isset($this->childBackend)) {
				return $this->selectRefresh($query, $hash, $tableIdMap, $cluster = 'default', $deepLookup = false);
			} else {
				return null;
			}
		} else {
		
		}		
	}
	
	public function _query($query, array $tables, $cluster = 'default', $deepLookup = false) {
		
	}
	
	
	public function get($key, $table, $ids, $cluster, $deepLookup = false) {
		
	}
	
	protected function _update($key, Entity $subject, $table, array $ids, $cluster, $skipMaster = false) {
		
	}
	
	protected function _insert($key, Entity $subject, $table, array $ids, $cluster) {
		
	}
	
	protected function _delete($key, Entity $subject, $table, $ids, $cluster) {
		
	}
	
	protected function _increment($data, $key, Entity $subject, $table, $ids, $cluster) {
		
	}
	
	public function generateEntityKey($key, $table, $ids, $cluster) {
		if(!is_array($ids)) {
			$ids = array($ids);
		}
		if(!is_array($key)) {
			$key = array($key);
		}
		return $cluster.'.'.$table.'.'.implode($this->key_concat, $ids).'.'.implode($this->key_concat, $key);
	}
}