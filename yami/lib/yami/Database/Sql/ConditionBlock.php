<?php
namespace yami\Database\Sql;

class ConditionBlock extends Expression {
	
	protected $operator = "AND";
	
	protected $conditions = array();
	
	/**
	 *
	 * @param string $operator
	 */
	public function __construct($where = null, $operator = "AND") {
		$this->setLogicalOperator($operator);
		if(is_array($where)) {
			$this->parse($where);
		}
	}
	
	public function parse($struc) {
		$lastOp = null;
		foreach($struc as $chunk) {
			switch($chunk['expr_type']) {
				case 'colref':
					$cond = new Condition();
					$cond->setField(new Field($chunk));
					break;
				case 'operator':
					switch($lastOp) {
						case 'colref':
							$cond->setOperator(new Operator($chunk));
							break;
						case 'const':
							$this->setLogicalOperator($chunk['base_expr']);
							break;
						default:
							throw new \Exception('unsupported operator context:'.$lastOp);
								
					}
					break;
				case 'const':
					$cond->setValue($chunk['base_expr']);
					$this->add($cond);
					break;
				case 'operator':
					$this->setLogicalOperator($chunk['base_expr']);
					break;
				case 'expression':
					$this->add(new ConditionBlock($chunk['sub_tree']));
					break;
				case 'subquery':
					$cond->setValue(new Select($chunk['sub_tree']));
					$this->add($cond);
					break;
				default:
					throw new \Exception('unsuported expression type: '.$chunk['expr_type']);
			}
			$lastOp = $chunk['expr_type'];
		}
	}
	
	public function setLogicalOperator($operator) {
		$this->operator = $operator;
	}
	
	public function setReference(select $select) {
		foreach($this->conditions as $condition) {
			$condition->setReference($select);
		}
		parent::setReference($select);
	}
	
	/**
	 *
	 * @param mixed $condition
	 * @return \yami\Database\Sql\ConditionBlock
	 */
	public function add($condition) {
		if(!($condition instanceof Expression)) {
			$condition = new Condition($condition);
		}
		if(!is_null($this->reference)) {
			$condition->setReference($this->reference);
		}
		$this->conditions[] = $condition;
		return $this;
	}
	
	public function __toString() {
		return '('.implode(' '.$this->operator.' ', $this->conditions).')';
	}	
	
}