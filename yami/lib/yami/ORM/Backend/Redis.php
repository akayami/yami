<?php
namespace yami\ORM\Backend;

use yami\Database\Sql\Operator;

use yami\Database\Sql\ConditionField;

use yami\Database\Sql\Condition;

use yami\Database\Sql\ConditionBlock;

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
				$out[] = $id;
			}
		}
		return $out;
	}

	// 	private function parseKey($key) {
	// 		$keys = explode($this->key_concat, $key);
	// 		$output = array();
	// 		foreach($keys as $k) {
	// 			$d = explode('.', $k);
	// 			$val = array_pop($d);
	// 			$key = array_pop($d);
	// 			$output[$key] = $val;// = array($key => $val);
	// 		}
	// 		return $output;
	// 	}

	private function getEntityKey($cluster, array $tables, array $keys, array $row) {
		$output = array();
		$output[] = $cluster;
		$output = array_merge($output, $tables);
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
		return $this->getEntityKey($cluster, array($table), $keys, $row);
	}

	/**
	 * Cacheing routine that looks ahead 5 pages to recache values
	 *
	 * @param Select $query
	 * @param string $hash
	 * @param array $tableIdMap
	 * @param string $cluster
	 * @param boolean $deepLookup
	 */
	private function selectRefresh(Select $query, $hash, array $ids, $count = false, $cluster = 'default', $deepLookup = false) {
		//print_r($ids);
		$pagesAheadMultiplier = 3;
		$flippedIds = array_flip($ids);
		//$t = $this->flatternTableIdMap($tableIdMap);
		$c = clone $query;
		if($query->hasLimit()) {
			$end = (int)$query->getOffsetValue() + (int)$query->getLimitValue();
			$start = (int)$query->getOffsetValue();
			$c->setLimit((int)$query->getLimitValue() * $pagesAheadMultiplier,  (int)$query->getOffsetValue());
			$index = $query->getOffsetValue();
		} else {
			$start = 0;
			$end = -1;
			$index = 0;
		}
		$res = new Recordset();
		if($this->childBackend->unbufferedSupported()) {
			//error_log('Unbuffered');
			$childRes = $this->childBackend->unbufferedSelect($c, $ids, $cluster, $deepLookup);
		} else {
			//error_log('Buffered');
			$childRes = $this->childBackend->Select($c, $ids, false, $cluster, $deepLookup);
		}
		//print_r($childRes);exit;
		$count = 0;
		//error_log("Start Index:".$index);
		$serialIndex = 0;
		try {
			$this->connection->multi();
			foreach ($childRes as $i => $row) {
				$key = $this->getEntityKey($cluster, $query->getTableNamesList(),  $ids, $row);
				//error_log("Rds Key:".$key);
				/**
				* Add Every Result to Redis list
				*/
				foreach($row as $field => $value) {
					$this->connection->hSet($key, $field, $value);
				}
				$this->connection->zAdd($hash, $index, $key);

				/**
				 * Add results within limtis to return resultset
				 */
				if(($index >= $start && $index < $end) || $end == -1) {
					$res[$serialIndex] = $row;
					$res->idRecordMap[implode('.', array_intersect_key($row, $flippedIds))] = $serialIndex;
					$serialIndex++;
				}
				if(($index % 10000) === 0) {
					set_time_limit(300);
					//error_log('Saved chunk to redis');
					$this->connection->exec();
				}
				$index++;
				$count++;
			}
			if($query->hasLimit() == true && ((int)$count < (int)$query->getLimitValue())) {
				$this->connection->set($hash.'_count', ($query->getOffsetValue() + $count));
			} elseif($query->hasLimit() === false) {
				$this->connection->set($hash.'_count', count($count));
			}
			$this->connection->exec();
			foreach($query->getTableNamesList() as $table) {
				$this->addRelatedSets($cluster, $table, $hash);
				$this->addRelatedSets($cluster, $table, $hash.'_count');
			}
		} catch(\RedisException $e) {
			//error_log('Redis Failure:'.$e->getMessage());
			$index = 0;
			foreach ($childRes as $key => $row) {
				if(($index >= $start && $index < $end) || $end == -1) {
					$res[] = $row;
				}
				$index++;
			}
		}
		return $res;

	}

	public function _select(Select $query, array $ids, $count = false, $cluster = 'default', $deepLookup = false) {
		$hash = $query->hash(true);
		$flippedIds = array_flip($ids);
		$totalCount = null;
		$this->connection = $this->backend->master(true);
		try {

			if($this->connection->exists($hash) && false) {
				if($this->connection->exists($hash.'_count')) {
					$totalCount = $this->connection->get($hash.'_count');
				}
				$recordSet = new Recordset(array(), $totalCount);
				if($query->hasLimit()) {
					$limit = $query->getLimitValue();
					$offset = $query->getOffsetValue();
					$cachedRes = $this->connection->zRangeByScore($hash, $offset, $offset + $limit - 1);
					if(count($cachedRes) != $limit) {
						$countVal = $this->connection->get($hash.'_count');
						if(!(is_numeric($countVal) && ($offset + $limit) >= $countVal)) {
							// 			 				error_log(($offset + $limit).'!='.$countVal);
							// 							error_log('Result Size Discrepency - Need to look below to see if there are more results:'.count($cachedRes).' - '.$limit);
							$cachedRes = false;
						}
						// 			 			else {
						// 			 				error_log('Result is the end of list');
						// 			 			}
					}
				} else {
					//error_log('No LIMIT. Getting All:'.$query);
					$cachedRes = $this->connection->zRange($hash, 0, -1);
				}
				if($cachedRes !== false) {
					$missing = array();
					foreach($cachedRes as $i => $key) {
						if($this->connection->exists($key)) {
							$recordSet[$i] = $this->connection->hGetAll($key);
							$recordSet->idRecordMap[implode('.', array_intersect_key($recordSet[$i], $flippedIds))] = $i;
						} else {
							$missing[$i] = $key;
						}
					}
					if(count($missing) > 0) {		# Logic to handle missing records, and put them back into redis
						$n = clone $query;
						$n->unsetGroup()->unsetHaving()->unsetLimit()->unsetOrder()->unsetWhere();
						$n->getWhere()->setLogicalOperator('OR');
						foreach($missing as $key) {
							$parsedKeys = $this->parseKey($key);
							$cb = new ConditionBlock();
							foreach($parsedKeys as $col => $val) {
								$cb->add(new Condition(new ConditionField($col), new Operator('='), $val));
							}
							$n->addCondition($cb);
						}
						$data = $this->childBackend->query($n, $ids, false, $cluster, true);
						foreach($missing as $resPos => $redisKey) {
							$orgKey = $redisKey;
							$redisKey = explode('.', $redisKey);
							$id = array_pop($redisKey);
							$redisKey = implode('.', $redisKey);
							$this->connection->multi();
							foreach($data as $record) {
								if($record[$redisKey] == $id) {

									$recordSet[$resPos] = $record;
									$recordSet->idRecordMap[implode('.', array_intersect_key($recordSet[$resPos], $flippedIds))] = $resPos;

										
									//$cachedRes[$resPos] = $record;
									foreach($record as $field => $value) {
										$this->connection->hSet($orgKey, $field, $value);
									}
									//error_log('Added missing records to Redis:'.$orgKey);
								}
							}
							$this->connection->exec();
						}
					}
					return $recordSet;
					//print_r($cachedRes);
					//return new Recordset($cachedRes, $totalCount);	// Found in cache and successfully retrived
				}
			}
			// 			else {
			// 				error_log('Not found in redis');
			// 			}
		} catch(\RedisException $e) {
			error_log('Redis Failed:'.$e->getMessage());
		} catch(\Exception $e) {
			error_log('Regular Exception Failed:'.$e->getMessage());
		}
		if(isset($this->childBackend)) {
			$val = $this->selectRefresh($query, $hash, $ids, $count, $cluster, $deepLookup);
			return $val;
		} else {
			return null;
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
				throw new \Exception('Failed to add '.$hash.' to redis');
			}
			foreach($tables as $table) {
				$this->addRelatedSets($cluster, $table, $hash);
			}
			return $res;
		} else {
			return new Recordset();
		}
	}

	protected function assembleRelatedKey($cluster, $table) {
		return 'keyHashList.'.$cluster.$this->key_concat.$table;
	}

	public function addRelatedSets($cluster, $table, $hash) {
		$this->connection = $this->backend->master(true);
		$this->connection->sAdd($this->assembleRelatedKey($cluster, $table), $hash);
	}

	public function clearRelatedSets($cluster, $table) {
		$this->connection = $this->backend->master(true);
		foreach($this->connection->sGetMembers($this->assembleRelatedKey($cluster, $table)) as $key) {
			$this->connection->delete($key);
		}
		$this->connection->delete($this->assembleRelatedKey($cluster, $table));
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
		$this->clearRelatedSets($cluster, $table);
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
		$this->clearRelatedSets($cluster, $table);
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
			$this->clearRelatedSets($cluster, $table);
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