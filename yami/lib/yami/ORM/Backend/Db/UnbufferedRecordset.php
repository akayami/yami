<?php
namespace yami\ORM\Backend\Db;

use yami\Database\Result;

class UnbufferedRecordset implements \Iterator {
	
	/**
	 * 
	 * @var Result
	 */
	protected $result;
	
	protected $current;
	
	protected $position = 0;
	
	protected $fields;
	
	public function __construct(Result $result) {
		$this->result = $result;
		$this->fields = $this->result->fields();
	}
	
	/**
	 * 
	 * @return \yami\Database\Result
	 */
	public function getResult() {
		return $this->result;
	}
	
	private function _current() {
		$this->current = $this->result->fetch();
		if(is_null($this->current) === true) {
			$this->current = false;
			return;
		}
		$this->position++;
	}
	
// 	private function _current() {
// 		// echo "\n".__METHOD__."\n";
// 		$row = $this->result->fetch();
// 		if(is_null($row) === true) {
// 			$this->current = false;
// 			return;
// 		}
// 		$out = array();
//  		foreach($row as $key => $val) {
//  			$out[$this->fields[$key]->identifier()] = $val;
//  		}
//  		$this->current = $out;
// 		$this->position++;
// 	}
	
	public function rewind() {
		// echo "\n".__METHOD__."\n";
		$this->position = 0;
		$c = $this->result; // --  SUCKS
		$this->result->fetchMode($c::FETCH_ASSOC);
		$this->_current();
	}
	
	public function current() {
		// echo "\n".__METHOD__."\n";
		return $this->current;
	}
	
	public function key() {
		// echo "\n".__METHOD__."\n";
		return $this->position;
	}
	
	public function next() {
		// echo "\n".__METHOD__."\n";
		return $this->_current();
	}
	
	public function valid() {
		// echo "\n".__METHOD__."\n";
		return ($this->current === false ? false : true);
	}	
}