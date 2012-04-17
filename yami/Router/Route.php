<?php
namespace yami\Router;

interface Route {
	
	/**
	 * @param string route
	 * 
	 * @return boolean
	 */
	public function isValid($route);
	
	
	
	/**
	 * @return array
	 */
	public function getParams();
}