<?php
namespace yami\Database\Adapter\PDO;

class Statement implements Statement {

	/**
	 *	
	 * 
	 * @var \PDOStatement
	 */
	protected $stmt;

	public function __construct(\PDOStatement $stmt) {
		$this->stmt = $stmt;
	}
}