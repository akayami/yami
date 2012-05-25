<?php
namespace yami\ORM\Backend;
use yami\ORM\Backend;

use yami\Misc\Singleton;

class Manager extends Singleton {

	protected static $instance;	
	protected static $config;
	protected static $backends = array();
	
	protected function __construct() {
		if(!isset(static::$config)) {			// Lazy self-provisioning.
			global $config;
			if(isset($config['backend'])) {
				static::setConfig($config['backend']);
			} else {
				throw new \Exception('Config not set');
			}
		}
	}
	
	/**
	 * @return \yami\ORM\Backend\Manager
	 */
	static public function getInstance() { 
		return parent::getInstance();
	}
	
	/**
	 * Set configuration
	 * @param array $config
	 */
	public static function setConfig(array $config = array()) {
		static::$config = $config;
	}
	
	/**
	 * Return a Backend
	 * @param string $name
	 * @return yami\ORM\Backend
	 * @throws \Exception 
	 */
	public function get($name = 'default') {
		if(!isset(static::$backends[$name])) {
			if(isset(static::$config[$name])) {
				static::$backends[$name] = $this->getBackend(static::$config[$name]);
			} else {
				throw new \Exception('Backend '.$name.' not configured');
			}
		}
		return static::$backends[$name];
	}
	
	/**
	 * Return backend
	 * 
	 * @param string $config
	 * @return \yami\ORM\Backend
	 */
	public function getBackend($config) {
		$backend = new $config['backend']($config['manager']::singleton()->get($config['namespace']));
		if(isset($config['child'])) {
			$backend->setChildBackend($this->getBackend($config['child']));
		}
		return $backend;
	}
	
}