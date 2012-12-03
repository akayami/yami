<?php
namespace yami;

use yami\Cache;

use yami\Database\Adapter;

use yami\Database\Manager;

/**
 *
 * @author tomasz
 *
 *	A hybrid collection/entity object.
 *
 */

abstract class Collection extends \Bacon\Collection {

	static $idField;
	static $table;
	static $cluster = 'default';
	static $readonlyFields = array();
	static $insertFields = array();
	static $updateFields = array();

	/**
	 * Saves item
	 *
	 * @param Adapter $conn
	 */
	public function save(Adapter $conn = null) {
		$adapter = (is_null($adapter) ? static::getCluster()->master() : $adapter);
		if(isset($this[static::$idField])) {
			return self::update($this->getCurrent(), array(self::$idField.'={int:id}', array('id' => $this[static::$idField])));
		} else {
			return self::insert($this->getCurrent());
		}
	}

	/**
	 *
	 * @return \yami\Database\Cluster
	 */
	public static function getCluster() {
		global $config;
		return Manager::singleton()->get(static::$cluster);
	}

	/**
	 *
	 * @param string $extra
	 * @param array $phs
	 * @return \webcams\Collection
	 */
	public static function select($extra, $phs, Cache $cache = null) {
		$q = 'SELECT '.static::$table.'.* FROM '.static::$table.' '.$extra;
		$conn = static::getCluster()->slave();
		if(!is_null($cache)) {
			ksort($phs);
			$key = $q.serialize($phs);
			error_log($key);
			$data = $cache->get($key, function() use ($conn, $q, $phs) {
				error_log('using cache');
				return $conn->pquery($q, $phs)->fetchAll();
			});
		} else {
			$data = $conn->pquery($q, $phs)->fetchAll();
		}
		return new static($data);
	}

	/**
	 *
	 * @param int $id
	 * @return \Bacon\Collection
	 */
	public static function byId($id, Adapter $adapter = null) {
		$adapter = (is_null($adapter) ? static::getCluster()->slave() : $adapter);
		return new static($adapter->pquery('SELECT * FROM `'.static::$table.'` WHERE `'.static::$idField.'`={int:id}', array('id' => $id))->fetch());
	}

	/**
	 *
	 * @param array $data
	 * @param array|int $where
	 * @param Adapter $adapter
	 * @return Adapter
	 */
	public static function update(array $data, $where, Adapter $adapter = null) {
		if(is_null($adapter)) {
			$adapter = static::getCluster()->master();
		}
		if(is_int($where)) {
			$where = array(static::$idField.'={int:id}', array('id' => $where));
		}
		$adapter->update(static::$table, $where, array_intersect_key($data, array_flip(static::$updateFields)), static::getStandardFilteredFields());
		return $adapter;
	}

	/**
	 *
	 * @param array $data
	 * @param Adapter $adapter
	 * @return Adapter
	 */
	public static function insert(array $data, Adapter $adapter = null) {
		if(is_null($adapter)) {
			$adapter = static::getCluster()->master();
		}
		$adapter->insert(static::$table, array_intersect_key($data, array_flip(static::$insertFields)), static::getStandardFilteredFields());
		return $adapter;
	}

	public static function getStandardFilteredFields() {
		return array_merge(array(static::$idField), static::$readonlyFields);
	}
}