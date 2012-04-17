<?php
namespace yami;

class View extends ArrayObject {
	
	protected $file;
	
	public function __construct(array $data = array()) {
		$this->data = $data;	
	}
	
	public function render() {
		require($this->file.'.phtml');
	}
	
	public function setView($string) {
		$this->file = $string;
	}
}