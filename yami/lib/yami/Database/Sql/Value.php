<?php
namespace yami\Database\Sql;
use yami\Database\Sql\Expression;

class Value extends Expression {

	protected $value;
	
	public function __construct($expr = null) {
		if(is_array($expr)) {
			if($expr['expr_type'] != 'const') {
				throw new \Exception('Illegal Expression Type: '.colref);
			}
			$this->setValue($expr['base_expr']);
		}
	}
	
	public function setValue($value) {
		$this->value = $value;
	}
	
	public function __toString() {
		return (String)$this->value;
	}
	
}