<?php
namespace yami\ORM;
use yami\Database\Adapter;

use yami\Database\Sql\Select as sqlSelect;

class Select extends sqlSelect {
	
	protected $handler;
	protected $placeholders;
	protected $adapter;
		
	/**
	 * 
	 */
	public function execute(array $placeholders = array()) {
		$this->placeholders = $placeholders;
		$o = $this->handler;
		return $o::load($this);
	}

	public function getPlaceholders() {
		return $this->placeholders;
	}
	
	public function hasPlaceholders() {
		return (count($this->placeholders) ? true : false);
	}
	
	public function quote($value) {
		$value = trim($value, "'");
		if(isset($this->adapter)) {
			return $this->adapter->quote($value);
		} else {
			return parent::quote($value);
		}
	}
	
	public function setDbAdapter(Adapter $adapter) {
		$this->adapter = $adapter;			
	}
	
	public function setCollectionName($reference) {
		$this->handler = $reference;
	}
	
	public function getCollectionName() {
		return $this->handler;
	}
}