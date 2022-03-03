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
	protected $required_columns    = [];
	protected $object_cache_fields = [];

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

		$this->_check_insert_data($data);
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
		return $this->get_by($this->primary_id_column, $ID);
	}

	/**
	 * get data by column
	 *（需要完善对象缓存，对于复杂查询统一调用 WP_XXX_Query，并在 WP_XXX_Query 中统一缓存）
	 * @return object|false
	 */
	public function get_by(string $field, $value) {
		$data = apply_filters("get_{$this->object_name}_data", false, $field, $value);
		if (false !== $data) {
			return $data;
		}

		// object cache
		$data = $this->maybe_get_data_from_cache($field, $value);
		if (false !== $data) {
			return $data;
		}

		// sql 查询
		if (is_null($value)) {
			$conditions = "`$field` IS NULL";
		} else {
			$conditions = "`$field` = " . "'{$value}'";
		}

		$sql  = "SELECT * FROM `$this->table` WHERE $conditions";
		$data = $this->wpdb->get_row($sql);

		// get data success
		if ($data) {
			$this->maybe_set_data_into_cache($field, $value, $data);

			do_action("get_{$this->object_name}_data_success", $data, $field, $value);
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

		$ID            = $data[$this->primary_id_column] ?? 0;
		$object_before = $this->get($ID);
		$data          = array_merge((array) $object_before, $data);
		$this->_check_update_data($data);

		$where  = [$this->primary_id_column => $ID];
		$update = $this->wpdb->update($this->table, $data, $where);
		if ($update) {
			$this->clean_table_cache($object_before);

			$object_after = $this->get($ID);
			do_action("after_{$this->object_name}_updated", $ID, $object_after, $object_before);
		}

		return $update ? $ID : 0;
	}

	/**
	 * delete data by primary id
	 *
	 * @return int The primary id on success. The value 0 on failure.
	 */
	public function delete(int $ID): int{
		$data = $this->get($ID);
		if (!$data) {
			return 0;
		}
		do_action("before_delete_{$this->object_name}", $data, $ID);

		$where  = [$this->primary_id_column => $ID];
		$delete = $this->wpdb->delete($this->table, $where);
		if ($delete) {
			$this->clean_table_cache($data);

			do_action("after_{$this->object_name}_deleted", $data, $ID);
		}

		return $delete ? $ID : 0;
	}

	/**
	 * check insert data
	 * @access private
	 */
	private function _check_insert_data(array $data) {
		if (!$this->required_columns) {
			throw new Exception('Required columns have not been initialized');
		}

		foreach ($this->required_columns as $column) {
			if (!isset($data[$column]) or !$data[$column]) {
				throw new Exception('Required columns are empty');
			}
		}

		$this->check_insert_data($data);
	}

	/**
	 * check insert data
	 */
	abstract protected function check_insert_data(array $data);

	/**
	 * check update data
	 * @access private
	 */
	private function _check_update_data(array $data) {
		$ID = $data[$this->primary_id_column] ?? 0;
		if (!$ID) {
			throw new Exception('Primary ID column are empty on update: ' . $this->primary_id_column);
		}

		if (!$this->get($ID)) {
			throw new Exception('Primary ID is invalid');
		}

		$this->check_update_data($data);
	}

	/**
	 * check update data
	 */
	abstract protected function check_update_data(array $data);

	/**
	 * maybe get data from cache
	 */
	private function maybe_get_data_from_cache(string $field, $value) {
		if (!in_array($field, $this->object_cache_fields)) {
			return false;
		}

		return wp_cache_get($value, $this->table_name . ':' . $field);
	}

	/**
	 * maybe set data into cache
	 */
	private function maybe_set_data_into_cache(string $field, $value, $data) {
		if (!in_array($field, $this->object_cache_fields)) {
			return false;
		}

		return wp_cache_set($value, $data, $this->table_name . ':' . $field);
	}

	/**
	 * clean table cache When a row is deleted or updated
	 */
	protected function clean_table_cache(object $old_data) {
		foreach ($old_data as $field => $value) {
			if (in_array($field, $this->object_cache_fields)) {
				wp_cache_delete($value, $this->table_name . ':' . $field);
			}
		}

		$this->refresh_db_table_last_changed();
	}

	/**
	 * Refresh last changed date for DB Table
	 */
	private function refresh_db_table_last_changed(): bool {
		return wp_cache_delete_last_changed($this->table_name);
	}

	/**
	 * Gets last changed date for the current DB table.
	 *
	 * @param string $group Where the cache contents are grouped.
	 * @return string UNIX timestamp with microseconds representing when the group was last changed.
	 */
	public function get_current_db_table_last_changed(): string {
		return wp_cache_get_last_changed($this->table_name);
	}
}
