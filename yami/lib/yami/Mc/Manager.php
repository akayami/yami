<?php
namespace yami\Mc;

class Manager {

	/**
	 * 
	 * @var Manager
	 */
	private static $instance;
	private static $config;
	private static $clusters = array();
	
	private function __construct() {
		if(!isset(static::$config)) {			// Lazy self-provisioning.
			global $config;
			static::setConfig($config['mc']);
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
	 * @return Manager
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
	 * @return \Memcached
	 * @throws Exception
	 */
	public function get($cluster = 'default') {
		if(isset(static::$clusters[$cluster])) {
			return static::$clusters[$cluster];
		}
		if(isset(static::$config[$cluster])) {
			static::$clusters[$cluster] = new \Memcached();
			if(static::$clusters[$cluster]->addServers(static::$config[$cluster])) {
				return static::$clusters[$cluster];
			} else {
				throw new \Exception('Error adding MC cluster '.$cluster.'. Misconfiguration ?');
			}
		} else {
			throw new \Exception('MC Cluster '.$cluster.' not defined!');
		}
	}	
	
}