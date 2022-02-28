<?php
namespace Model;

/**
 *
 */
class WPDB_Handler_Post extends WPDB_Handler_Abstract {

	protected $table_name          = 'posts';
	protected $object_name         = 'post';
	protected $primary_id_column   = 'ID';
	protected $required_columns    = ['post_type', 'post_title', 'post_name'];
	protected $object_cache_fields = ['ID', 'post_name'];
}
