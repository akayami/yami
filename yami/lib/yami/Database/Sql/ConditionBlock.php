<?php
namespace yami\Database\Sql;
use yami\Database\Sql\Expression;

class ConditionBlock extends Expression {

	protected $operator = "AND";
	
	protected $conditions = array();
	
	/**
	 * 
	 * @param string $operator
	 */
	public function __construct($operator = "AND") {
		$this->setLogicalOperator($operator);
	}
	
	public function setLogicalOperator($operator) {
		$this->operator = $operator;
	}
	
	public function setReference(select $select) {
		foreach($this->conditions as $condition) {
			$condition->setReference($select);
		}
		parent::setReference($select);
	}

	/**
	 * 
	 * @param mixed $condition
	 * @return \yami\Database\Sql\ConditionBlock
	 */
	public function add($condition) {
		if(!($condition instanceof Expression)) {
			$condition = new Condition($condition);
		}
		if(!is_null($this->reference)) {
			$condition->setReference($this->reference);
		}
		$this->conditions[] = $condition;
		return $this;
	}
	
	public function __toString() {
		return '('.implode(' '.$this->operator.' ', $this->conditions).')';
	}
	
	/**
	 * 
	 * @param string $operator
	 * @return \yami\Database\Sql\ConditionBlock
	 */
	public static function make($operator = "AND") {
		return new static($operator);
	}
	
}