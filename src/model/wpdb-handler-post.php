<?php
namespace Model;

use Exception;

/**
 *
 */
class WPDB_Handler_Post extends WPDB_Handler_Abstract {

	protected $table_name          = 'posts';
	protected $object_name         = 'post';
	protected $primary_id_column   = 'ID';
	protected $required_columns    = ['post_type', 'post_title', 'post_name'];
	protected $object_cache_fields = ['ID', 'post_name'];

	protected function check_insert_data(array $data) {}

	protected function check_update_data(array $data) {}

	private function common_check(array $data) {
		$parent = (int) $data['parent'] ?? 0;
		if ($parent > 0 && !$this->get($parent)) {
			throw new Exception(__('Parent post does not exist.'));
		}
	}
}
