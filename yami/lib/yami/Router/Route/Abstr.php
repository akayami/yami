<?php
namespace yami\Router\Route;

abstract class Abstr {
	
	public $controller;
	public $action;

	/**
	 * 
	 * @param string $controller
	 * @param string $action
	 */
	public function __construct($controller, $action) {
		$this->controller = $controller;
		$this->action = $action;
	}
	
	/**
	 * @return boolean
	 */
	public function handle() {
		$a = new $this->controller($this->action);
		return $a->{$this->action}();
	}
	
}