<?php
namespace yami\Database\Sql;
use yami\Database\Sql\Expression;

class ConditionBlock extends Expression {

	protected $type = "AND";
	
	protected $conditions = array();
	
	/**
	 * 
	 * @param string $type
	 */
	public function __construct($type = "AND") {
		$this->type = $type;
	}

	/**
	 * 
	 * @param mixed $condition
	 * @return \yami\Database\Sql\ConditionBlock
	 */
	public function add($condition) {
		if(!($condition instanceof Condition) || ($condition instanceof ConditionBlock)) {
			$condition = new Condition($condition);
		}
		$this->conditions[] = $condition;
		return $this;
	}
	
	public function __toString() {
		return '('.implode(' '.$this->type.' ', $this->conditions).')';
	}
	
}