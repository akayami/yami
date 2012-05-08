<?php
namespace yami\Database\Sql;
use yami\Database\Sql\Expression;

class Operator extends Expression {
	
	protected $operator;
	
	public function __construct($operator = null) {
		$this->setOperator($operator);
	}
	
	public function parseStructure(array $expr) {
		if(is_array($expr)) {
			if($expr['expr_type'] != 'operator') {
				throw new \Exception('Illegal Expression Type: '.colref);
			}
			$this->setOperator($expr['base_expr']);
		}
	}
		
	public function setOperator($expr) {
		$this->operator = trim($expr);
	}
	
	public function __toString() {
		return ' '.$this->operator.' ';
	}	
}