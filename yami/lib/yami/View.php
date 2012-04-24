<?php
namespace yami;

use yami\Misc\ArrayObject;

class View extends ArrayObject {
	
	protected $action;
	
 	public function __construct(array $data = array()) {
 		$this->__data = $data;
 	}
	
	
	public function render() {	
// 		$incPaths = explode(PATH_SEPARATOR, get_include_path());
// 		foreach($incPaths as $path) {
// 			$file = $path.$this->action.'.phtml';
// 			if(($real = realpath($file)) !== false) {
// 				require($real);
// 			}
// 		}
		if(!@include($this->action.'.phtml')) {
			throw new \Exception('Missing view:'.$this->action.'.phtml');
		}
	}
	
	public function setActionName($string) {
		$this->action = $string;
	}
	
	/**
	 * 
	 * 
	 * @param filepath $path  A filepaht within the include path available
	 * @param array $data	  Provide dataset to use if different than within the main scope
	 */
	public function inject($path, array $data = null) {
		if(is_null($data)) {
			require($path.'.phtml');
		} else {
			$v = new View($data);
			$v->setActionName($path);
			$v->render();
		}
	}
}