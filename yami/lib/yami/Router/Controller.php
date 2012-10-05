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
						$this->handleRoute($this->route);
						return true;						
					} catch(\Exception $e) {
						throw $e;
// 						if($e->getCode() != 404) {
// 							throw new \Exception('Internal System Error: '.$request.' '.$e->getMessage(), 500, $e);
// 						}						
					}
				}
			}
		}
		throw new \Exception('Requested resource not found: '.$request, 404);
	}	
	
	/**
	 * 
	 * @param Route $route
	 * @throws Exception
	 */
	protected function handleRoute(Route $route) {
 		$previous = set_error_handler(function($errno, $errstr, $errfile, $errline) {
 			throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);			
 		});
		try {
			$cName = $route->getController();
			$a = new $cName($route->getAction());
			$a->{$route->getAction()}();
			$a->render();
		} catch(\Exception $e) {
			throw $e;
		}
		if($previous != null) {
			set_error_handler($previous);
		}
	}
	
	/**
	 * @return Controller
	 */
	static public function getInstance() {
		return parent::getInstance();
	}
	
}