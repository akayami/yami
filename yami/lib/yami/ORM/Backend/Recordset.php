<?php
namespace yami\ORM\Backend;

class Recordset extends \ArrayObject {
	
	public $totalCount;
	public $idRecordMap;
	
	public function __construct(array $data = array(), $totalCount = null) {
		parent::__construct($data);
		$this->totalCount = $totalCount;
	}
}