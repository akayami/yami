<?php
namespace yami\Database\Sql;
use yami\Database\Sql\Expression;

class Condition extends Expression {
	
	protected $table;
	protected $field;
	protected $operator;
	protected $value;
	protected $fieldEscape = '`';
	
	public function __construct($definition) {
		$this->parse($definition);		
	}
		
	/**
	 * 
	 * @param string $name
	 * @return \yami\Database\Sql\Condition
	 */
	public function table($name) {
		$this->table = $name;
		return $this;
	}
	
	/**
	 * 
	 * @param string $name
	 * @return \yami\Database\Sql\Condition
	 */
	public function field($name) {
		$this->field = str_replace($this->fieldEscape, '', $name);
		return $this;
	}
	
	public function operator($name) {
		$this->operator = $name;
		return $this;
	}

	public function value($name) {
		$this->value = $name;
		return $this;
	}
	
	
	protected function parse($string) {
		if(preg_match('#((?P<table>\w+)\.)?(?P<field>.+)\s+(?P<operator>.+)\s+(?P<value>.+)#', $string, $matches)) {
			if(isset($matches['table']) && strlen($matches['table'])) {
				$this->table($matches['table']);
			}
			if(isset($matches['field']) && strlen($matches['field'])) {
				$this->field($matches['field']);
			}
			
			if(isset($matches['operator']) && strlen($matches['operator'])) {
				$this->operator($matches['operator']);
			}
			
			if(isset($matches['value']) && strlen($matches['value'])) {
				$this->value($matches['value']);
			}
		}
	}
	
	public function __toString() {
		return (isset($this->table) ? $this->fieldEscape.$this->table.$this->fieldEscape.'.' : '').$this->fieldEscape.$this->field.$this->fieldEscape.' '.$this->operator.' '.$this->value;		
	}
	
}