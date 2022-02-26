<?php
namespace Model;

use Exception;

/**
 * 单行数据表操作抽象基类
 * - 本类操作均针对单行
 * - 在 wpdb 的基础上统一添加 Hook
 */
abstract class WPDB_Handler_Abstract {

	protected $wpdb;
	protected $table_name;
	protected $object_name;
	protected $primary_id_column;
	protected $required_columns = [];

	protected static $last_changed_key = 'last_changed';

	/**
	 * Constructer
	 *
	 * Init
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		$table_name  = $this->table_name;
		$this->table = $wpdb->$table_name;
	}

	/**
	 * insert data
	 *
	 * @return int primary id
	 */
	public function insert(array $data): int {
		// update
		if (isset($data[$this->primary_id_column])) {
			return $this->update($data);
		}

		$data = apply_filters("insert_{$this->object_name}_data", $data);
		do_action("before_insert_{$this->object_name}", $data);

		$this->check_insert_data($data);
		$insert = $this->wpdb->insert($this->table, $data);
		if ($insert) {
			$this->refresh_db_table_last_changed();

			do_action("after_{$this->object_name}_inserted", $this->wpdb->insert_id, $data);
		}

		return $this->wpdb->insert_id;
	}

	/**
	 * get data by primary id
	 *
	 * @return object|false
	 */
	public function get(int $ID) {
		$data = wp_cache_get($ID, $this->table_name);
		if (false !== $data) {
			return $data;
		}

		$data = $this->get_by([$this->primary_id_column => $ID]);
		if ($data) {
			wp_cache_set($ID, $data, $this->table_name);
		}

		return $data;
	}

	/**
	 * get data by column
	 *（需要完善对象缓存，对于复杂查询统一调用 WP_XXX_Query，并在 WP_XXX_Query 中统一缓存）
	 * @return object|false
	 */
	public function get_by(array $where) {
		$data = apply_filters("get_{$this->object_name}_data", false, $where);
		if (false !== $data) {
			return $data;
		}

		// sql 查询
		if (false === $where) {
			return false;
		}

		$conditions = [];
		foreach ($where as $field => $value) {
			if (is_null($value)) {
				$conditions[] = "`$field` IS NULL";
				continue;
			}

			$conditions[] = "`$field` = " . "'{$value}'";
		}
		$conditions = implode(' AND ', $conditions);
		$sql        = "SELECT * FROM `$this->table` WHERE $conditions";
		$data       = $this->wpdb->get_row($sql);

		// get data success
		if ($data) {
			do_action("get_{$this->object_name}_data_success", $data, $where);
		}

		return $data ? (object) $data : false;
	}

	/**
	 * update data by primary id
	 *
	 * @return int The primary id on success. The value 0 on failure.
	 */
	public function update(array $data): int{
		$data = apply_filters("update_{$this->object_name}_data", $data);
		do_action("before_update_{$this->object_name}", $data);

		$this->check_update_data($data);
		$ID            = $data[$this->primary_id_column] ?? 0;
		$object_before = $this->get($ID);

		$where  = [$this->primary_id_column => $ID];
		$update = $this->wpdb->update($this->table, $data, $where);
		if ($update) {
			wp_cache_delete($ID, $this->table_name);
			$this->refresh_db_table_last_changed();

			$object_after = $this->get($ID);
			do_action("after_{$this->object_name}_updated", $ID, $object_after, $object_before, $where);
		}

		return $update ? $ID : 0;
	}

	/**
	 * delete data by primary id
	 *
	 * @return int The primary id on success. The value 0 on failure.
	 */
	public function delete(int $ID): int{
		$where = [$this->primary_id_column => $ID];
		do_action("before_delete_{$this->object_name}", $where);

		$delete = $this->wpdb->delete($this->table, $where);
		if ($delete) {
			wp_cache_delete($ID, $this->table_name);
			$this->refresh_db_table_last_changed();

			do_action("after_{$this->object_name}_deleted", $where);
		}

		return $delete ? $ID : 0;
	}

	/**
	 * check insert data
	 */
	private function check_insert_data(array $data) {
		if (!$this->required_columns) {
			throw new Exception('Required columns have not been initialized');
		}

		foreach ($this->required_columns as $column) {
			if (!isset($data[$column]) or !$data[$column]) {
				throw new Exception('Required columns are empty');
			}
		}
	}

	/**
	 * check update data
	 */
	private function check_update_data(array $data) {
		$ID = $data[$this->primary_id_column] ?? 0;
		if (!$ID) {
			throw new Exception('Primary ID column are empty on update: ' . $this->primary_id_column);
		}

		if (!$this->get($ID)) {
			throw new Exception('Primary ID is invalid');
		}
	}

	/**
	 * Refresh last changed date for DB Table
	 */
	private function refresh_db_table_last_changed() {
		wp_cache_set(static::$last_changed_key, microtime(), $this->table_name);
	}

	/**
	 * Gets last changed date for the current DB table.
	 *
	 * @param string $group Where the cache contents are grouped.
	 * @return string UNIX timestamp with microseconds representing when the group was last changed.
	 */
	public function get_current_db_table_last_changed(): string {
		return static::get_db_table_last_changed($this->table_name);
	}

	/**
	 * Gets last changed date for the specified DB table.
	 *
	 * @param string $group Where the cache contents are grouped.
	 * @return string UNIX timestamp with microseconds representing when the group was last changed.
	 */
	public static function get_db_table_last_changed(string $table_name): string{
		$last_changed = wp_cache_get(static::$last_changed_key, $table_name);

		if (!$last_changed) {
			$last_changed = microtime();
			wp_cache_set(static::$last_changed_key, $last_changed, $table_name);
		}

		return $last_changed;
	}
}
