<?php
namespace WP_Core\Model;

use WP_Core\Utility\Singleton_Trait;

/**
 *
 */
class WPDB_Handler_User extends WPDB_Handler_Abstract {

	protected $table_name          = 'users';
	protected $object_name         = 'user';
	protected $primary_id_column   = 'ID';
	protected $required_columns    = ['user_login', 'user_pass', 'display_name'];
	protected $object_cache_fields = ['ID', 'user_login', 'user_nicename', 'user_email'];

	use Singleton_Trait;

	protected function check_insert_data(array $data) {}

	protected function check_update_data(array $data) {}
}
