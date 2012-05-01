<?php
namespace yami\Redis;

class Cluster {

	private $config;
	
	private $instances = array();
	
	private $configTemplate = array(
			'hostname' => '',
			'port' => 6379,
			'timeout' => 2,
			'socket' => '',
			'persistent' => false,
	);
	
	/**
	 *
	 * Enter description here ...
	 * @param array $config
	 */
	public function __construct(array $config) {
		$this->config = $config;
	}
	
	/**
	 *
	 * Enter description here ...
	 * @param boolean $new
	 * @return \Redis
	 */
	public function master($new = false) {
		return $this->get('master', $new);
	}
	
	/**
	 *
	 * Enter description here ...
	 * @param boolean $new
	 * @return \Redis
	 */
	public function slave($new = false) {
		return $this->get('slave', $new);
	}
	
	/**
	 *
	 * Enter description here ...
	 * @param string $type
	 * @param boolean $new
	 * @return \Redis
	 * @throws \Exception
	 */
	public function get($type = 'slave', $new = false) {
		if(isset($this->instances[$type]) && $new === false) {
			return $this->instances[$type][$type.'_default'];
		}
		if(!isset($this->config[$type])) {
			throw new \Exception('Connection '.$type.' not defined');
		}
		$config = $this->configTemplate;
		if(isset($this->config[$type]['shared'])) {
			$config = array_merge($config, $this->config[$type]['shared']);
		}
		$x = (sizeof($this->config[$type]['servers']) > 1 ? mt_rand(0, sizeof($this->config[$type]['servers']) - 1) : 0);
		$config = array_merge($config, $this->config[$type]['servers'][$x]);
		if(!isset($this->instances[$type])) {
			$key = $type.'_default';
		} else {					
			do {
				$key = $type.'_'.md5(mt_rand(1, 10000).time());
			} while(!isset($key) || isset($this->instances[$type][$key]));
		}
		
		$this->instances[$type][$key] = $this->getRedis($config);
		
		return $this->instances[$type][$key];
	}
	
	/**
	 *  
	 * @param array $config
	 * @return \Redis
	 * 
	 */
	private function getRedis($config) {
		$a = new \Redis();
		(strlen($config['socket']) == 0) ? ($config['persistent'] ? $a->pconnect($config['hostname'], $config['port'], $config['timeout']) : $a->connect($config['host'], $config['port'], $config['timeout'])) : $a->connect($config['socket']);
		return $a;		
	}
	
}