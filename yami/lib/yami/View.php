<?php
namespace yami;

use yami\Misc\ArrayObject;

class View extends ArrayObject {

	protected $action;

 	public function __construct(array $data = array()) {
 		$this->__data = $data;
 	}

 	public function get($key, $default = null) {
 		if(parent::offsetExists($key)) {
 			return parent::offsetGet($key);
 		} elseif(!is_null($default)) {
 			return $default;
 		} else {
 			throw new \Exception('Undefined index: '.$key);
 		}
 	}

	public function render() {
		ob_start();
		if(!include($this->action.'.phtml')) {
			throw new \Exception('Missing view:'.$this->action.'.phtml');
		}
		$out = ob_get_contents();
		ob_end_clean();
		echo $out;
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
			if(!include($path.'.phtml')) {
				throw new \Exception('Failed to include: '.$path.'.phtml');
			}
		} else {
			$v = new Static($data);
			$v->setActionName($path);
			$v->render();
		}
	}
}