<?php
namespace yami\Router;
use yami\Misc\Singleton;
use yami\Http\Request;

class Controller extends Singleton {

	
	protected static $instance;
	
	public $routes;
	
	/**
	 * 
	 * @var Route
	 */
	public $route;

	/**
	 * 
	 * @param Route $route
	 * @param int $priority
	 */
	public function addRoute(Route $route, $priority = 0) {
		if(!isset($this->routes[$priority])) {
			$this->routes[$priority] = array();
		}
		$this->routes[$priority][] = $route;
	}
	
	/**
	 * 
	 * @param string $request
	 */
	public function route($request) {
		ksort($this->routes);
		foreach($this->routes as $routeBlock) {
			foreach($routeBlock as /* @var $route Route */ $route) {
				if($route->isValid($request)) {
					try {
						Request::getInstance()->setURI($route->getParams());	// Reading extracted parameters
						$this->route = $route;						
						return $route->handle();						
					} catch(\Exception $e) {
						throw new \Exception('Internal System Error: '.$request, 500, $e);						
					}
				}
			}
		}
		throw new \Exception('Requested resource not found: '.$request, 404);
	}
	
	/**
	 * @return Controller
	 */
	static public function getInstance() {
		return parent::getInstance();
	}
	
}