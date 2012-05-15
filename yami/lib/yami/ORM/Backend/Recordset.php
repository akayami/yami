<?php
namespace yami\ORM\Backend;

class Recordset extends \ArrayObject {
	
	public $count;
	
	public function __construct(array $data = array(), $totalCount = null) {
		parent::__construct($data);
		$this->count = $totalCount;
	}
	
}