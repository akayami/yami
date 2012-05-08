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
	
	public function __construct($tableName = null, $alias = null) {
		$this->parseTableName($tableName);
		$this->setAlias($alias);
	}
	
	
	
	
	
	public function parseStructure(array $expr) {
		//print_r($expr);
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
							$item = new Field($ref_expr);
							break;
						case 'operator':
							$item = new Operator($ref_expr);
							break;
						default:
							throw new \Exception('Unsuported element type in Join reference clause');
					}
					$this->refClause[] = $item; 
				}				
			}
			if(is_array($expr['sub_tree'])) {
				if($expr['expr_type'] == 'subquery') {
					$this->refClause[] = new Select($expr['sub_tree']); 
				}
			}
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
//		print_r($this->refClause);
//		print_r($this);
		$output = (isset($this->schema) ? $this->quoteIdentifier($this->schema).'.' : '').(isset($this->tableName) ? $this->quoteIdentifier($this->tableName) : '');
		if(!$this->isFirst) {
			$output = $this->joinType.' '.$output.' '.$this->refType.' ';
			foreach($this->refClause as $close) {
				$output .= ($close instanceof Select) ? '('.$close.')' : $close;
			}
		}
		$output = $output.(is_string($this->alias) ? ' AS '.$this->quoteIdentifier($this->alias) : '');
		return $output;
	}
}