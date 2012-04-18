<?php
namespace yami\Router\Action;

use yami\View;

class Controller {

	public $action;

	/**
	 * Constructs the most basic controller suppored by yami. Passes action name
	 * 
	 * @param string $action
	 */
	public function __construct($action) {
		$this->action = $action;
	}
}