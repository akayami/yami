<?php
namespace yami\Database\Sql;

class Condition extends ConditionExpression {
	
	protected $field;
	protected $operator;
	protected $value;
	
	
	/**
	 * 
	 * @param ConditionField $field
	 * @param Operator $operator
	 * @param string $value
	 */
	public function __construct(ConditionField $field = null, Operator $operator = null,  $value = '') {
		if(!is_null($field)) $this->setField($field);
		if(!is_null($operator)) $this->setOperator($operator);
		if(!is_null($value)) $this->setValue($value);		
	}
	
	public function parseStructure(array $expr) {
		throw new \Exception('Cannot parse field structure');	
	}
	
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