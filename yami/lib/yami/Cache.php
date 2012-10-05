<?php
namespace yami;

interface Cache {
	
	public function put($key, $value, $TTL = null, $realTTL = null);
	
	public function get($key, $callback);
	
	public function delete($key);
	
}