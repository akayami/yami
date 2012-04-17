<?php
namespace yami\Router;
use yami\Request;

use yami\Singleton;

class Controller extends Singleton {

	public $routes;

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
	 * @param string $path
	 */
	public function route($path) {
		ksort($this->routes);
		foreach($this->routes as $routeBlock) {
			foreach($routeBlock as $route) {
				if($route->isValid($path)) {
					try {
						Request::getInstance()->appendArray($route->getParams());
						$a = new $route->controller($route->action);
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