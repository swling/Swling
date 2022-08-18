<?php
namespace WP_Core\Model;

use Exception;
use WP_Core\Utility\Singleton_Trait;

/**
 *
 */
class WPDB_Handler_User extends WPDB_Row {

	protected $table_name          = 'users';
	protected $object_name         = 'user';
	protected $primary_id_column   = 'ID';
	protected $required_columns    = ['user_login', 'user_pass', 'display_name'];
	protected $object_cache_fields = ['ID', 'user_login', 'user_nicename', 'user_email'];

	use Singleton_Trait;

	private function __construct() {
		parent::__construct();
	}

	protected function check_insert_data(array $data): array{
		static::common_check($data);

		// 用户名去重
		if ($this->get_by('user_login', $data['user_login'])) {
			throw new Exception('existing_user_login');
		}

		return $data;
	}

	protected function check_update_data(array $data): array{
		static::common_check($data);

		// 用户名去重
		if (isset($data['user_login'])) {
			$exists_user = $this->get_by('user_login', $data['user_login']);
			if ($exists_user and $exists_user->ID != $data['ID']) {
				throw new Exception('existing_user_login');
			}
		}

		return $data;
	}

	private function common_check(array $data) {
		// Remove any non-printable chars from the login string to see if we have ended up with an empty username.
		$user_login = trim($data['user_login']);

		// user_login must be between 0 and 60 characters.
		if (empty($user_login)) {
			throw new Exception('empty_user_login');
		}

		if (mb_strlen($user_login) > 60) {
			throw new Exception('user_login_too_long');
		}
	}

}
