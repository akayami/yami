<?php
namespace yami\Database\Sql;
use yami\Database\Sql\Expression;

class Select extends Expression {

	protected $fields = array();
	protected $tables = array();
	protected $where;
	protected $group;	
	protected $having;
	protected $order;
	protected $limit;
	protected $union;
	protected $unionKeywords = array('UNION', 'UNION ALL', 'UNION DISTINCT');
	protected $unionToken;
	
	public function __construct($query = null) {
		if(!is_null($query)) {
			if(is_array($query)) {
				$this->parseStructure($query);
			} elseif (is_string($query)) {
				$this->parseString($query);
			}
		}		
	}
		
	/**
	 * 
	 * @param Order $order
	 * @return \yami\Database\Sql\Select
	 */
	public function addOrder(Order $order) {
		$this->order[] = $order;
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
		return (!($this->having instanceof ConditionBlock) ? $this->having = new ConditionBlock() : $this->having);
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
		$this->group[] = $aggregateField;
	}
	
	/**
	 * 
	 * @param Limit $limit
	 * @return \yami\Database\Sql\Select
	 */
	public function addLimit(Limit $limit) {
		$this->limit = $limit;
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
		return isset($this->limit);
	}
	
	public function getLimitValue() {
		return $this->limit->rowcount();
	}
	
	public function getOffsetValue() {
		return $this->limit->offset();
	}
	
	protected function parseString($string) {
		$parser = new \PHPSQLParser($string);
		if($parser->parsed) {
			$this->parseStructure($parser->parsed);
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
		return (!($this->where instanceof ConditionBlock) ? $this->where = new ConditionBlock() : $this->where);
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
		$this->tables[] = $table;
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
		$this->fields[] = $field;
		return $this;
	}
	
	public function parseStructure(array $struc) {

		if(isset($struc[$this->unionKeywords[0]]) || isset($struc[$this->unionKeywords[1]]) || isset($struc[$this->unionKeywords[2]])) {
			foreach($struc as $key => $value) {				
				if(in_array($key, $this->unionKeywords)) {
					$this->unionToken = $key;
					foreach($value as $stmt) {
						$this->union[] = new Select($stmt);
					}
				} else {
					throw new \Exception('Unparsable structure');
				}
			}
			return;
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
				$this->group[] = Aggregate::fromStructure($group);
			}
		}
		if(isset($struc['HAVING'])) {
			$this->having = ConditionBlock::fromStructure($struc['HAVING']);
		}
		
		if(isset($struc['ORDER'])) {
			foreach($struc['ORDER'] as $group) {
				$this->order[] = Order::fromStructure($group);//new Order($group);
			}
		}
		if(isset($struc['LIMIT'])) {
			if(is_numeric($struc['LIMIT']['rowcount']) && (is_numeric($struc['LIMIT']['offset']))) {
				$this->limit = new Limit($struc['LIMIT']['rowcount'], $struc['LIMIT']['offset']);
			} elseif(is_numeric($struc['LIMIT']['rowcount'])) {
				$this->limit = new Limit($struc['LIMIT']['rowcount']);
			} else {
				throw new \Exception('Offset not supported without limit (do not ask me why)');
			}
		}
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
			case '':
				return new Expression('');
		}
	}
	
	protected function buildUnion() {
		return implode(' '.$this->unionToken.' ', $this->union);
	}
	
	public function get($skipLimit = false, $skipOrder = false) {
		if(is_array($this->union)) {
			return $this->buildUnion();
		}
		//echo (count($this->fields));
		if(count($this->fields) == 0) {
			$this->fields[] = new Field('*');
		}
		$output = "SELECT ".implode(', ', $this->fields);
		for($i = 0; $i < count($this->tables); $i++) {
			$table = $this->tables[$i];
			if($i ==0) {
				$table->isFirst(true);
				$output .= ' FROM';
			}
			$output .= ' '.$table;
		}
		if(isset($this->where)) {
			$output .= ' WHERE '.$this->where;
		}
		if(is_array($this->group)) {
			$output .= ' GROUP BY '.implode(', ', $this->group);
		}
		if(isset($this->having)) {
			$output .= ' HAVING '.$this->having;
		}
		if(isset($this->order) && !$skipOrder) {
			$output .= ' ORDER BY '.implode(', ', $this->order);
		}
		if(isset($this->limit) && !$skipLimit) {
			$output .= $this->limit;
		}
		return $output;		
	}
	
	public function __toString() {
		return $this->get();
	}
	
	/**
	 * 
	 * @return $this;
	 */
	public function unsetLimit() {
		$this->limit = null;
		return $this;
	}

	/**
	 *
	 * @return \yami\Database\Sql\Select
	 */
	public function unsetWhere() {
		$this->where = null;
		return $this;
	}
	
	/**
	 *
	 * @return \yami\Database\Sql\Select
	 */
	public function unsetGroup() {
		$this->group  = null;
		return $this;
	}
	
	/**
	 *
	 * @return \yami\Database\Sql\Select
	 */
	public function unsetHaving() {
		$this->having  = null;
		return $this;
	}
	
	/**
	 *
	 * @return \yami\Database\Sql\Select
	 */
	public function unsetOrder() {
		$this->order  = null;
		return $this;
	}
	
}