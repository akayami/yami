<?php
namespace yami\ORM\Backend;

use yami\Database\Result\CommonResult;

use yami\ORM\Entity;

use yami\ORM\Backend;

use yami\Database\Cluster;

class Db extends Backend {

	/**
	 * 
	 * Enter description here ...
	 * @var SPLN_DB
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
	
	public function beginTransaction($cluster = 'default') {
		if(isset($this->childBackend)) {
			$this->childBackend->beginTransaction($cluster);
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
	 * @param SPLN_Db_Adapter $connection
	 * @return SPLN_Backend_Db
	 */
	public function setConnection(SPLN_Db_Adapter $connection) {
		$this->connection = $connection;
		return $this;
	}
	
	/**
	 * Unsets default cionnection
	 * @return SPLN_Backend_Db
	 */
	public function unsetConnection() {
		unset($this->connection);
		return $this;
	}
	
	/**
	 * Returns the connection
	 * @return SPLN_Db_Adapter
	 */
	public function getConnection() {
		return $this->connection;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPLN_Backend::_select()
	 */
	public function _select($query, array $tableIdMap, $cluster = 'default', $deepLookup = false) {
		$h = (isset($this->connection) ? $this->connection : $this->connection = $this->backend->slave());
		$res = $this->getRecordset($h->query($query));
		return $res;		
	}
	
	protected function _query($query, array $tables, $cluster = 'default', $deepLookup = false) {
		$h = (isset($this->connection) ? $this->connection : $this->connection = $this->backend->slave());
		$res = $this->getRecordset($h->query($query));
		return $res;
	} 
	
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
	 * @see lib/SPLN/SPLN_Backend::get()
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
			$res = $h->pquery($q, $aId)->fetch();
			return $res;	
		} catch(Exception $e) {
			throw new SPLN_Backend_Exception('noitemfound', 'Requested item was not found', null, $e);
		}
	}
	
	protected function _update($keys, Entity $subject, $table, $ids, $cluster, $skipMaster = false) {
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
			throw new SPLN_Backend_Exception('nochanges', 'Failed to update any rows');
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
	 * @see SPLN_Backend::_delete()
	 */
	protected function _delete($keys, Entity $subject, $table, $ids, $cluster) {		
		if($this->dbDelete($keys, $subject, $table, $ids, $cluster) == 0) {
			throw new SPLN_Backend_Exception('nochanges', 'Failed to update any rows');
		}
		return $subject;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param mixed $keys
	 * @param Entity $subject
	 * @param string $table
	 * @param array $ids
	 * @param string $cluster
	 */
	protected function dbDelete($keys, Entity $subject, $table, $ids, $cluster) {
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
	 */
	protected function dbUpdate($keys, array $subject, $table, $ids, $cluster, $skipMaster = false) {		
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
	 * @see SPLN_Backend::_insert()
	 */
	protected function _insert($keys, Entity $subject, $table, $ids, $cluster) {
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
		
	private function getIdentifierWhere(array $ids, SPLN_Db_Adapter $h) {
		foreach($ids as $key => $id) {
			$ids[$key] = $h->quoteIdentifier($id).'={str:'.$id.'}';
		}
		return implode(' AND ', $ids);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see SPLN_Backend::_increment()
	 */
	protected function _increment($data, $key, Entity $subject, $table, $ids, $cluster) {
		$h = $this->getMasterHandle();
		$fields = array();
		foreach($data as $field => $amount) {
			if(!isset($subject[$field])) {
				throw new Exception('Invalid Field:'.$field);
			}
			if(!is_numeric($amount)) {
				throw new Exception('Invalid Increment Amount:'.$amount);
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