<?php

namespace yami\Router\Route;
use yami\Request;

use yami\Router\Route;

class Regex extends Abstr implements Route {

	protected $regex;
	public $matches;
	
	/**
	 * 
	 * @param string $regex
	 * @param string $controller
	 * @param string $action
	 */
	public function __construct($regex, $controller, $action) {
		parent::__construct($controller, $action);
		$this->regex = $regex;	
	}

	/**
	 * (non-PHPdoc)
	 * @see yami\Router.Route::getParams()
	 */
	public function getParams() {
		return $this->matches;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see yami\Router.Route::isValid()
	 */
	public function isValid(Request $request) {
		$pieces = explode('?', $request->REQUEST_URI);	
		if(preg_match($this->regex, $pieces[0], $matches)) {
			$this->matches = $matches;
			return true;
		}
 		return false;
	}		
}