<?php
namespace yami\Database\Result;

use yami\Database\Result;

abstract class CommonResult implements Result {

	protected $fetchMode = 1;

	public function fetchMode($arg) {
		$this->fetchMode = $arg;
	}


}