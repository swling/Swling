<?php
/**
 * User API: WP_User class
 *
 * @package WordPress
 * @subpackage Users
 * @since 4.4.0
 */

/**
 * Core class used to implement the WP_User object.
 *
 * @since 2.0.0
 *
 * @property string $nickname
 * @property string $description
 * @property string $user_description
 * @property string $first_name
 * @property string $user_firstname
 * @property string $last_name
 * @property string $user_lastname
 * @property string $user_login
 * @property string $user_pass
 * @property string $user_nicename
 * @property string $user_email
 * @property string $user_url
 * @property string $user_registered
 * @property string $user_activation_key
 * @property string $user_status
 * @property int    $user_level
 * @property string $display_name
 * @property string $spam
 * @property string $deleted
 * @property string $locale
 * @property string $rich_editing
 * @property string $syntax_highlighting
 * @property string $use_ssl
 */
class WP_User extends WP_Object {
	/**
	 * User data container.
	 *
	 * @since 2.0.0
	 * @var stdClass
	 */
	public $data;

	/**
	 * The user's ID.
	 *
	 * @since 2.1.0
	 * @var int
	 */
	public $ID = 0;

	/**
	 * User level 0~10
	 *
	 */
	public $user_level = 0;

	/**
	 * get wpdb handler instance
	 *
	 */
	protected static function get_wpdb_handler(): object {
		return WP_Core\Model\WPDB_Handler_User::get_instance();
	}

	/**
	 * Constructor.
	 *
	 * @since 3.5.0
	 *
	 * @param WP_User|object $user User object.
	 */
	public function __construct(object $user) {
		$this->user_level = static::get_user_level($user->ID);
		parent::__construct($user);
	}

	public static function get_user_level(int $user_id): int {
		return get_user_meta($user_id, 'wp_user_level') ?: 0;
	}

	public static function update_user_level(int $user_id, int $user_level): int {
		return update_user_meta($user_id, 'wp_user_level', $user_level);
	}
}
