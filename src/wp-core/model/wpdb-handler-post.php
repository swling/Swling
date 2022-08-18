<?php
namespace WP_Core\Model;

use Exception;
use WP_Core\Utility\Singleton_Trait;

/**
 *
 */
class WPDB_Handler_Post extends WPDB_Row {

	protected $table_name          = 'posts';
	protected $object_name         = 'post';
	protected $primary_id_column   = 'ID';
	protected $required_columns    = ['post_type', 'post_title', 'post_name'];
	protected $object_cache_fields = ['ID', 'post_name'];

	use Singleton_Trait;

	protected function check_insert_data(array $data): array{
		$data = $this->common_check($data);

		return $data;
	}

	protected function check_update_data(array $data): array{
		$data                      = $this->common_check($data);
		$data['post_modified']     = current_time('mysql');
		$data['post_modified_gmt'] = current_time('mysql', 1);

		// 更新未发布的 post，设置发布时间
		if (!in_array($data['post_status'], ['publish', 'private'])) {
			$data['post_date']     = current_time('mysql');
			$data['post_date_gmt'] = current_time('mysql', 1);
		}

		return $data;
	}

	private function common_check(array $data): array{
		$parent = (int) ($data['parent'] ?? 0);
		if ($parent > 0 && !$this->get($parent)) {
			throw new Exception(__('Parent post does not exist.'));
		}

		if (!isset($data['post_date']) || '0000-00-00 00:00:00' == $data['post_date']) {
			$data['post_date']     = current_time('mysql');
			$data['post_date_gmt'] = current_time('mysql', 1);
		}

		return $data;
	}
}
