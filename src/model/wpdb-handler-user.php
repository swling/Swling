<?php
namespace Model;

/**
 *
 */
class WPDB_Handler_User extends WPDB_Handler_Abstract {

	protected $table_name        = 'users';
	protected $object_name       = 'user';
	protected $primary_id_column = 'ID';
	protected $required_columns  = ['user_login', 'user_pass', 'display_name'];
}
