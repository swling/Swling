<?php

namespace Model;

/**
 * 本类操作均针对单行
 */
abstract class WPDB_Handler_Abstract {

	protected $table_name;
	protected $object_name;
	protected $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		$table_name  = $this->table_name;
		$this->table = $wpdb->$table_name;
	}

	final public function insert(array $data, array $format = []): int{
		$data = apply_filters("insert_{$this->object_name}_data", $data);

		do_action("before_insert_{$this->object_name}", $data);

		$insert = $this->wpdb->insert($this->table, $data, $format);

		if ($insert) {
			do_action("after_insert_{$this->object_name}", $this->wpdb->insert_id, $data);
		}

		return $this->wpdb->insert_id;
	}

	// 需要设置安全过滤 prepare （未完成）
	final public function get(array $where) {
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

	final public function update(array $data, array $where, array $format = [], array $where_format = []) {
		$data = apply_filters("update_{$this->object_name}_data", $data);

		do_action("before_update_{$this->object_name}", $data);

		$update = $this->wpdb->update($this->table, $data, $where, $format, $where_format);

		if ($update) {
			do_action("after_update_{$this->object_name}", $data, $where);
		}

		return $update;
	}

	final public function delete(array $where, array $where_format = []) {
		do_action("before_delete_{$this->object_name}", $where);

		$delete = $this->wpdb->delete($this->table, $where, $where_format);

		if ($delete) {
			do_action("after_delete_{$this->object_name}", $where);
		}
	}
}
