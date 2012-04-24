<?php
namespace yami\Database\Result;

use yami\Database\Result;

abstract class CommonResult implements Result {

	const FETCH_ASSOC = 1;
	const FETCH_NUM = 2;
	const FETCH_BOTH = 3;

	protected $fetchMode = 1;

	public function fetchMode($arg) {
		$this->fetchMode = $arg;
	}


}