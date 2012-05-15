<?php
namespace yami\ORM;
use yami\Database\Adapter;

use yami\Database\Sql\Select as sqlSelect;

class Select extends sqlSelect {
	
	protected $handler;
	protected $placeholders;
	protected $adapter;
	
	
	public function hash($excludeLimit = false, $excludeOrder = false) {
		if(is_array($this->placeholders) && count($this->placeholders) > 0) {
			$phs = $this->placeholders;
 			ksort($phs);
			return hash('md5', $this->get($excludeLimit, $excludeOrder).serialize($phs));
		} else {
			return hash('md5', $this->get($excludeLimit, $excludeOrder));
		}
	}
		
	/**
	 * 
	 * @param array $placeholders
	 * @return Collection
	 */
	public function execute(array $placeholders = array()) {
		$this->placeholders = $placeholders;
		$o = $this->handler;
		return $o::load($this);
	}
	
	public function generate(array $placeholders = array()) {
		$this->placeholders = $placeholders;
		$o = $this->handler;
		return $o::fetch($this);
	}
	
	public function setPlaceholders(array $phs) {
		$this->placeholders = $phs;
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