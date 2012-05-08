<?php
namespace yami\Database\Sql;

class ConditionField extends Field {
	
	public function __construct($field = null) {
		parent::__construct($field, null);
	}
	
	public function setAlias($aliasName) {
		throw new \Exception('Aliasing a condition field not allowed');
	}
	
}