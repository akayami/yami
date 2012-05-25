<?php
namespace yami\Database\Sql;

class Inlist extends Expression {
	
	protected $list = array();
	
	public function parseStructure(array $expr) {
		foreach($expr['sub_tree'] as $element) {
			switch($element['expr_type']) {
				case 'colref':
					$this->list[] = new Expression($element['base_expr']);
					break;
				default:
					throw new Exception('Unhandled inlist element type:'.$element['expr_type']); 
			}
		}
	}
	
	public function __toString() {
		return "(".implode(',', $this->list).")";
	}
	
}