<?php
namespace yami\ORM;
use yami\Database\Adapter;

use yami\Database\Sql\Select as sqlSelect;

class Select extends sqlSelect {
	
	protected $orm;
	protected $placeholders;
	protected $adapter;
	
	public function __construct($ormRefrence) {
		$this->orm = $ormRefrence;
	}
	
	/**
	 * 
	 */
	public function execute(array $placeholders = array()) {
		$this->placeholders = $placeholders;
		$o = $this->orm;
		return $o::get($this);
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
}