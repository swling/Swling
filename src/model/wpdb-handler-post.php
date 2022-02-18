<?php
namespace Model;

/**
 *
 */
class WPDB_Handler_Post extends WPDB_Handler_Abstract {

	protected $table_name        = 'posts';
	protected $object_name       = 'post';
	protected $primary_id_column = 'ID';

	public function insert_post() {}

	public function get_post() {}

	public function update_post() {}

	public function delete_post() {}

}
