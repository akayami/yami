<?php
namespace yami\Database\Sql;

class Table extends Expression {
	
	protected $table;
	protected $alias;
	
	public function __construct($definition = null) {
		$this->parse($definition);
	}
	
	/**
	 * 
	 * @param string $name
	 * @return \yami\Database\Sql\Table
	 */
	public function table($name) {
		$this->table = $name;
		return $this;
	}
	
	/**
	 * 
	 * @param string $name
	 * @return \yami\Database\Sql\Table
	 */
	public function alias($name) {
		$this->alias = $name;
		return $this;
	}
	
	public function getTable() {
		return $this->table;
	}
	
	public function getAlias() {
		return $this->alias;
	}
	
	public function getIdentifier() {
		if(isset($this->alias)) {
			return $this->alias;
		} else {
			return $this->table;
		}
	}
	
	protected function parse($string) {
		if(preg_match("#(?P<table>\w+)(\s+as\s+(?P<alias>\w+))?#", $string, $matches)) {
			if(isset($matches['table']) && strlen($matches['table'])) {
				$this->table($matches['table']);
			}
			if(isset($matches['alias']) && strlen($matches['alias'])) {
				$this->alias($matches['alias']);
			}
		} else{
			throw new \Exception('Unreadable field format');
		}
	}	
	
	public function __toString() {
		return $this->quoteIdentifier($this->table).(strlen($this->alias) ? ' as '.$this->quoteIdentifier($this->alias) : '');
	}
}