<?php
namespace yami\Database\Sql;

class Order extends Field {
	
	
	protected $direction = 'ASC';
	
	public function __construct($expr = null) {
		if(is_array($expr)) {
			if(!in_array($expr['type'], array('expression', 'alias'))) {
//			if($expr['type'] != 'expression' || $expr['type'] != 'alias') {
				throw new \Exception('Unknown Expression Type: '.$expr['type']);
			}
			$this->parse($expr);
		}
	}
	
	protected function parse($expr) {
		$this->parseFieldName($expr['base_expr']);
		$this->setDirection($expr['direction']);
	}
	
	public function setDirection($direction = 'ASC') {
		if($this->direction == 'ASC') {
			$this->direction = 'ASC';
		} else {
			$this->direction = 'DESC';
		}
	}
	
	public function __toString() {
		return parent::__toString().' '.$this->direction;
	}
}