<?php
namespace yami\Database\Sql;

class Limit extends Expression {
	
	protected $offset;
	protected $rowcount;
	
	public function __construct($rowcount = null, $offset = null) {
		if(!is_null($rowcount)) $this->setRowcount($rowcount);
		if(!is_null($offset)) $this->setOffset($offset);
	}
	
	public function setRowcount($rowcount) {
		if(!is_numeric($rowcount)) throw new \Exception('Rowcount must be numeric: '.$rowcount);
		$this->rowcount = $rowcount;
	}
	
	public function setOffset($offset) {
		if(!is_numeric($offset)) throw new \Exception('Offset must be numeric: '.$offset);
		$this->offset = $offset;
	}
	
	public function __toString() {
		return (!is_null($this->rowcount) ? ' LIMIT '.$this->rowcount.(!is_null($this->offset) ? ' OFFSET '.$this->offset : '') : '');
	}
	
	public function rowcount() {
		return $this->rowcount;
	}
	
	public function offset() {
		return $this->offset;
	}
}