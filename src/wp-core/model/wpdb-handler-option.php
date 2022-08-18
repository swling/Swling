<?php
namespace WP_Core\Model;

use WP_Core\Utility\Singleton_Trait;

/**
 *
 */
class WPDB_Handler_Option extends WPDB_Row {

	protected $table_name          = 'options';
	protected $object_name         = 'option';
	protected $primary_id_column   = 'option_id';
	protected $required_columns    = ['option_name', 'option_value'];
	protected $object_cache_fields = ['option_name'];

	use Singleton_Trait;

	private function __construct() {
		parent::__construct();
	}

	protected function check_insert_data(array $data): array{
		return $data;
	}

	protected function check_update_data(array $data): array{
		return $data;
	}
}
