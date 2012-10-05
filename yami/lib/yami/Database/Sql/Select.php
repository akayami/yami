<?php
namespace yami\Database\Sql;
use yami\Database\Sql\Expression;

class Select extends Expression {

	protected $parsed = array('fields' => array(), 'tables' => array(), 'options' => array());
	
//	protected $fields = array();
//	protected $tables = array();
//	protected $where;
//	protected $group;	
//	protected $having;
//	protected $order;
//	protected $limit;
//	protected $union;
	protected $unionKeywords = array('UNION', 'UNION ALL', 'UNION DISTINCT');
	protected $unionToken;
	public $structure;
	protected $cache = true;
	protected $cacheTTL = 86400;
	
	public function __construct($query = null, $cache = null, $cacheTLL = null) {
		
		if($cache != null) {
			$this->cache = ($cache ? true : false);
		}
		if(is_int($cacheTLL)) {
			$this->cacheTTL = $cacheTLL;
		}
		
		if(!is_null($query)) {
			if(is_array($query)) {
				$this->parseStructure($query);
			} elseif (is_string($query)) {
				$this->parseString($query);
			}
		}		
	}
	
	public function getTableNamesList() {
		$out = array();
		foreach($this->parsed['tables'] as /* @var Table */ $table) {
			$out[] = $table->getTablename();
		}
		return $out;
	}
	
	/**
	 * 
	 * @param string $option
	 * @return \yami\Database\Sql\Select
	 */
	public function addQueryOption($option) {
		$this->parsed['options'][] = $option;
		return $this;
	}
	
	/**
	 * 
	 * @param Expression $order
	 * @return \yami\Database\Sql\Select
	 */
	public function addOrder(Expression $order) {
		$this->parsed['order'][] = $order;
		return $this;
	}
	
	/**
	 * 
	 * @param ConditionExpression $condition
	 * @return \yami\Database\Sql\Select
	 */
	public function addHaving(ConditionExpression $condition) {
		$this->getHaving()->add($condition);
		return $this;
	}
	
	/**
	 * 
	 * @return ConditionBlock
	 */
	public function getHaving() {
		return (!($this->parsed['having'] instanceof ConditionBlock) ? $this->parsed['having'] = new ConditionBlock() : $this->parsed['having']);
	}
	
	/**
	 * Alias for AddAggregate
	 * 
	 * @param Aggregate $aggregateField
	 */
	public function addGroup(Aggregate $aggregateField) {
		$this->addAggregate($aggregateField);
		return $this;	
	}
	
	
	public function addAggregate(Aggregate $aggregateField) {
		$this->parsed['group'][] = $aggregateField;
	}
	
	/**
	 * 
	 * @param Limit $limit
	 * @return \yami\Database\Sql\Select
	 */
	public function addLimit(Limit $limit) {
		$this->parsed['limit'] = $limit;
		return $this;
	}
	
	/**
	 * 
	 * @param int $rowcount
	 * @param int $offset
	 * @return \yami\Database\Sql\Select
	 */
	public function setLimit($rowcount, $offset = null) {
		$this->addLimit(new Limit($rowcount, $offset));
		return $this;
	}
	
	/**
	 * 
	 * @param ConditionExpression $c
	 * @return \yami\Database\Sql\Select
	 */
	public function addCondition(ConditionExpression $c) {
		$this->getWhere()->add($c);
		return $this;
	}
	
	public function hasLimit() {
		return isset($this->parsed['limit']);
	}
	
	public function getLimitValue() {
		return $this->parsed['limit']->rowcount();
	}
	
	public function getOffsetValue() {
		return $this->parsed['limit']->offset();
	}
	
	protected function parseString($string) {
		$key = md5($string);
		if(apc_exists($key) && $this->cache) {
			$ok = false;
			$s = apc_fetch($key, $ok);
			if($ok) {
				$this->structure = $s;				
			}
		} else {
			$this->structure = new \PHPSQLParser($string);
			if($this->cache) {
				apc_store($key, $this->structure, $this->cacheTTL);
			}
		}		
		if($this->structure->parsed) {
			$key = md5('parsed_'.$string);
			if(apc_exists($key) && $this->cache) {
				$ok = false;
				$s = apc_fetch($key, $ok);
				if($ok) {
					$this->parsed = $s;
				}				
			} else {
				$this->parseStructure($this->structure->parsed);
				if($this->cache) {
					apc_store($key, $this->parsed, $this->cacheTTL);
				}
			}
		}		
	}
	
	/**
	 * 
	 * @param string $string
	 * @return \yami\Database\Sql\Select
	 */
	public function field($string) {
		if(is_string($string)) {
			$this->parseString('SELECT '.$string);			
		}
		return $this;
	}
	
	/**
	 * 
	 * @param string $string
	 * @return \yami\Database\Sql\Select
	 */
	public function table($string) {
		if(is_string($string)) {
			$this->parseString('FROM '.$string);			
		}
		return $this;
	}
	
	
	/**
	 * 
	 * @return \yami\Database\Sql\ConditionBlock
	 */
	public function getWhere() {
		return (!($this->parsed['where'] instanceof ConditionBlock) ? $this->parsed['where'] = new ConditionBlock() : $this->parsed['where']);
	}
	
	/**
	 * 
	 * @param Condition $condition
	 * @return \yami\Database\Sql\Select
	 */
	public function where($condition) {		
		if(is_string($condition)) {
			$this->parseString('WHERE '.$condition);
		}
		return $this;
	}
	
	/**
	 * 
	 * @param mixed $expression
	 * @return \yami\Database\Sql\Select
	 */
	public function order($expression) {
		if(is_string($expression)) {
			$this->parseString(' ORDER BY '.$expression);
		}
		return $this;
	}
	
	/**
	 * 
	 * @param mixed $expression
	 * @return \yami\Database\Sql\Select
	 */
	public function having($expression) {
		if(is_string($expression)) {
			$this->parseString('HAVING '.$expression);
		}
		return $this;
	}
	
	/**
	 *
	 * @param mixed $expression
	 * @return \yami\Database\Sql\Select
	 */
	public function group($expression) {
		if(is_string($expression)) {
			$this->parseString(' GROUP BY '.$expression);
		}
		return $this;
	}
	
	/**
	 *
	 * @param mixed $expression
	 * @return \yami\Database\Sql\Select
	 */
	public function limit($expression) {
		if(is_string($expression)) {
			$this->parseString('LIMIT '.$expression);
		}
		return $this;
	}
	
	
	/**
	 * 
	 * @param Table $table
	 * @return \yami\Database\Sql\Select
	 */
	public function addTable(Table $table) {
		$this->parsed['tables'][] = $table;
		return $this;		
	}
	
	/**
	 * 
	 * @param unknown_type $field
	 * @throws \Exception
	 * @return \yami\Database\Sql\Select
	 */
	public function addField($field) {
		if(($field instanceof Expression) === false) {
			throw new \Exception('Unsupported field format');
		}
		$this->parsed['fields'][] = $field;
		return $this;
	}
	
	public function parseStructure(array $struc) {
		if(isset($struc[$this->unionKeywords[0]]) || isset($struc[$this->unionKeywords[1]]) || isset($struc[$this->unionKeywords[2]])) {
			foreach($struc as $key => $value) {				
				if(in_array($key, $this->unionKeywords)) {
					$this->unionToken = $key;
					foreach($value as $stmt) {
						$this->parsed['union'][] = new Select($stmt);
					}
				} else {
					throw new \Exception('Unparsable structure');
				}
			}
			return;
		}
		if(isset($struc['OPTIONS'])) {
			foreach($struc['OPTIONS'] as $option) {
				$this->parsed['options'][] = $option;
			}
		}
		if(isset($struc['SELECT'] )) {
			foreach($struc['SELECT'] as $item) {
				$this->addField($this->processField($item));
			}
		}
		if(isset($struc['FROM'] )) {
			foreach($struc['FROM'] as $item) {
				$this->addTable($this->processTable($item));
			}
		}
		
		if(isset($struc['WHERE'])) {
			$this->getWhere()->parseStructure($struc['WHERE']);			
		}
		if(isset($struc['GROUP'])) {
			foreach($struc['GROUP'] as $group) {
				$this->parsed['group'][] = Aggregate::fromStructure($group);
			}
		}
		if(isset($struc['HAVING'])) {
			$this->parsed['having'] = ConditionBlock::fromStructure($struc['HAVING']);
		}
		
		if(isset($struc['ORDER'])) {
			foreach($struc['ORDER'] as $group) {
				$this->parsed['order'][] = Order::fromStructure($group);//new Order($group);
			}
		}
		if(isset($struc['LIMIT'])) {
			$this->parsed['limit'] = new Limit((isset($struc['LIMIT']['rowcount']) ? $struc['LIMIT']['rowcount'] : null), (isset($struc['LIMIT']['offset']) ? $struc['LIMIT']['offset'] : null));			
		}
		return $this;
	}
		
	protected function processTable(array $item) {
		return Table::fromStructure($item);
	}
	
	protected function processField(array $item) {
		switch($item['expr_type']) {		
			case 'colref':
				return Field::fromStructure($item);
			case 'aggregate_function':
				return new Func($item);
			case 'subquery':				
				return new Select($item);
			case '':
				return new Expression('');
		}
	}
	
	protected function buildUnion() {
		return implode(' '.$this->unionToken.' ', $this->parsed['union']);
	}
	
	public function get($skipLimit = false, $skipOrder = false) {
		if(isset($this->parsed['union']) && is_array($this->parsed['union'])) {
			return $this->buildUnion();
		}
		//echo (count($this->parsed['fields']));
		if(count($this->parsed['fields']) == 0) {
			$this->parsed['fields'][] = new Field('*');
		}
		$output = "SELECT ".implode(',', $this->parsed['options']).' '.implode(', ', $this->parsed['fields']);
		//print_r($this->parsed['tables']);exit;
		for($i = 0; $i < count($this->parsed['tables']); $i++) {
			$table = $this->parsed['tables'][$i];
			if($i ==0) {
				$table->isFirst(true);
				$output .= ' FROM';
			}
			$output .= ' '.$table;
		}
		if(isset($this->parsed['where'])) {
			$output .= ' WHERE '.$this->parsed['where'];
		}
		if(isset($this->parsed['group']) && is_array($this->parsed['group'])) {
			$output .= ' GROUP BY '.implode(', ', $this->parsed['group']);
		}
		if(isset($this->parsed['having'])) {
			$output .= ' HAVING '.$this->parsed['having'];
		}
		if(isset($this->parsed['order']) && !$skipOrder) {
			$output .= ' ORDER BY '.implode(', ', $this->parsed['order']);
		}
		if(isset($this->parsed['limit']) && !$skipLimit) {
			$output .= $this->parsed['limit'];
		}
		return $output;		
	}
	
	public function __toString() {
		return $this->get();
	}
	
	/**
	 * 
	 * @return \yami\Database\Sql\Select
	 */
	public function unsetTable() {
		$this->parsed['tables'] = array();
		return $this;
	}
	
	/**
	 * 
	 * @return $this;
	 */
	public function unsetLimit() {
		$this->parsed['limit'] = null;
		return $this;
	}

	/**
	 *
	 * @return \yami\Database\Sql\Select
	 */
	public function unsetWhere() {
		$this->parsed['where'] = null;
		return $this;
	}
	
	/**
	 *
	 * @return \yami\Database\Sql\Select
	 */
	public function unsetGroup() {
		$this->parsed['group']  = null;
		return $this;
	}
	
	/**
	 *
	 * @return \yami\Database\Sql\Select
	 */
	public function unsetHaving() {
		$this->parsed['having']  = null;
		return $this;
	}
	
	/**
	 *
	 * @return \yami\Database\Sql\Select
	 */
	public function unsetOrder() {
		$this->parsed['order']  = null;
		return $this;
	}
	
	public function unsetQueryOption() {
		$this->parsed['options'] = null;
		return $this;
	}
	
}