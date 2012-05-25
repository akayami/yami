<?php
namespace yami;

use yami\Misc\ArrayObject;

class View extends ArrayObject {
	
	protected $action;
	
 	public function __construct(array $data = array()) {
 		$this->__data = $data;
 	}
	
	
	public function render() {
// 		set_error_handler(function($errno, $errstr, $errfile, $errline) {
// 			error_log('-'.$errno.'-'.$errstr);
// 			throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
// 		});
		if(!@include($this->action.'.phtml')) {
			throw new \Exception('Missing view:'.$this->action.'.phtml');
		}
//		restore_error_handler();
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
// 			set_error_handler(function($errno, $errstr, $errfile, $errline) {
// 				error_log('-'.$errno.'-'.$errstr);
// 				throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
// 			});
			if(!@include($path.'.phtml')) {
				throw new \Exception('Failed to include: '.$path.'.phtml');	
			}
//			restore_error_handler();
		} else {
			$v = new View($data);
			$v->setActionName($path);
			$v->render();
		}
	}
}