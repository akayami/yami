<?php
namespace yami\Router\Action;

use yami\View;
use yami\Router\Controller as AppController;

class Controller {

	/**
	 * 
	 * @var yami\View
	 */
	public $view;
	public $action;
	protected $render = true;

	/**
	 * Constructs the most basic controller suppored by yami. Passes action name
	 * 
	 * @param string $action
	 */
	public function __construct($action = null) {
		$this->action = $action;
		$this->view = new View();
		$this->setActionName(AppController::getInstance()->route->getAction());
		
	}
	
	public function setActionName($name) {
		$this->view->setActionName(str_replace('\\', DIRECTORY_SEPARATOR, AppController::getInstance()->route->getController().'\\'.$name));
	}
	
	public function disableViewRendering() {
		$this->render = false;
	}
	
	public function enableViewRendering() {
		$this->render = true;
	}
	
	public function render() {
		if($this->render) {
			$this->view->render();
		}
	}	
	
	/**
	 * @todo Option to render on destuct. May or may not be benefical. To be evaluated.
	 */
	public function __destruct() {
		//		$this->render();
	}	
}