<?php

/**
 * Retrieves the name of the metadata table for the specified object type.
 *
 * @since 2.9.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string $type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                     or any other object type with an associated meta table.
 * @return string|false Metadata table name, or false if no metadata table exists
 */
function _get_meta_table($type) {
	global $wpdb;

	$table_name = $type . 'meta';

	if (empty($wpdb->$table_name)) {
		return false;
	}

	return $wpdb->$table_name;
}
