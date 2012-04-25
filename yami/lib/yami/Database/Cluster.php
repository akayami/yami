<?php
namespace yami\Database;

class Cluster {
	
	private $config;
	
	private $instances = array();
	
	private $configTemplate = array(
			'hostname' => '',
			'username' => '',
			'password' => '',
			'port' => 3306,
			'dbname' => 'dbname',
			'socket' => '',
			'persistent' => false, 
			'adapter' => 'yami\Database\Adapter\Mysqli'		
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
	* @return \yami\Database\Adapter
	*/
	public function master($new = false) {
		return $this->get('master', $new);
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param boolean $new
	 * @return \yami\Database\Adapter
	 */
	public function slave($new = false) {
		return $this->get('slave', $new);
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param string $type
	 * @param boolean $new
	 * @return \yami\Database\Adapter
	 * @throws \Exception
	 */
	public function get($type = 'slave', $new = false) {
		if(isset($this->instances[$type]) && $new === false) {
			return $this->instances[$type];
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
		do {
			$key = $type.'_'.md5(mt_rand(1, 10000).time());
		} while(!isset($key) || isset($this->instances[$type][$key]));
		$this->instances[$type][$key] = new $config['adapter']($config, $type);
		return $this->instances[$type][$key];
	}
}