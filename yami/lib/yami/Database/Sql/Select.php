<?php
namespace yami\Database\Sql;

class Select extends Expression {

	protected $field = array();
	protected $from = array();
	protected $where = array();
	protected $group = array();
	protected $order = array();
	protected $limit = array();
	
	public function __construct() {
		
	}
	
	public function where($expression) {
		$where = new Where($expression);
		return $this;
	}
	
	/**
	 * 
	 * @param mixed $expression
	 * @return \yami\Database\Sql\Select
	 */
	public function from($expression) {
		if(!($expression instanceof Table)) {
			$expression = new Table($expression);
		}
		$this->from[] = $expression;
// 		if(!isset($this->from[$tableName = $expression->getTable()])) {
// 			$this->from[$tableName] = array($expression);
// 		} else {
// 			$this->from[$tableName][] = $expression;
// 		}
		if(strlen($expression->getAlias())) {
			$this->updateTable($expression);
		}
		return $this;
	}
	
	protected function updateTable(Table $table) {
		if(isset($this->field[$table->getTable()])) {
			foreach($this->field[$table->getTable()] as $index => $field) {
				if(strlen($fieldAlias = $table->getAlias())) {
					$field->table($fieldAlias);
				}
			}
		}
	}
	
	/**
	 *
	 * @param mixed $expression
	 * @return \yami\Database\Sql\Select
	 */
	public function field($expression) {
		if(!($expression instanceof Field)) {
			$expression = new Field($expression);
		}
		 
		if(!isset($this->field[$tableName = $expression->getTable()])) {
			$this->field[$tableName] = array($expression);
		} else {
			$this->field[$tableName][] = $expression;
		}
		return $this;
	}
	
	
	public function __toString() {
		$fieldArr = array();
		foreach($this->field as $table => $fields) {
			$fieldArr = array_merge($fieldArr, $fields);
		}
		$q = "SELECT ".implode(',', $fieldArr)." FROM ".implode(', ',$this->from);
		return $q;
	}	
}