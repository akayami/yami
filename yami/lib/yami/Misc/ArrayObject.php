<?php
namespace yami\Misc;

abstract class ArrayObject implements \IteratorAggregate, \ArrayAccess, \Serializable, \Countable {
	
	protected $__data = array();
	
	public function toArray(array $filter = null) {
		if(!is_null($filter)) {
			return array_intersect_key($this->__data, array_flip($filter));
		} else {
			return $this->__data;
		}
	}
	
	/**
	 * 
	 * @param array $data
	 * @return array 
	 */
	public function exchangeArray(array $data) {
		$old = $this->__data;
		$this->__data = $data;
		return $old;
	}
	
	/**
	 * 
	 * @param array $data
	 * @param boolean $overwrite
	 * @return static
	 */
	public function appendArray(array $data, $overwrite = false) {
		if($overwrite) {
			$this->__data = array_merge($data, $this->__data);
		} else {
			$this->__data = array_merge($this->__data, $data);
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
		return isset($this->__data[$offset]);
	}
	
	public function offsetGet($offset) {
		return $this->__data[$offset];
	}
	
	public function offsetSet($offset, $value) {
		return $this->__data[$offset] = $value;
	}
	
	public function offsetUnset($offset) {
		unset($this->__data[$offset]);
	}
	
	public function count() {
		return count($this->__data);
	}
	
	public function serialize() {
		return serialize($this->__data);
	}
	
	public function unserialize($serialized) {
		$this->__data = unserialize($serialized);
	}
	
	public function getIterator() {
		return new AppendIterator($this);
	}	
	
}