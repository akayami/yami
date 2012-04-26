<?php
namespace yami\Database\Sql;

class Expression {
	
	/**
	 * 
	 * @var Select
	 */
	protected $reference;
	
	protected $quoteChr = '`';
	
	public function __construct($expression) {
		$this->expression = $expression;
	}
	
	public function __toString() {
		return $this->expression;
	}
	
	public function setReference(Select $select) {
		$this->reference = $select;
	}
	
	public function byTableByReference($argument) {
		if(isset($this->reference)) {
			return $this->reference->getTable($argument);
		}
		return $argument;
	}
	
	public function quoteIdentifier($field) {
		return $this->getIdentifierQuoteCharacter().$field.$this->getIdentifierQuoteCharacter();
	}
	
	public function getIdentifierQuoteCharacter() {
		return $this->quoteChr;
	}
	
	public function quote($value) {
		if(isset($this->reference)) {
			return $this->reference->quote($value);
		} else {
			return "'".$value."'";
		}
	}
		
}