<?php
namespace yami\Database\Sql;

class Select extends Expression {

	protected $field = array();
	protected $from = array();
	protected $where;
	protected $group = array();
	protected $order = array();
	protected $limit;
	
	public function __construct() {
		
	}
	
	public function order($expression) {
		if(!($expression instanceof Order)) {
			$expression = new Order($expression);
		}	
		$expression->setReference($this);
		$this->order[] = $expression;
	}
	
	public function group($expression) {
		if(!($expression instanceof Field)) {
			$expression = new Field($expression);
		}
		$expression->setReference($this);
		$this->group[] = $expression;
	}
	
	public function limit($limit, $offset = 0) {
		if(!is_int($limit) || !is_int($offset)) throw new \Exception('Limit and offset must have numeric values');
		$this->limit['limit'] = $limit;
		$this->limit['offset'] = $offset;
	}
	
	/**
	 * 
	 * @return \yami\Database\Sql\ConditionBlock
	 */
	public function getConditionBlock() {
		if(!isset($this->where)) {
			$this->where = new ConditionBlock();
		}
		return $this->where; 
	}

	public function where($expression) {
		if(!isset($this->where)) {
			if($expression instanceof ConditionBlock) {
				$this->where = $expression;
				return $this;
			} else {
				$this->where = new ConditionBlock();				
			}
		}
		
		if(!($expression instanceof Expression)) {
			$expression = new Expression($expression); // Using Expression instead of Condition, as condition parsing is not good enough
		}
		$expression->setReference($this);
		$this->where->add($expression);
		return $this;
	}
	
	public function from($expression) {
		if(!($expression instanceof Table)) {
			$expression = new Table($expression);
		}
		$this->from[$expression->getTable()] = $expression;
		return $this;
	}
	
	public function getTable($tableName) {
		if(isset($this->from[$tableName])) {
			return $this->from[$tableName]->getIdentifier();
		} else {
			return $tableName;
		}
	}
	
	public function fields(array $fields) {
		foreach($fields as $field) {
			$this->field($field);
		}
		return $this;
	}
	
	public function field($expression) {
		if(!($expression instanceof Field)) {
			$expression = new Field($expression);
		}
		$expression->setReference($this);
		if(count($this->from) == 0 && strlen($table = $expression->getTable()) > 0) {
			$this->from($table);
		}
		$this->field[] = $expression;
		return $this;
	}
	
	/**
	 * This is unsafe, and normally is overwritten by quote binded to DB adapter that can quote and escape properly.
	 * This is here so that a query can be generated so that it's output can be used for caching without need for an instance of DB adapter.
	 * There is no other viable and safe way of doing this.
	 * 
	 * @param string $value
	 */
	public function quote($value) {
		return "'".$value."'";		
	}
	
	
	public function __toString() {
		$q = "SELECT ".(count($this->field) ? implode(', ', $this->field) : '*')." FROM ".implode(', ',$this->from);
		if(isset($this->where)) {
			$q.= ' WHERE '.$this->where;
		}
		if(count($this->group)) {
			$q.= ' GROUP BY '.implode(', ', $this->group);
		}
		if(count($this->order)) {
			$q.= ' ORDER BY '.implode(', ', $this->order);
		}
		if(isset($this->limit)) {
			$q.= ' LIMIT '.$this->limit['limit'].' OFFSET '.$this->limit['offset'];
		}
		return $q;
	}

	public function __clone() {
		throw new Exception('Cloning not tested. Do not use for now');
	}
}