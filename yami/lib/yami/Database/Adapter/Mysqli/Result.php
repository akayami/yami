<?php
namespace yami\Database\Adapter\Mysqli;

use yami\Database\Result\CommonResult;

class Result extends CommonResult {
	
	/**
	 * 
	 * Enter description here ...
	 * @var mysqli_result
	 */
	public $result;
	
	/**
	 * 
	 * Enter description here ...
	 * @var mysqli
	 */
	protected $handle;
	
	protected $fields;
	
	protected $buffer;
	
	protected $current;
	
	protected $affectedRows;
	
	
	public function __construct(\mysqli_result $result, \mysqli $handle) {
		$this->result = $result;
		$this->handle = $handle;
		$this->affectedRows = $this->handle->affected_rows;
	}

	/**
	 * (non-PHPdoc)
	 * @see yami\Database.Result::fields()
	 */
	public function fields() {
		if(!isset($this->fields)) {
			$this->fields = $this->result->fetch_fields();
		}
		foreach($this->fields as $index => $field) {
			$this->fields[$index] = new Field($field);
		}
		return $this->fields;
	}
		
	/**
	 * (non-PHPdoc)
	 * @see yami\Database.Result::fetchAll()
	 */
	public function fetchAll() {
		if(!isset($this->buffer)) {
			$this->buffer = array();
			while($row = $this->result->fetch_array($this->getFetchMode())) {
				$this->buffer[] = $row;
			}
		}
		return $this->buffer;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @return int
	 * @throws Exception
	 */
	protected function getFetchMode() {
		switch($this->fetchMode) {
			case 1:
				return MYSQLI_ASSOC;
			case 2:
				return MYSQLI_NUM;
			case 3:
				return MYSQLI_BOTH;
			default:
				throw new Exception('Unsupported fetch mode'); 
		}
	}
	
	
	/**
	 * (non-PHPdoc)
	 * @see yami\Database.Result::fetch()
	 */
	public function fetch() {
		if(!isset($this->current)) {
			$this->current = $this->fetchAll();
		}
		$return = current($this->current);
		if($return == false) {
			unset($this->current);
		} else {
			next($this->current);
		}
		return $return;
	}
		
	/**
	 * (non-PHPdoc)
	 * @see yami\Database.Result::length()
	 */
	public function length() {
		return $this->result->num_rows;
	}
	
	public function affectedRows() {
		return $this->affectedRows;
	}
	
}