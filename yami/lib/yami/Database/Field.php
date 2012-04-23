<?php
namespace yami\Database;

interface Field {

	public function name();

	public function table();

	public function identifier();

}