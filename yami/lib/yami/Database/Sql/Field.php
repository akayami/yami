<?php
namespace yami\Database\Sql;
use yami\Database\Sql\Expression;

class Field extends Expression {
	
	protected $table;
	protected $field;
	protected $schema;
	
	public function __construct($expr = null) {
		if(is_array($expr)) {
			if($expr['expr_type'] != 'colref') {
				throw new \Exception('Unknown Expression Type: '.$expr['expr_type']);
			}
			$this->parse($expr);
		}
	}
	
	protected function parse(array $expr) {
		$this->parseFieldName($expr['base_expr']);
		if(isset($expr['alias']) && is_array($expr['alias'])) {
			$this->parseAlias($expr['alias']);
		}
	}

	
	public function parseFieldName($field) {
		$a = explode('.', $field);
		switch(count($a)) {			
			case 3:
				$this->setSchema($a[0])->setTable($a[1])->setFiled($a[2]);
				break;
			case 2:
				$this->setTable($a[0])->setFiled($a[1]);
				break;
			case 1:
				$this->setFiled($a[0]);
				break;
			default:
				throw new \Exception('Unparsable Field Format:'.$field);
		}
	}
	
	public function setTable($tableName) {
		$this->table = $this->trimIdentifier($tableName);
		return $this;
	}
	
	public function setFiled($fieldName) {
		if($fieldName === '*') {
			$this->field = new Expression('*');
		} else {
			$this->field = $this->trimIdentifier($fieldName);
		}
		return $this;
	}
	
	public function setSchema($schema) {
		$this->schema = $this->trimIdentifier($schema);
		return $this;
	}
	
	public function __toString() {
		return (isset($this->table) ? $this->quoteIdentifier($this->table).'.' : '').$this->quoteIdentifier($this->field).(isset($this->alias) ? ' AS '.$this->quoteIdentifier($this->alias) : '');
	}
	
} 