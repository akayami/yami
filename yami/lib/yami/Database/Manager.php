<?php
namespace yami\Database;

class Manager {

	private static $instance;
	private static $config;
	private static $clusters = array();

	private function __construct() {
		if(!isset(static::$config)) {			// Lazy self-provisioning.
			if(class_exists('\Bacon\Config', true) && \Bacon\Config::isInitialized()) {
				error_log(print_r(\Bacon\Config::getInstance(), true));
				static::setConfig(\Bacon\Config::getInstance()['db']);
			} else {
				global $config;
				static::setConfig($config['db']);
			}
		}
	}

	public function __clone() {
		trigger_error('Clone is not allowed.', E_USER_ERROR);
	}

	public static function setConfig(array $config) {
		static::$config = $config;
	}

	/**
	 * Enter description here ...
	 *
	 * @return \yami\Database\Manager
	 */
	public static function singleton() {
		if(!isset(static::$instance)) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 *
	 * Enter description here ...
	 * @param string $cluster
	 * @return Cluster
	 * @throws Exception
	 */
	public function get($cluster = 'default') {
		if(isset(static::$clusters[$cluster])) {
			return static::$clusters[$cluster];
		}
		if(isset(static::$config[$cluster])) {
			static::$clusters[$cluster] = new Cluster(static::$config[$cluster]);
			return static::$clusters[$cluster];
		} else {
			throw new \Exception('Cluster '.$cluster.' not defined!');
		}
	}

}