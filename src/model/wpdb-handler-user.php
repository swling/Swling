<?php
namespace Model;

/**
 *
 */
class WPDB_Handler_User extends WPDB_Handler_Abstract {

	protected $table_name        = 'users';
	protected $object_name       = 'user';
	protected $primary_id_column = 'ID';

	public function insert_user() {}

	public function get_user() {}

	public function update_user() {}

	public function delete_user() {}
}
