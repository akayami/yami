<?php
namespace yami\Database\Sql;
use yami\Database\Sql\Select;
use yami\Database\Sql\Expression;


class Table extends Expression {
	
	protected $expressions = array('table', 'subquery');
	protected $schema;
	protected $tableName;
	protected $joinType;
	protected $refType;
	protected $refClause = array();
	protected $isFirst = false;
	
	public function __construct($tableName = null, $alias = null, $join = null, Condition $condition = null) {		
		$this->parseTableName($tableName);
		$this->setAlias($alias);
		$this->setJoin($join);
		$this->refType = 'ON';
		if(!is_null($condition)) $this->addJoinCondition($condition);
	}
	
	public function addJoinCondition(Condition $condition = null) {
		$this->refClause[] = $condition;
	}
	
	public function setJoin($join) {
		$this->joinType = $join;
	}
	
	
	
	public function parseStructure(array $expr) {
		//print_r($expr);

		if($expr['expr_type'] == 'table') {			
			if(isset($expr['table'])) {
				$this->parseTableName($expr['table']);
			} 
			if(isset($expr['ref_type'])) {
				$this->refType = $expr['ref_type'];
				$this->joinType = $expr['join_type'];
				if(isset($expr['ref_clause']) && is_array($expr['ref_clause'])) {					
					foreach($expr['ref_clause'] as $ref_expr) {
						switch($ref_expr['expr_type']) {
							case 'colref':
								$item = Field::fromStructure($ref_expr);
								break;
							case 'operator':
								$item = Operator::fromStructure($ref_expr);
								break;
							case 'subquery':
								$item = new Select($ref_expr['sub_tree']);
								break;
							case 'const':
								$item = $ref_expr['base_expr'];
								break;
							default:
								throw new \Exception('Unsuported element type in Join reference clause:'.$ref_expr['expr_type']);
						}
						$this->refClause[] = $item; 
					}				
				}
			}
		} elseif($expr['expr_type'] == 'subquery') {
				$this->tableName = new Select($expr['sub_tree']);
		}
		if(is_array($expr['alias'])) {
			$this->setAlias($expr['alias']['name']);		
		}
	}

	/**
	 * 
	 * @param string $schema
	 * @return \yami\Database\Sql\Table
	 */
	public function setSchema($schema) {
		$this->schema = $schema;
		return $this;
	}
	
	/**
	 * 
	 * @param string $name
	 * @return \yami\Database\Sql\Table
	 */
	public function setTablename($name) {
		$this->tableName = $this->trimIdentifier($name);
		return $this;
	}
	
	public function getTablename() {
		return $this->tableName;
	}
		
	
	public function parseTableName($field) {
		$a = explode('.', $field);
		switch(count($a)) {			
			case 2:
				$this->setSchema($a[0])->setTablename($a[1]);
				break;
			case 1:
				$this->setTablename($a[0]);
				break;
			default:
				throw new \Exception('Unparsable Field Format:'.$field);
		}
	}
	
	public function isFirst($isFirst = false) {
		$this->isFirst = $isFirst;
	}
	
	public function __toString() {
		$output = 
			(isset($this->schema) ? $this->quoteIdentifier($this->schema).'.' : '').
			($this->tableName instanceof Expression) ? '('.$this->tableName.')' : (isset($this->tableName) ? $this->quoteIdentifier($this->tableName) : '');
		$output = $output.(is_string($this->alias) ? ' AS '.$this->quoteIdentifier($this->alias) : '');
		if(!$this->isFirst) {
			$output = $this->joinType.' '.$output.' '.$this->refType.' ';
			foreach($this->refClause as $close) {
				$output .= ($close instanceof Select) ? '('.$close.')' : $close;
			}
		}
		return $output;
	}
}