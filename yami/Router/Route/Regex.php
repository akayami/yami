<?php

namespace yami\Router\Route;
use yami\Router\Route;

class Regex implements Route {

	protected $regex;
	public $matches;
	public $controller;
	public $action;
	
	public function __construct($regex, $controller, $action) {
		$this->regex = $regex;
		$this->controller = $controller;
		$this->action = $action;		
	}
		
	public function __call($method, $args) {
		if (isset( $this->{$method}) &&  $this->{$method} instanceof \Closure ) {
			return call_user_func_array($this->{$method},$args);
		} else {
			throw new \Exception('Call to undefined method:'.get_called_class().'::'.$method);
		}		
	}
	
	public function getParams() {
		return $this->matches;
	}
	
	public function isValid($route) {
		if(preg_match($this->regex, $route, $matches)) {
			$this->matches = $matches;
			return true;
		}
 		return false;
	}
	
}