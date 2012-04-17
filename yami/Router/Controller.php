<?php
namespace yami\Router;
use yami\Request;

use yami\Singleton;

class Controller extends Singleton {

	public $routes;
	public $controller;
	public $action;

	/**
	 * 
	 * @param Route $route
	 * @param int $priority
	 */
	public function addRoute(Route $route, $priority = 0) {
		if(isset($this->routes[$priority])) {
			$this->routes[$priority] = array();
		}
		$this->routes[$priority][] = $route;
	}
	
	/**
	 * 
	 * @param Request $param
	 */
	public function route(Request $request) {
		ksort($this->routes);
		foreach($this->routes as $routeBlock) {
			foreach($routeBlock as /* @var $route Route */ $route) {
				if($route->isValid($request)) {
					try {
						Request::getInstance()->setURI($route->getParams());	// Reading extracted parameters
						return $route->handle();
						
						$a = new $route->controller($route->action);				// Instanciating the router
						return true;
					} catch(\Exception $e) {
						throw $e;
					}
				}
			}
		}
		throw new \Exception('404');
	}
	
	/**
	 * @return Controller
	 */
	static public function getInstance() {
		return parent::getInstance();
	}
	
}