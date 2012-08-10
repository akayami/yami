<?php
namespace yami\Router;


interface Route {
	
	/**
	 * @param $request route
	 * 
	 * @return boolean
	 */
	public function isValid($route);
	
	
	
	/**
	 * @return array
	 */
	public function getParams();
	
	public function getController();
	
	public function getAction();
}