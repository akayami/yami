<?php
namespace yami\Database\Sql;

class Order extends Field {
	
	
	protected $direction = 'ASC';
	
	public function __construct($fieldName = null, $direction = null) {
		if(!is_null($fieldName)) $this->parseFieldName($fieldName);
		if(!is_null($direction)) $this->setDirection($direction);
	} 
	
	public function parseStructure(array $expr) {
		if(!in_array($expr['type'], array('expression', 'alias'))) {
			throw new \Exception('Unknown Expression Type: '.$expr['type']);
		}
		$this->parseFieldName($expr['base_expr']);
		$this->setDirection($expr['direction']);		
	}
	
	public function parseFieldName($string) {
		if(trim(strtoupper($string)) == 'RAND()') {
			$this->field = new Expression('RAND()');
			return $this;
		}
		parent::parseFieldName($string);
		return $this;
	}
		
	public function setDirection($direction = 'ASC') {
		if(strtoupper($this->direction) == 'ASC') {
			$this->direction = 'ASC';
		} else {
			$this->direction = 'DESC';
		}
		return $this;
	}
	
	public function __toString() {
		return parent::__toString().' '.$this->direction;
	}
}