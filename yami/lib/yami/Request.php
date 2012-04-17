<?php
namespace yami;

class Request extends ArrayObject {

	protected static $instance;
	protected $uri = array();
	protected $isUriSet = false;
	
	/**
	 * @return yami\Request
	 */
	static public function getInstance() {
		if(!isset(static::$instance)) {
			static::$instance = new static();
		}
		return static::$instance;
	}
	
	protected function __construct() {
		$this->data = array_merge($_SERVER, $_POST, $_GET, $_COOKIE, $_REQUEST, $this->uri, $this->data);
	}	
	
	public function __clone() {
		throw new Exception('Cannot clone a singleton:'.get_called_class());
	}
	
	public function __wakeup() {
		throw new Exception('Unserializing is not allowed for singleton:'.get_called_class());
	}
	
	/**
	 * 
	 * @param string $key
	 * @param string $source
	 */
	public function has($key, $source = null) {
				
		switch($source) {
			case 'request':
				return isset($_REQUEST[$key]);
				break;
			case 'get':
				return isset($_GET[$key]);
				break;
			case 'post':
				return isset($_POST[$key]);
				break;
			case 'cookie':
				return isset($_COOKIE[$key]);
				break;
			case 'uri':
				return isset($this->uri[$key]);
				break;
			default:
				return isset($this->data[$key]);
		}
	}
	
	/**
	 * 
	 * @param string $key
	 * @param string $default
	 * @param string $source
	 */
	public function get($key, $default = null, $source = null, $sanitize = true) {
		if($this->has($key, $source)) {
			switch($source) {
				case 'request':
					return $_REQUEST[$key];
					break;
				case 'get':
					return $_GET[$key];
					break;
				case 'post':
					return $_POST[$key];
					break;
				case 'cookie':
					return $_COOKIE[$key];
					break;
				case 'uri':
					return $this->data[$key];
					break;
				default:					
					return (isset($_REQUEST[$key]) || isset($_GET[$key]) || isset($_POST[$key]) || isset($_COOKIE[$key]) || isset($this->data[$key]));
			}
		} else {
			return $default;
		}
	}
	
	/**
	 * 
	 * @param string $key
	 * @param string $value
	 * @param string $source
	 * @param boolean $overwriteURI
	 * @throws Exception
	 */
	public function set($key, $value, $source = null, $allowURI = false) {
		switch($source) {
			case 'uri':
				if(!$overwriteURIBlock) {
					throw new Exception('Cannot set uri type');	
				} else {
					$this->uri[$key] = $value;
					$this->data[$key] = $value;
				}
				break;
			case 'request':
				throw new Exception('Cannot set request type');				
			case 'get':
				throw new Exception('Cannot set get type');
			case 'post':
				throw new Exception('Cannot set post type');
			case 'cookie':
				throw new Exception('Cannot set cookie type');
			default:
				$this->data[$key] = $value;
		}
	}
	
	public function setURI(array $uri = array()) {
		if($this->isUriSet === true) {
			throw new \Exception('uri namespace already set');
		} else {
			$this->uri = $uri;
		}
		$this->data = array_merge($_POST, $_GET, $_COOKIE, $_REQUEST, $this->uri, $this->data);
	}
}