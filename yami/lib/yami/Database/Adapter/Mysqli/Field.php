<?php
namespace yami\Database\Adapter\Mysqli;

use yami\Database\Field as FieldInterface;

class Field implements FieldInterface {

	protected $mysqliField;

	public function __construct($field) {
		$this->mysqliField = $field;
	}

	public function name() {
		return $this->mysqliField->name;
	}

	public function table() {
		return $this->mysqliField->orgtable;
	}

	public function identifier() {
		return strlen($this->table()) > 0 && $this->table() != 'COLUMNS' ? $this->table().'.'.$this->name() : $this->name();
		//		return $this->table().'.'.$this->name();
	}
}