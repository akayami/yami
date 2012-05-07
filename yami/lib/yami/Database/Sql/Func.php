<?php
namespace yami\Database\Sql;
use yami\Database\Sql\Expression;

class Func extends Expression {
	
	protected $func;
	protected $sub = array();
	
	public function __construct($expr = null) {
		if(is_array($expr)) {
			if($expr['expr_type'] != 'aggregate_function') {
				throw new \Exception('Unknown Expression Type: '.colref);
			}
			$this->parse($expr);
		}
	}
	
	protected function parse(array $expr) {
		$this->setFunction($expr['base_expr']);
		if(isset($expr['alias'])) {
			$this->parseAlias($expr['alias']);
		}
		if(isset($expr['sub_tree'])) {
			$this->parseList($expr['sub_tree']);
		}
	}
	
	protected function parseList(array $list) {
		foreach($list as $item) {
			switch($item['expr_type']) {
				case 'colref':
					$this->addExpression(new Field($item));
					break;
				case 'operator':
					$this->addExpression(new Operator($item));
					break;
				case 'const':
					$this->addExpression(new Value($item));
					break;
				case 'expression':
					$this->addExpression(new Expression($item));
					break;
			}
		}
	}

	protected function addExpression(Expression $exp) {
		$this->sub[] = $exp;
	}	
	
	public function setFunction($func) {
		$this->func = $func;
	}
	
	public function __toString() {
		return $this->func.parent::__toString();
	}
	
}