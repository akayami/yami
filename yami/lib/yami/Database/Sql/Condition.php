<?php
namespace yami\Database\Sql;
use yami\Database\Sql\Expression;

class Condition extends Expression {
	
	protected $table;
	protected $field;
	protected $operator;
	protected $value;
	
	protected $expression;
	protected $alias;
		
	public function __construct($definition = null, $alias = null) {
		if(!is_null($definition)) {
			if($definition instanceof Expression) {
				$this->expression = $definition;
				$this->alias = $alias;
			}
			$this->parse($definition);
		}	
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
		$this->field = str_replace($this->getIdentifierQuoteCharacter(), '', $name);
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
		if(isset($this->expression)) {
			return '('.$this->expression.') as '.$this->quoteIdentifier($this->alias);
		}
		return (isset($this->table) ? $this->quoteIdentifier($this->byTableByReference($this->table)).'.' : '').$this->quoteIdentifier($this->field).' '.$this->operator.' '.$this->quote($this->value);		
	}
	
	/**
	 * 
	 * @param string $field
	 * @param string $value
	 * @param string $operator
	 */
	public static function make($field = null, $value = null, $operator = '=', $table = null) {
		$c = new Static();
		$c->field($field)->value($value)->operator($operator)->table($table);
		return $c;
	} 
	
}