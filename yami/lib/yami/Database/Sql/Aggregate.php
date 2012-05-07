<?php
namespace yami\Database\Sql;
use yami\Database\Sql\Expression;

class Aggregate extends Field {
	
	public function __construct($expr = null) {
		if(is_array($expr)) {
			if($expr['type'] != 'expression') {
				throw new \Exception('Unknown Expression Type: '.$expr['type']);
			}
			$this->parse($expr);
		}
	}
	
	public function parse($expr) {
		$this->parseFieldName($expr['base_expr']);
	}
	
}