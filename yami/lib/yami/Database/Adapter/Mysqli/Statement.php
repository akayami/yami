<?php
namespace yami\Database\Adapter\Mysqli;

use yami\Database\Statement;

class Statement implements Statement {

	/**
	 *
	 * Enter description here ...
	 * @var mysqli_stmt
	 */
	protected $stmt;

	public function __construct(\mysqli_stmt $stmt) {
		$this->stmt = $stmt;
	}
}