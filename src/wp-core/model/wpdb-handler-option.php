<?php
namespace WP_Core\Model;

use WP_Core\Utility\Singleton_Trait;

/**
 *
 */
class WPDB_Handler_Option extends WPDB_Handler_Abstract {

	protected $table_name          = 'options';
	protected $object_name         = 'option';
	protected $primary_id_column   = 'option_id';
	protected $required_columns    = ['option_name', 'option_value'];
	protected $object_cache_fields = ['option_name'];

	use Singleton_Trait;

	protected function check_insert_data(array $data) {}

	protected function check_update_data(array $data) {}
}
