<?php
namespace yami\Database\Sql;

class ConditionBlock extends ConditionExpression {
	
	protected $operator = "AND";
	
	protected $conditions = array();
	
	/**
	 *
	 * @param string $operator
	 */
	public function __construct($operator = "AND") {
		$this->setLogicalOperator($operator);
	}
	
	
	
	public function parseStructure(array $struc) {
		$lastOp = null;
		foreach($struc as $chunk) {
			switch($chunk['expr_type']) {
				case 'colref':
					if(!isset($cond) || is_null($cond)) {
						$cond = new Condition();
						$cond->setField(Field::fromStructure($chunk));
					} else {
						if($chunk['base_expr'] && substr($chunk['base_expr'], 0, 1) == "{" && substr($chunk['base_expr'], -1) == "}") {
							$cond->setValue(new Expression($chunk['base_expr']));
						} else {							
							$cond->setValue(Field::fromStructure($chunk));
						}
						$this->add($cond);
						$cond = null;
					}
					break;
				case 'operator':
					switch($lastOp) {
						case 'colref':
							$cond->setOperator(Operator::fromStructure($chunk));
							break;
						case 'const':
							$this->setLogicalOperator($chunk['base_expr']);
							break;
						case 'in-list':
							$this->setLogicalOperator($chunk['base_expr']);
							break;
						default:
							throw new \Exception('unsupported operator context:'.$lastOp);
								
					}
					break;
				case 'const':
					$cond->setValue($chunk['base_expr']);
					$this->add($cond);
					$cond = null;
					break;
				case 'operator':
					$this->setLogicalOperator($chunk['base_expr']);
					break;
				case 'expression':
					$this->add(ConditionBlock::fromStructure($chunk['sub_tree']));					
					break;
				case 'subquery':
					$cond->setValue(new Select($chunk['sub_tree']));
					$this->add($cond);
					$cond = null;
					break;
				case 'in-list':
					$cond->setValue(Inlist::fromStructure($chunk));
					//$cond->setValue(new Expression($chunk['sub_tree'][0]['base_expr']));
					$this->add($cond);
					$cond = null;
//					print_r($cond);
					break;
				default:
//					print_r($chunk);
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
	 * @param ConditionExpression $condition
	 * @return \yami\Database\Sql\ConditionBlock
	 */
	public function add(ConditionExpression $condition) {
// 		if(!($condition instanceof Expression)) {
// 			$condition = new Condition($condition);
// 		}
		if(!is_null($this->reference)) {
			$condition->setReference($this->reference);
		}
		$this->conditions[] = $condition;
		return $this;
	}
	
	public function __toString() {
		$output = '';
		foreach($this->conditions as $condition) {
			if($condition instanceof ConditionBlock) {
				$output .= (strlen($output) > 0 ? ' '.$this->operator.' ' : '').'('.$condition.')';
			} else {
				$output .= (strlen($output) > 0 ? ' '.$this->operator.' ' : '').$condition;
			}
		}
		return $output;
	}
	
	public function __toString2() {
		
		return '(IN'.implode(' '.$this->operator.' ', $this->conditions).')';
	}	
	
}