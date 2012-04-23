<?php
namespace yami\Database\Adapter\PDO;

use yami\Database\Result\CommonResult;

class Result extends CommonResult {
		
	/**
	 * 
	 * Enter description here ...
	 * @var PDOStatement
	 */
	protected $result;
	
	/**
	 * 
	 * Enter description here ...
	 * @var array
	 */
	protected $fields;
	
	public function __construct(\PDOStatement $result) {
		$this->result = $result;
	}
	
	public function fields() {
		if(!isset($this->fields)) {
			$fields = array();
			for($index = 0; $index < $this->result->columnCount(); $index++) {
				$field = new Field($this->result->getColumnMeta($index));
				$fields[$field->name()] = $field;
			}
			$this->fields = $fields;
		}
		return $this->fields;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see lib/SPLN/Db/Result/SPLN_Db_Result_Interface::fetchAll()
	 */
	public function fetchAll() {
		$data = $this->result->fetchAll(\PDO::FETCH_ASSOC);
		if(is_null($columns)) {
			return $data;
		}
		foreach($data as $key => $row) {
			$data[$key] = array_intersect_key($row, array_flip($columns));
			
		}
		return $data;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see lib/SPLN/Db/Result/SPLN_Db_Result_Interface::fetch()
	 */
	public function fetch() {
		return $this->result->fetch(\PDO::FETCH_ASSOC);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see lib/SPLN/Db/Result/SPLN_Db_Result_Interface::length()
	 */
	public function length() {
		return count($this->result->fetchAll(\PDO::FETCH_ASSOC));
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function affectedRows() {
		return $this->result->rowCount();
	}
}