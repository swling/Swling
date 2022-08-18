<?php
namespace WP_Core\Model;

use WP_Core\Utility\Singleton_Trait;

/**
 * # Meta Handler
 *
 * 对应 table 及 hook
 * - postmeta
 * - termmeta
 * - usermeta
 * - commentmeta
 *
 * ## 注意
 * meta 统一按 $object_id_column 缓存获取"组数据”，不单独按字段名缓存“行数据”
 * 故此设置 $this->object_cache_fields = [$this->primary_id_column] 仅缓存主键，用作更新删除等操作;
 * !与 WordPress 不同，本框架同一 object id 不支持多个重名 meta key
 */
class WPDB_Handler_Meta extends WPDB_Rows {

	protected $table_name;
	protected $object_name;
	protected $primary_id_column;
	protected $required_columns    = [];
	protected $object_cache_fields = [];

	protected $object_id_column;

	use Singleton_Trait;

	protected function check_insert_data(array $data): array{
		return $data;
	}

	protected function check_update_data(array $data): array{
		return $data;
	}

	/**
	 * init meta type
	 * - postmeta、termmeta、usermeta、commentmeta
	 */
	public function set_meta_type(string $meta_type) {
		$this->primary_id_column   = ('user' === $meta_type) ? 'umeta_id' : 'meta_id';
		$this->table_name          = $meta_type . 'meta';
		$this->object_name         = $this->table_name;
		$this->object_id_column    = $meta_type . '_id';
		$this->required_columns    = [$this->object_id_column, 'meta_key', 'meta_value'];
		$this->object_cache_fields = [$this->primary_id_column];

		$this->instance_wpdb();
		$this->instance_wpdb_row();
	}

	/**
	 * get single meta data object by object ID and meta key
	 * @return object|false
	 */
	public function get_meta(int $object_id, string $meta_key) {
		return $this->get_row($object_id, ['meta_key' => $meta_key]);
	}

	/**
	 * get single meta value by object ID and meta key
	 * @return mixed|false
	 */
	public function get_meta_value(int $object_id, string $meta_key) {
		$meta = $this->get_meta($object_id, $meta_key);
		return $meta ? $meta->meta_value : false;
	}

	/**
	 * add meta data
	 * @return int meta id
	 */
	public function add_meta(int $object_id, string $meta_key, $meta_value): int{
		// meta already exists
		$data = $this->get_meta($object_id, $meta_key);
		if ($data) {
			return $this->update_meta($object_id, $meta_key, $meta_value);
		}

		$data = [
			$this->object_id_column => $object_id,
			'meta_key'              => $meta_key,
			'meta_value'            => $meta_value,
		];

		return $this->add_row($object_id, $data);
	}

	/**
	 * update meta data
	 * If no value already exists for the specified object ID and metadata key, the metadata will be added.
	 * @return int meta id
	 */
	public function update_meta(int $object_id, string $meta_key, $meta_value): int{
		$data = $this->get_meta($object_id, $meta_key);
		if (!$data) {
			return $this->add_meta($object_id, $meta_key, $meta_value);
		}

		// 数据相同
		if ($data->meta_value === $meta_value || (maybe_serialize($data->meta_value) === maybe_serialize($meta_value))) {
			return 0;
		}

		$data               = (array) $data;
		$data['meta_value'] = $meta_value;

		return $this->update_row($object_id, ['meta_key' => $meta_key], $data);
	}

	/**
	 * delete meta data
	 * @return int meta id
	 */
	public function delete_meta(int $object_id, string $meta_key): int {
		return $this->delete_row($object_id, ['meta_key' => $meta_key]);
	}
}
