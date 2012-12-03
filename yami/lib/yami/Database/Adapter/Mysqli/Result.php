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

	protected $affectedRows;


	public function __construct(\mysqli_result $result, \mysqli $handle) {
		$this->result = $result;
		$this->handle = $handle;
		$this->affectedRows = $this->handle->affected_rows;
	}

	public function fields() {
		if(!isset($this->fields)) {
			$this->fields = $this->result->fetch_fields();
			foreach($this->fields as $index => $field) {
				$this->fields[$index] = new Field($field);
			}
		}
		return $this->fields;
	}

	/**
	 * (non-PHPdoc)
	 * @see yami\Database.Result::fetchAll()
	 */
	public function fetchAll() {
		return $this->result->fetch_all($this->getFetchMode());
	}

	/**
	 *
	 * Enter description here ...
	 * @return int
	 * @throws \Exception
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
				throw new \Exception('Unsupported fetch mode');
		}
	}


	/**
	 * (non-PHPdoc)
	 * @see yami\Database.Result::fetch()
	 */
	public function fetch() {
		return $this->result->fetch_array($this->getFetchMode());
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