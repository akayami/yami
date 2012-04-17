<?php
namespace yami;

class View extends ArrayObject {
	
	public $filePath;
	
	public function __construct(array $data = array()) {
		$this->data = $data;	
	}
}