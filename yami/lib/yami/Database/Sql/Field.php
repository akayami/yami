<?php
namespace yami\Database\Sql;

use yami\Database\Sql\Expression;

class Field extends Expression {
	
	protected $table;
	protected $field;
	protected $alias;
	
	public function __construct($definition = null) {
		if($definition instanceof Expression) {
			$this->field = $definition;
		} elseif(is_array($definition)) {
			$this->parse($definition);
		} else {
			if(!is_null($definition)) {
				$this->breakUpString($definition);
			}
		}
	}
	
	/**
	 * 
	 * @param string $name
	 * @return \yami\Database\Sql\Field
	 */
	public function table($name) {
		$this->table = $name;
		return $this;
	}
	
	/**
	 * 
	 * @param string $name
	 * @return \yami\Database\Sql\Field
	 */
	public function field($name) {
		if($name == '*') {
			$name = new Expression('*');
		}
		$this->field = $name;
		return $this;
	}
	
	public function getTable() {
		return $this->table;
	}
	
	/**
	 * 
	 * @param string $name
	 * @return \yami\Database\Sql\Field
	 */
	public function alias($name) {
		$this->alias = $name;
		return $this;
	}
	
	public function getField() {
		return $this->field;
	}
	
	public function getAlias() {
		return $this->alias;
	}
	
	protected function parse(array $field) {
		switch($field['expr_type']) {
			case 'colref':
				$this->breakUpFieldDetails($field['base_expr']);
				break;
			case 'aggregate_function':
				$this->field(new SQLFunction($field));
				break;
			case 'expression':
				if(count($field['sub_tree']) > 1) {
					throw new Exception('Unsupported mulit expression field');
				}					
				switch($field['sub_tree'][0]['expr_type']) {
					case 'subquery':
						$this->field(new Select($field['sub_tree'][0]['sub_tree']));
						break;
					default:
						throw new \Exception('unsupported field type expression');
				}
				break;
			default:
				throw new \Exception('unsupported field type:'.$field['expr_type']);
		}
		if(isset($field['alias'])) {
			$this->alias($field['alias']['name']);
		}
	}
	
	protected function breakUpFieldDetails($string) {
		$regex = "#((?P<table>\w+)\.)?(?P<field>(?:\w+)|(?:\*+))#";
		if(preg_match($regex, $string, $matches)) {
			if(isset($matches['table']) && strlen($matches['table'])) {
				$this->table($matches['table']);
			}
			if(isset($matches['field']) && strlen($matches['field'])) {
				$this->field($matches['field']);
			}
		}
	}
	
	protected function breakUpString($string) {		
		$regex = "#((?P<table>\w+)\.)?(?P<field>(?:\w+)|(?:\*+))(\s+as\s+(?P<alias>\w+))?#";	
		if(preg_match($regex, $string, $matches)) {
			if(isset($matches['table']) && strlen($matches['table'])) {
				$this->table($matches['table']);
			}
			
			if(isset($matches['field']) && strlen($matches['field'])) {
				$this->field($matches['field']);
			}
			if(isset($matches['alias']) && strlen($matches['alias'])) {
				$this->alias($matches['alias']);
			}
		} else{
			throw new \Exception('Unreadable field format');
		}
	}
	
	public function __toString() {
		
		if($this->field instanceof Select) {
			return '('.$this->field.')'.(strlen($this->alias) ? ' AS '.$this->quoteIdentifier($this->alias) : '');
		}
		
		return (strlen($this->table) ? $this->quoteIdentifier($this->byTableByReference($this->table)).'.' : '').$this->quoteIdentifier($this->field).(strlen($this->alias) ? ' AS '.$this->quoteIdentifier($this->alias) : '');
	}
	
	/**
	 * 
	 * @param string $field
	 * @param string $table
	 * @param string $alias
	 * @return \yami\Database\Sql\Field
	 */
	public static function make($name, $table = null, $alias = null) {
		$c = new static();
		$c->field($name)->table($table)->alias($alias);
		return $c;
	}
	
}