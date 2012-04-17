<?php
namespace yami\Router\Route;
use yami\Http\Request;

use yami\Router\Route;
use yami\Router\Route\Abstr;

class Simple extends Abstr implements Route {
	
	protected $pattern;
	
	public function __construct($pattern, $controller, $action) {
		parent::__construct($controller, $action);
		$this->pattern = $pattern;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see yami\Router.Route::isValid()
	 */
	public function isValid(Request $request) {
		if($request->REQUEST_URI == $this->pattern) {
			return true;
		}
		return false;		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see yami\Router.Route::getParams()
	 */
	public function getParams() {
		return array();
	}	
}