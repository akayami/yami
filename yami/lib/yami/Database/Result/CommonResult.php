<?php
namespace yami\Database\Result;

use yami\Database\Result;

abstract class CommonResult implements Result {

	protected $fetchMode = 1;

	public function fetchMode($arg) {
		$this->fetchMode = $arg;
	}

	/**
	 * (non-PHPdoc)
	 * @see \yami\Database\Result::fetchCol()
	 */
	public function fetchCol($columnName) {
		$output = array();
		while($row = $this->fetch()) {
			if(isset($row[$columnName])) {
				$output[] = $row[$columnName];
			} else {
				throw new \Exception('Specified column name not found:'.$columnName);
			}
		}
		return $output;
	}

}