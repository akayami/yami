<?php
namespace yami;

abstract class ArrayObject implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable {
	
	protected $data = array();

	
	public function toArray(array $filter = null) {
		if(!is_null($filter)) {
			return array_intersect_key($this->data, array_flip($filter));
		} else {
			return $this->data;
		}
	}
	
	public function setArray(array $data) {
		$this->data = $data;
	}
	
	/**
	 * 
	 * @param array $data
	 * @param boolean $overwrite
	 * @return static
	 */
	public function appendArray(array $data, $overwrite = false) {
		if($overwrite) {
			$this->data = array_merge($data, $this->data);
		} else {
			$this->data = array_merge($this->data, $data);
		}
		return $this;
	}
	
	public function __set($key, $val) {
		$this[$key] = $val;
	}
	
	public function __get($key) {
		return $this[$key];
	}
	
	public function __isset($key) {
		return isset($this[$key]);
	}
	
	public function __unset($key) {
		unset($this[$key]);
	}
	
	public function offsetExists($offset) {
		return isset($this->data[$offset]);
	}
	
	public function offsetGet($offset) {
		return $this->data[$offset];
	}
	
	public function offsetSet($offset, $value) {
		return $this->data[$offset] = $value;
	}
	
	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}
	
	public function count() {
		return count($this->data);
	}
	
	public function serialize() {
		return serialize($this->data);
	}
	
	public function unserialize($serialized) {
		$this->data = unserialize($serialized);
	}
	
	public function getIterator() {
		return new AppendIterator($this);
	}	
	
}