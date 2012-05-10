<?php
namespace yami\ORM\Backend;

use yami\ORM\Backend\Db\UnbufferedRecordset;

use yami\ORM\Select;

use yami\Database\Adapter;

use yami\Database\Result\CommonResult;

use yami\ORM\Entity;

use yami\ORM\Backend;

use yami\Database\Cluster;

class Db extends Backend {

	/**
	 * 
	 * Enter description here ...
	 * @var Cluster
	 */
	private $backend;
	private $tableName;
	private $ids;
	private $connection;
	
	public function __construct(Cluster $db) {
		$this->backend = $db;
	}
	
	protected function getMasterHandle($new = false) {
		return (isset($this->connection) ? ($this->connection->isMaster() ? $this->connection : $this->connection = $this->backend->master($new)) : $this->connection = $this->backend->master($new));
	}
	
	public function beginTransaction() {
		if(isset($this->childBackend)) {
			$this->childBackend->beginTransaction();
		}
		$h = $this->getMasterHandle(true);
		$h->transaction();
	}
	
	public function commitTransaction() {
		if(isset($this->childBackend)) {
			$this->childBackend->commitTransaction();
		}
		$this->connection->commit();		
	}
	
	public function rollbackTransaction() {
		if(isset($this->childBackend)) {
			$this->childBackend->rollbackTransaction();
		}
		$this->connection->rollback();
	}
	
	/**
	 * Allows to overwrite default connection handling
	 * 
	 * @param Adapter $connection
	 * @return Db
	 */
	public function setConnection(Adapter $connection) {
		$this->connection = $connection;
		return $this;
	}
	
	/**
	 * Unsets default cionnection
	 * @return Db
	 */
	public function unsetConnection() {
		unset($this->connection);
		return $this;
	}
	
	/**
	 * Returns the connection
	 * @return Adapter
	 */
	public function getConnection() {
		return $this->connection;
	}
	
	/**
	 * Returns a buffered recordset (you can iterate multiple times, but takes a lot of memory)
	 *
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::_select()
	 */
	public function _select($query, array $tableIdMap, $cluster = 'default', $deepLookup = false) {		
		return $this->getRecordset($this->basicSelect($query, $tableIdMap, $cluster, $deepLookup));			
	}
	
	/**
	 * Returns a buffered recordset (you can iterate multiple times, but takes a lot of memory)
	 * 
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::_query()
	 */
	protected function _query($query, array $tables, $cluster = 'default', $deepLookup = false) {
		return $this->getRecordset($this->basicQuery($query, $tables, $cluster, $deepLookup));
	} 
	
	private function basicQuery($query, array $tables, $cluster = 'default', $deepLookup = false) {
		$h = (isset($this->connection) ? $this->connection : $this->connection = $this->backene());
		return $h->query($query);
	}
	
	private function basicSelect($query, array $tableIdMap, $cluster = 'default', $deepLookup = false) {
		$h = (isset($this->connection) ? $this->connection : $this->connection = $this->backend->slave());
		if($query instanceof Select) {
			$query->setDbAdapter($h);
			if($query->hasPlaceholders()) {
				return $h->pquery($query, $query->getPlaceholders());
			}
		}
		return $h->query($query);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::unbufferedSupported()
	 */
	public function unbufferedSupported() {
		return true;
	}
	
	/**
	 * Returns an unbuffered resultset. You can iterate ONCE, but only uses up as much memory as one record would
	 * Mostly useful for internal usage, caching-ahead and such.
	 * 
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::unbufferedQuery()
	 */
	public function unbufferedQuery($query, array $tables, $cluster = 'default', $deepLookup = false) {		
		return new UnbufferedRecordset($this->basicQuery($query, $tables, $cluster, $deepLookup));
	}
	
	/**
	 * Returns an unbuffered resultset. You can iterate ONCE, but only uses up as much memory as one record would
	 * Mostly useful for internal usage, caching-ahead and such.
	 * 
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::unbufferedSelect()
	 */
	public function unbufferedSelect($query, array $tableIdMap, $cluster = 'default', $deepLookup = false) {
		return new UnbufferedRecordset($this->basicSelect($query, $tableIdMap, $cluster, $deepLookup));		
	}
	
	/**
	 * 
	 * @param CommonResult $result
	 * @return Recordset
	 */
	public function getRecordset(CommonResult $result) {
		$output = new Recordset();
		$fields = $result->fields();
		
		$result->fetchMode($result::FETCH_NUM);
		while($row = $result->fetch()) {
			$tmp = array();
			foreach($row as $key => $val) {
				$tmp[$fields[$key]->identifier()] = $val;
			}
			$output[] = $tmp;
		}
		return $output;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::get()
	 */
	public function get($id, $table, $ids, $cluster, $deepLookup = false) {
		if($deepLookup === true && isset($this->childBackend)) {
			$res = $this->childBackend->get($id, $table, $ids, $cluster, $deepLookup);
			try {
				if($this->_update($id, $res, $table, $ids, $cluster) > 0) {
					$reload = true;
				} else {
					$reload = false;
				}
			} catch(Exception $e) {
				$id = $this->insert($id, $subject, $table, $ids, $cluster, $deepLookup);
				$reload = true;
			}
			if($reload === false) {
				return $res;
			}
		}
		if(!is_array($id))  {
			$aId[$ids[0]] = $id;
		} else {
			$aId = $id;
		}		
		$h = (isset($this->connection) ? $this->connection : $this->connection = $this->backend->slave());
		$q = 'SELECT * FROM '.$table.' WHERE '.$this->getIdentifierWhere($ids, $h);
		try {			
			$res = $h->pquery($q, $aId);
			$res->fetchMode($res::FETCH_NUM);
			$fields = $res->fields();
			if($row = $res->fetch()) {				
				$out = array();
				foreach($row as $key => $val) {
					$out[$fields[$key]->identifier()] = $val;
				}
				return $out;
			} else {
				throw new Exception('Unable to return');
			}
		} catch(Exception $e) {
			throw new Exception('noitemfound', 'Requested item was not found', null, $e);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::_update()
	 */
	protected function _update($keys, Entity $subject, $table, array $ids, $cluster, $skipMaster = false) {
		/*
		 * Filter out fields that should not be updated, such as timestamps with default values to be set by DB
		 */
		$data = array();
		foreach($subject->getStructure() as $field) {
			if($field['Type'] == 'timestamp' && $field['Default'] == 'CURRENT_TIMESTAMP') {
				// Skipping timestamps with default values as they're to be updated by the db
			} elseif(in_array($field['Field'], $subject->counterFields)) {
				// Skipping counter fields
			} else {
				$data[$field['Field']] = $subject[$field['Field']];
			}
		}
		
		$affected = $this->dbUpdate($keys, $data, $table, $ids, $cluster, $skipMaster);
				
		if($affected == 0) {
			throw new Exception('nochanges', 'Failed to update any rows');
		}
		
		/*
		 * Reselect values from db, to make sure the returned object reflects exactly what's in DB.
		 * Mysql has a tendency to magically convert 1234asfdsa into 1234 in case of an int field.
		 * It's also helpful if db has timestamps etc. 
		 */
		$subject = $subject::make($this->get($keys, $table, $ids, $cluster, false));			
		return $subject;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::_delete()
	 */
	protected function _delete($keys, Entity $subject, $table, $ids, $cluster) {		
		if($this->dbDelete($keys, $subject, $table, $ids, $cluster) == 0) {
			throw new Exception('nochanges', 'Failed to update any rows');
		}
		return $subject;
	}
	
	/**
	 * 
	 * @param mixed $keys
	 * @param Entity $subject
	 * @param string $table
	 * @param array $ids
	 * @param string $cluster
	 */
	protected function dbDelete($keys, Entity $subject, $table, array $ids, $cluster) {
		$h = $this->getMasterHandle();
		$query = "DELETE FROM ".$h->quoteIdentifier($table).' WHERE '.$this->getIdentifierWhere($ids, $h);
		$res = $h->pquery($query, $keys);
		return $h->affectedRows();
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param mixed $keys
	 * @param array $subject
	 * @param string $table
	 * @param array $ids
	 * @param string $cluster
	 * @param boolean $skipMaster
	 * @return int
	 */
	protected function dbUpdate($keys, array $subject, $table, array $ids, $cluster, $skipMaster = false) {		
		$h = $this->getMasterHandle();		
		$fields = array();
		foreach($subject as $field => $value) {
			$fields[] = $h->quoteIdentifier($field).'='.(is_null($value) ? 'NULL' : $h->quote($value));
		}
		
		$query = "UPDATE ".$h->quoteIdentifier($table)." SET ".implode(',', $fields).' WHERE '.$this->getIdentifierWhere($ids, $h);
		$res = $h->pquery($query, $keys);		
		return $h->affectedRows();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::_insert()
	 */
	protected function _insert($keys, Entity $subject, $table, array $ids, $cluster) {
		$h = $this->getMasterHandle();
		foreach($subject->getStructure() as $field) {
			if($field['Type'] == 'timestamp' && $field['Default'] == 'CURRENT_TIMESTAMP') {
				// Skipping timestamps with default values as they're to be updated by the db
			} elseif($field['Extra'] == 'auto_increment') {
				// Skipping Autoincrement fields
			} else {
				$data[$field['Field']] = $subject[$field['Field']];
			}
		}
				
		$fields = array_keys($data);
		array_walk($fields, function(&$value, $key, $handle) {
			$value = $handle->quoteIdentifier($value);	
		}, $h);
		array_walk($data, function(&$value, $key, $handle) {
			if(!is_null($value)) {
				$value = $handle->quote($value);
			} else {
				$value = 'NULL';
			}
		}, $h);
		$query = "INSERT INTO ".$h->quoteIdentifier($table).' ('.implode(',', $fields).') VALUES ('.implode(',',$data).')';
		$h->query($query);
		$insertID = $h->getLastInsertID();
		if($insertID > 0) {
			$subject[$subject->getAutoIncrement()] = $insertID;
		}
		$idVals = array();
		foreach($ids as $id) {
			$idVals[$id] = $subject[$id];
		}
//		error_log(print_r($idVals, true));
//		error_log(print_r($ids, true));
		$subject = $subject::make($this->get($idVals, $table, $ids, $cluster));
		return $subject;
	}
		
	private function getIdentifierWhere(array $ids, Adapter $h) {
		foreach($ids as $key => $id) {
			$ids[$key] = $h->quoteIdentifier($id).'={str:'.$id.'}';
		}
		return implode(' AND ', $ids);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see yami\ORM.Backend::_increment()
	 */
	protected function _increment($data, $key, Entity $subject, $table, $ids, $cluster) {
		$h = $this->getMasterHandle();
		$fields = array();
		foreach($data as $field => $amount) {
			if(!isset($subject[$field])) {
				throw new \Exception('Invalid Field:'.$field);
			}
			if(!is_numeric($amount)) {
				throw new \Exception('Invalid Increment Amount:'.$amount);
			}
			if($amount < 0) {
				$sign = '-';
			} else {
				$sign = '+';
			}
			$fields[] = $h->quoteIdentifier($field).'='.$h->quoteIdentifier($field).$sign.abs($amount);
		}
		$query = "UPDATE ".$h->quoteIdentifier($table)." SET ".implode(',', $fields).' WHERE '.$this->getIdentifierWhere($ids, $h);
		$res = $h->pquery($query, $key);
		
		$subject = $subject::make($this->get($key, $table, $ids, $cluster, false));			
		return $subject;				
	}
}