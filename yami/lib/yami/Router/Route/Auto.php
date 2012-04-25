<?php
namespace yami\Router\Route;
use yami\Http\Request;

use yami\Router\Route;

class Auto extends Regex {

	protected $regex;
	public $matches;
	protected $namespace;
	
	/**
	 * 
	 */
	public function __construct($defaultControllerNamespace = '') {
		parent::__construct('#/(?<controller>\w+)/(?<action>\w+)(?<uriparams>.*)$#', '', '');
		$this->namespace = $defaultControllerNamespace;
	}	
	
	/**
	 * (non-PHPdoc)
	 * @see yami\Router.Route::isValid()
	 */
	public function isValid($request) {
		if($result = parent::isValid($request)) {
			$this->controller = $this->matches['controller'];
			$this->action = $this->matches['action'];
			if(isset($this->matches['uriparams'])) {
				$this->matches = array_merge($this->matches, $this->parseURI($this->matches['uriparams']));
			}
		}
		return $result;		
	}
	
	protected function parseURI($uri) {
		$list = explode('/', trim($this->matches['uriparams'], '/'));
		$i = 2;
		$values = array();
		foreach($list as $element) {
			if($i % 2 == 0) {
				$key = $element;
			} else {
				$values[$key] = $element;
			}
			$i++;
		}
		return $values;
	}
	
	public function handle() {
		$incPaths = explode(PATH_SEPARATOR, get_include_path());
		foreach($incPaths as $path) {
			$class = $this->controller;
			$file = $path.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $this->namespace).DIRECTORY_SEPARATOR.$class.'.php';			
			if(($real = realpath($file)) !== false) {
				$object = $this->namespace.'\\'.$class;
				$a = new $object($this->action);
				if(method_exists($a, $this->action)) {
					$result = $a->{$this->action}();
				} else {
					throw new \yami\Router\Exception('Action Not Found', 404);
				}
				$a->render();
				return $result;
			}
		}
		throw new \yami\Router\Exception('Not Found', 404);
	}
}