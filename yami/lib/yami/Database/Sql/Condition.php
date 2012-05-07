<?php
namespace yami\Database\Sql;

class Condition extends Expression {
	
	protected $field;
	protected $operator;
	protected $value;
	

	public function setField(Field $field) {
		$this->field = $field;
	}
	
	public function setOperator(Operator $operator) {
		$this->operator = $operator;
	}
	
	public function setValue($value) {
		$this->value = $value;
	}
	
	public function __toString() {
		return $this->field.$this->operator.$this->quote($this->value);
	}
}