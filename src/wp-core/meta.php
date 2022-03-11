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

function get_meta_handler(string $meta_type): object{
	$handler = Model\WPDB_Handler_Meta::get_instance();
	$handler->set_meta_type($meta_type);
	return $handler;
}

/**
 * Adds metadata for the specified object.
 *
 * @param string $meta_type  Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                           or any other object type with an associated meta table.
 * @param int    $object_id  ID of the object metadata is for.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 *
 * @return int The meta ID on success, 0 on failure.
 */
function add_metadata(string $meta_type, int $object_id, string $meta_key, $meta_value) {
	$handler = get_meta_handler($meta_type);
	return $handler->add_meta($object_id, $meta_key, $meta_value);
}

/**
 * Retrieves the value of a metadata field for the specified object type and ID.
 *
 * @param string $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                          or any other object type with an associated meta table.
 * @param int    $object_id ID of the object metadata is for.
 * @param string $meta_key  Optional. Metadata key. If not specified, retrieve all metadata for
 *                          the specified object. Default empty.
 * @return mixed  The value of the meta field
 */
function get_metadata(string $meta_type, int $object_id, string $meta_key) {
	$handler = get_meta_handler($meta_type);
	return $handler->get_meta($object_id, $meta_key);
}

/**
 * Updates metadata for the specified object. If no value already exists for the specified object
 * ID and metadata key, the metadata will be added.
 *
 * @param string $meta_type  Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                           or any other object type with an associated meta table.
 * @param int    $object_id  ID of the object metadata is for.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @return int The new meta field ID if a field with the given key didn't exist 0 on failure
 */
function update_metadata(string $meta_type, int $object_id, string $meta_key, $meta_value) {
	$handler = get_meta_handler($meta_type);
	return $handler->update_meta($object_id, $meta_key, $meta_value);
}

/**
 * Deletes metadata for the specified object.
 *
 * @param string $meta_type  Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                           or any other object type with an associated meta table.
 * @param int    $object_id  ID of the object metadata is for.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Optional. Metadata value. Must be serializable if non-scalar.
 *                           If specified, only delete metadata entries with this value.
 *                           Otherwise, delete all entries with the specified meta_key.
 *                           Pass `null`, `false`, or an empty string to skip this check.
 *                           (For backward compatibility, it is not possible to pass an empty string
 *                           to delete those entries with an empty string for a value.)
 *
 * @return int The meta ID on success, 0 on failure.
 */
function delete_metadata(string $meta_type, int $object_id, string $meta_key) {
	$handler = get_meta_handler($meta_type);
	return $handler->delete_meta($object_id, $meta_key);
}
