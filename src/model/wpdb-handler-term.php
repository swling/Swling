<?php
namespace Model;

/**
 *
 */
class WPDB_Handler_Term extends WPDB_Handler_Abstract {

	protected $table_name          = 'terms';
	protected $object_name         = 'term';
	protected $primary_id_column   = 'term_id';
	protected $required_columns    = ['name', 'slug', 'taxonomy'];
	protected $object_cache_fields = ['term_id'];
}
