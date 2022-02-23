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

		$this->check_data($data, false);
		$insert = $this->wpdb->insert($this->table, $data);
		if ($insert) {
			do_action("after_insert_{$this->object_name}", $this->wpdb->insert_id, $data);
		}

		return $this->wpdb->insert_id;
	}

	/**
	 * get data by primary id
	 *
	 * @return object data object
	 */
	public function get(int $ID): object{
		$where = [$this->primary_id_column => $ID];
		return $this->get_by($where);
	}

	/**
	 * get data by column
	 *
	 * @return object data object
	 */
	public function get_by(array $where): object{
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
		$data       = (object) $this->wpdb->get_row($sql);

		// Action 获取成功：可用于设置对象缓存
		if ($data) {
			do_action("get_{$this->object_name}_data_success", $data, $where);
		}

		return $data;
	}

	/**
	 * update data by primary id
	 *
	 * @return int — The number of rows updated, 0 on error.
	 */
	public function update(array $data): int{
		$data = apply_filters("update_{$this->object_name}_data", $data);
		do_action("before_update_{$this->object_name}", $data);

		$this->check_data($data, true);
		$where  = [$this->primary_id_column => $data[$this->primary_id_column]];
		$update = $this->wpdb->update($this->table, $data, $where);
		if ($update) {
			do_action("after_update_{$this->object_name}", $data, $where);
		}

		return $update;
	}

	/**
	 * delete data by primary id
	 *
	 * @return int — The number of rows updated, 0 on error.
	 */
	public function delete(int $ID): int{
		$where = [$this->primary_id_column => $ID];
		do_action("before_delete_{$this->object_name}", $where);

		$delete = $this->wpdb->delete($this->table, $where);
		if ($delete) {
			do_action("after_delete_{$this->object_name}", $where);
		}

		return $delete;
	}

	/**
	 * check data
	 *
	 */
	private function check_data(array $data, bool $is_update) {
		if ($is_update) {
			if (!isset($post_data[$this->primary_id_column])) {
				throw new Exception('Primary ID column are empty on update');
			}

			return;
		}

		if (!$this->required_columns) {
			throw new Exception('Required columns have not been initialized');
		}

		foreach ($this->required_columns as $column) {
			if (!isset($data[$column]) or !$data[$column]) {
				throw new Exception('Required columns are empty');
			}
		}
	}
}
