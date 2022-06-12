<?php
namespace WP_Core\Model;

/**
 * # Rows Handler
 * 同一张表中，具有共同属性的多行数据操作基类
 *
 * Rows 定义：具有共同属性的并列行数据典型如；
 * - wp_user_meta、wp_post_meta……
 *
 * 约定：共同属性值为 int 类型
 * 作用：主要用于统一读写方法降低代码重复，并统一设置内存缓存（object cache）
 *
 * @since 2022.06.11
 */
abstract class WPDB_Handler_Rows extends WPDB_Handler_Abstract {

	// 共同属性 id 字段名
	protected $object_id_column;

	/**
	 * Retrieves all raws value for the specified object.
	 * @return array|false
	 */
	public function get_rows(int $object_id) {
		$data = $this->get_object_rows_cache($object_id);
		if (false !== $data) {
			return $data;
		}

		global $wpdb;
		$data = $wpdb->get_results(
			$wpdb->prepare("SELECT * FROM {$this->table} WHERE {$this->object_id_column} = %d ORDER BY %s ASC", $object_id, $this->primary_id_column)
		);

		if ($data) {
			$this->set_object_rows_cache($object_id, $data);
		}

		return $data;
	}

	/**
	 * delete specified object all rows
	 *
	 * @return int The number of rows updated
	 */
	public function delete_rows(int $object_id): int {
		global $wpdb;
		$action = $wpdb->delete($this->table, [$this->object_id_column => $object_id]);
		if (!$action) {
			return 0;
		}

		// 依次删除每一行数据对应的缓存
		$old_data = $this->get_rows($object_id);
		foreach ($old_data as $_data) {
			$this->clean_row_cache($_data);
		}

		return $action;
	}

	/**
	 * get single row data object by object ID and row key
	 *
	 * @param $object_id int
	 * @param $where     array  example: ['field' => 'value']
	 *
	 * @return object|false
	 */
	public function get_row(int $object_id, array $where) {
		$data = $this->get_rows($object_id);
		foreach ($data as $row) {

			foreach ($where as $field => $value) {
				if ($value == $row->$field) {
					return $row;
				}
			}

		}

		return false;
	}

	/**
	 * add row data
	 * @return int row id
	 */
	public function add_row(int $object_id, array $data): int{
		$data[$this->object_id_column] = $object_id;
		$id                            = $this->insert($data);
		if ($id) {
			$this->delete_object_rows_cache($object_id);
		}
		return $id;
	}

	/**
	 * update row data
	 * If no value already exists for the specified object ID and rowdata key, the rowdata will be added.
	 * @return int row id
	 */
	public function update_row(int $object_id, array $where, array $data): int{
		$_data = $this->get_row($object_id, $where);
		if (!$_data) {
			return 0;
		}

		$data = array_merge((array) $_data, $data);
		$id   = $this->update($data);
		if ($id) {
			$this->delete_object_rows_cache($object_id);
		}
		return $id;
	}

	/**
	 * delete row data
	 *
	 * @param $object_id int
	 * @param $where     array  example: ['field' => 'value']
	 *
	 * @return int row id
	 */
	public function delete_row(int $object_id, array $where): int{
		$data = $this->get_row($object_id, $where);
		if (!$data) {
			return 0;
		}

		$primary_id_column = $this->primary_id_column;
		$primary_id        = $data->$primary_id_column;

		$primary_id = $this->delete($primary_id);
		if ($primary_id) {
			$this->delete_object_rows_cache($object_id);
		}
		return $primary_id;
	}

	/**
	 * get cache of all rows data for specific object id
	 * @return  mixed
	 */
	private function get_object_rows_cache(int $object_id): mixed {
		return wp_cache_get($object_id, $this->table_name);
	}

	/**
	 * set rows data cache for specific object id
	 * @return  row id
	 */
	private function set_object_rows_cache(int $object_id, array $data): bool {
		return wp_cache_set($object_id, $data, $this->table_name);
	}

	/**
	 * set rows data cache for specific object id
	 * @return  row id
	 */
	private function delete_object_rows_cache(int $object_id): bool {
		return wp_cache_delete($object_id, $this->table_name);
	}
}
