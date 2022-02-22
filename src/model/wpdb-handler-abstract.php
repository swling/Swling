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

	public function insert(array $data): int{
		$this->check_data($data, false);

		if (isset($data[$this->primary_id_column])) {
			return $this->update_db($data, [$this->primary_id_column => $data[$this->primary_id_column]]);
		} else {
			return $this->insert_db($data);
		}
	}

	public function get(int $ID): object{
		$post = $this->get_db([$this->primary_id_column => $ID]);
		return $post;
	}

	public function update(array $data): int{
		$this->check_data($data, true);

		return $this->insert_db($data);
	}

	public function delete(int $ID): int {
		return $this->delete_db([$this->primary_id_column => $ID]);
	}

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

	private function insert_db(array $data, array $format = []): int{
		$data = apply_filters("insert_{$this->object_name}_data", $data);

		do_action("before_insert_{$this->object_name}", $data);

		$insert = $this->wpdb->insert($this->table, $data, $format);

		if ($insert) {
			do_action("after_insert_{$this->object_name}", $this->wpdb->insert_id, $data);
		}

		return $this->wpdb->insert_id;
	}

	// 需要设置安全过滤 prepare （未完成）
	private function get_db(array $where) {
		// 数据钩子，可用于对象缓存拦截sql查询
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

			$conditions[] = "`$field` = " . $value;
		}

		$conditions = implode(' AND ', $conditions);
		$sql        = "SELECT * FROM `$this->table` WHERE $conditions";
		$data       = $this->wpdb->get_row($sql);

		// Action 获取成功：可用于设置对象缓存
		if ($data) {
			do_action("get_{$this->object_name}_data_success", $data, $where);
		}

		return $this->wpdb->get_row($sql);
	}

	private function update_db(array $data, array $where, array $format = [], array $where_format = []) {
		$data = apply_filters("update_{$this->object_name}_data", $data);

		do_action("before_update_{$this->object_name}", $data);

		$update = $this->wpdb->update($this->table, $data, $where, $format, $where_format);

		if ($update) {
			do_action("after_update_{$this->object_name}", $data, $where);
		}

		return $update;
	}

	private function delete_db(array $where, array $where_format = []) {
		do_action("before_delete_{$this->object_name}", $where);

		$delete = $this->wpdb->delete($this->table, $where, $where_format);

		if ($delete) {
			do_action("after_delete_{$this->object_name}", $where);
		}

		return $delete;
	}
}
