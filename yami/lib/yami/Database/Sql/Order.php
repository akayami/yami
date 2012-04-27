<?php
namespace yami\Database\Sql;
use yami\Database\Sql\Field;

class Order extends Field {
		
	protected $direction = 'ASC';
	
	/**
	 * 
	 * @param string $direction
	 * @return \yami\Database\Sql\Order
	 */
	public function direction($direction = 'ASC') {
		if($direction == 'ASC') {
			$this->direction = 'ASC';
		} else {
			$this->direction = 'DESC';
		}
		return $this;
	}
	
	protected function parse($string) {
		if(preg_match("#((?P<table>\w+)\.)?(?P<field>\w+)(\s+(?P<direction>(asc|desc)))?#i", $string, $matches)) {
			if(isset($matches['table']) && strlen($matches['table'])) {
				$this->table($matches['table']);
			}
			if(isset($matches['field']) && strlen($matches['field'])) {
				$this->field($matches['field']);
			}
			if(isset($matches['direction']) && strlen($matches['direction'])) {
				$this->direction($matches['direction']);
			}
			
		} else{
			throw new \Exception('Unreadable field format');
		}
	}
	
	public function __toString() {
		return (strlen($this->table) ? $this->quoteIdentifier.$this->byTableByReference($this->table).$this->quoteIdentifier.'.' : '').$this->quoteIdentifier.$this->field.$this->quoteIdentifier.' '.$this->direction;
	}	
}