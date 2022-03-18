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
 * 故此设置 $this->object_cache_fields = [];
 * 即：meta 不沿用 WPDB_Handler_Abstract 缓存机制
 * 与 WordPress 不同，本框架同一 object id 不支持多个重名 meta key
 */
class WPDB_Handler_Meta extends WPDB_Handler_Abstract {

	protected $table_name;
	protected $object_name;
	protected $primary_id_column;
	protected $required_columns    = [];
	protected $object_cache_fields = [];

	private $object_id_column;

	use Singleton_Trait;

	protected function check_insert_data(array $data) {}

	protected function check_update_data(array $data) {}

	/**
	 * init meta type
	 * - postmeta、termmeta、usermeta、commentmeta
	 */
	public function set_meta_type(string $meta_type) {
		$this->primary_id_column = ('user' === $meta_type) ? 'umeta_id' : 'meta_id';
		$this->table_name        = $meta_type . 'meta';
		$this->object_name       = $this->table_name;
		$this->object_id_column  = $meta_type . '_id';
		$this->required_columns  = [$this->object_id_column, 'meta_key', 'meta_value'];

		$table_name  = $this->table_name;
		$this->table = $this->wpdb->$table_name;
	}

	/**
	 * query meta data array by object ID
	 * @return array|false
	 */
	public function get_object_meta_data(int $object_id) {
		$data = $this->get_object_meta_cache($object_id);
		if (false !== $data) {
			return $data;
		}

		global $wpdb;
		$data = $wpdb->get_results(
			$wpdb->prepare("SELECT * FROM {$this->table} WHERE {$this->object_id_column} = %d ORDER BY %s ASC", $object_id, $this->primary_id_column)
		);

		if ($data) {
			$this->set_object_meta_cache($object_id, $data);
		}

		return $data;
	}

	/**
	 * get single meta data object by object ID and meta key
	 * @return object|false
	 */
	public function get_meta(int $object_id, string $meta_key) {
		$data = $this->get_object_meta_data($object_id);
		foreach ($data as $single_meta) {
			if ($meta_key == $single_meta->meta_key) {
				return $single_meta;
			}
		}

		return false;
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

		$meta_id = $this->insert($data);
		if ($meta_id) {
			$this->delete_object_meta_cache($object_id);
		}
		return $meta_id;
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

		$meta_id = $this->update($data);
		if ($meta_id) {
			$this->delete_object_meta_cache($object_id);
		}
		return $meta_id;
	}

	/**
	 * delete meta data
	 * @return int meta id
	 */
	public function delete_meta(int $object_id, string $meta_key): int{
		$data = $this->get_meta($object_id, $meta_key);
		if (!$data) {
			return 0;
		}

		$primary_id_column = $this->primary_id_column;
		$meta_id           = $data->$primary_id_column;

		$meta_id = $this->delete($meta_id);
		if ($meta_id) {
			$this->delete_object_meta_cache($object_id);
		}
		return $meta_id;
	}

	/**
	 * get cache of all meta data for specific object id
	 * @return  mixed
	 */
	private function get_object_meta_cache(int $object_id): mixed {
		return wp_cache_get($object_id, $this->table_name);
	}

	/**
	 * set meta data cache for specific object id
	 * @return  meta id
	 */
	private function set_object_meta_cache(int $object_id, array $data): bool {
		return wp_cache_set($object_id, $data, $this->table_name);
	}

	/**
	 * set meta data cache for specific object id
	 * @return  meta id
	 */
	private function delete_object_meta_cache(int $object_id): bool {
		return wp_cache_delete($object_id, $this->table_name);
	}
}
