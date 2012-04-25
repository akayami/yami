<?php
namespace yami\Database\Sql;

use yami\Database\Sql\Expression;

class Field extends Expression {
	
	protected $table;
	protected $field;
	protected $alias;
	protected $fieldEscape = '`';
	
	public function __construct($definition = null) {
		$this->parse($definition);
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
		$this->field = $name;
		return $this;
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
	
	public function getTable() {
		return $this->table;
	}
	
	public function getField() {
		return $this->field;
	}
	
	public function getAlias() {
		return $this->alias;
	}
	
	protected function parse($string) {		
		if(preg_match("#((?P<table>\w+)\.)?(?P<field>\w+)(\s+as\s+(?P<alias>\w+))?#", $string, $matches)) {
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
		return (strlen($this->table) ? $this->fieldEscape.$this->table.$this->fieldEscape.'.' : '').$this->fieldEscape.$this->field.$this->fieldEscape.(strlen($this->alias) ? ' AS '.$this->fieldEscape.$this->alias.$this->fieldEscape : '');
	}
	
}