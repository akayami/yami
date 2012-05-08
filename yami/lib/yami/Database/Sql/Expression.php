<?php
namespace yami\Database\Sql;

class Expression {

	protected $reference;
	protected $quoteChr = '`';
	protected $alias;
	protected $expr;
	
	public function __construct($expr = null) {
		$this->expr = $expr;
	}
	
	public static function fromStructure(array $expr) {
		$a = new Static();
		$a->parseStructure($expr);
		return $a;
	}
	
	public function parseStructure(array $expr) {
		
	}
			
	public function setReference(Select $select) {
		$this->reference = $select;
	}
	
	public function trimIdentifier($identifier) {
		return trim($identifier, $this->getIdentifierQuoteCharacter());
	}
	
	public function trimValue($value) {
		return trim($value, $this->getValueQuoteCharacter());
	}
	
	public function quoteIdentifier($field) {
		if($field instanceof Expression) {
			return $field;
		}
		return $this->getIdentifierQuoteCharacter().$field.$this->getIdentifierQuoteCharacter();
	}
	
	public function getIdentifierQuoteCharacter() {
		return $this->quoteChr;
	}
	
	public function getValueQuoteCharacter() {
		return "'";
	}
	
	protected function parseAlias(array $expr) {
		$this->setAlias($expr['base_expr']);
	}
	
	public function setAlias($aliasName) {
		if(strlen($aliasName) > 0) {
			$this->alias = $this->trimIdentifier($aliasName);
		}
		return $this;
	}	
	
	public function quote($value) {
		if($value instanceof Expression) {
			return "(".$value.")";
		} else {
			$value = $this->trimValue($value);
			if(isset($this->reference)) {
				return $this->reference->quote($value);
			} else {
				return "'".$value."'";
			}
		}
	}
	
	public function __toString() {
		return $this->expr;
	}
}