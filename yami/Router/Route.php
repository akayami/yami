<?php
namespace yami\Router;

use yami\Request;

interface Route {
	
	/**
	 * @param Request route
	 * 
	 * @return boolean
	 */
	public function isValid(Request	$route);
	
	
	
	/**
	 * @return array
	 */
	public function getParams();
	
	
	public function handle();
}