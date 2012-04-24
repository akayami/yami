<?php
namespace yami\Database\Adapter;

use yami\Database\Adapter\Mysqli\Result;

class Mysqli extends Abstr {

	protected $config;

	/**
	 *
	 * Enter description here ...
	 * @var mysqli
	 */
	protected $__handle;

	protected $__type;

	public function __construct(array $config, $type = 'master') {
		$this->config = $config;
		$this->__type = $type;

	}

	public function __get($key) {
		switch($key) {
			case 'handle':
				if(!isset($this->__handle)) {
					$config = $this->config;
					$this->__handle = new \mysqli($config['hostname'], $config['username'], $config['password'], $config['dbname'], $config['port'], $config['socket']);
					if($this->__handle->connect_errno > 0) {
						throw new \Exception($this->__handle->connect_error, $this->__handle->connect_errno);
					}
				}
				return $this->__handle;
				break;
		}
	}

	/**
	 *
	 * @param string $query
	 * @return Result
	 */
	public function query($query) {
		$res = $this->handle->query($query);
		if(is_bool($res)) {
			if($res === false) {
				throw new \Exception($this->handle->error);
			}
			return $res;
		}
		return new Result($res, $this->handle);
	}

	public function transaction() {
		$this->handle->autocommit(false);
	}

	public function commit() {
		$this->handle->commit();
		$this->handle->autocommit(true);
	}

	public function rollback() {
		$this->handle->rollback();
		$this->handle->autocommit(true);
	}

	public function quote($string) {
		return "'".$this->handle->real_escape_string($string)."'";
	}

	public function quoteIdentifier($string) {
		return '`'.$string.'`';
	}

	public function getLastInsertID() {
		return $this->handle->insert_id;
	}

	public function affectedRows() {
		return $this->handle->affected_rows;
	}
}