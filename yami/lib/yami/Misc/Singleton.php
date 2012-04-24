<?php
namespace yami\Misc;

abstract class Singleton {
		
	/**
	 * @return Singleton
	 */
	static public function getInstance() {
		if(!isset(static::$instance)) {
			static::$instance = new static();
		}
		return static::$instance; 
	}
	
	protected function __construct() {
	
	}

	public function __clone() {
		throw new \Exception('Cannot clone a singleton:'.get_called_class());
	}
	
	public function __wakeup() {
		throw new \Exception('Unserializing is not allowed for singleton:'.get_called_class());
	}
}