<?php
namespace yami\ORM\Backend;

class Exception extends \Exception {
	
	protected $key;
	
	public function __construct($key = null, $message = null, $code = null, $previous = null) {
		parent::__construct($message, $code, $previous);
		$this->key = $key;
	}
	
	public function getKey() {
		return $this->key;
	}
	
}