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
	$handler = WP_Core\Model\WPDB_Handler_Meta::get_instance();
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
	$handler    = get_meta_handler($meta_type);
	$meta_value = maybe_serialize($meta_value);
	return $handler->add_meta($object_id, $meta_key, $meta_value);
}

/**
 * Retrieves all raw metadata value for the specified object.
 *
 * @param string $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                          or any other object type with an associated meta table.
 * @param int    $object_id ID of the object metadata is for.
 * @return array|false
 */
function get_metadata_raw(string $meta_type, int $object_id): mixed{
	$handler = get_meta_handler($meta_type);
	return $handler->get_rows($object_id);
}

/**
 * Retrieves the raw data of a metadata  for the specified object type and ID.
 *
 * @param string $meta_type Type of object metadata is for. Accepts 'post', 'comment', 'term', 'user',
 *                          or any other object type with an associated meta table.
 * @param int    $object_id ID of the object metadata is for.
 * @param string $meta_key  Metadata key.
 *
 * @return object|false     The object of the meta
 */
function get_meta_object(string $meta_type, int $object_id, string $meta_key) {
	$handler = get_meta_handler($meta_type);
	return $handler->get_meta($object_id, $meta_key);
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
function get_metadata(string $meta_type, int $object_id, string $meta_key): mixed{
	$meta_object = get_meta_object($meta_type, $object_id, $meta_key);
	$data        = $meta_object->meta_value ?? false;
	return $data ? maybe_unserialize($data) : false;
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
	$handler    = get_meta_handler($meta_type);
	$meta_value = maybe_serialize($meta_value);
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

//
// Post meta functions.
//

/**
 * Adds a meta field to the given post.
 *
 * Post meta data is called "Custom Fields" on the Administration Screen.
 *
 * @since 1.5.0
 *
 * @param int    $post_id    Post ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @return int   Meta ID on success, 0 on failure.
 */
function add_post_meta(int $post_id, string $meta_key, mixed $meta_value): int {
	return add_metadata('post', $post_id, $meta_key, $meta_value);
}

/**
 * Deletes a post meta field for the given post ID.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching the key, if needed.
 *
 * @since 1.5.0
 *
 * @param int    $post_id    Post ID.
 * @param string $meta_key   Metadata name.
 * @return int   The meta ID on success, 0 on failure.
 */
function delete_post_meta(int $post_id, string $meta_key): int {
	return delete_metadata('post', $post_id, $meta_key);
}

/**
 * Retrieves a post meta field for the given post ID.
 *
 * @since 1.5.0
 *
 * @param int    $post_id Post ID.
 * @param string $key     Optional. The meta key to retrieve. By default,
 *                        returns data for all keys. Default empty.
 * @return mixed          The value of the meta field.
 */
function get_post_meta(int $post_id, string $meta_key): mixed {
	return get_metadata('post', $post_id, $meta_key);
}

/**
 * Updates a post meta field based on the given post ID.
 *
 * Use the `$prev_value` parameter to differentiate between meta fields with the
 * same key and post ID.
 *
 * If the meta field for the post does not exist, it will be added and its ID returned.
 *
 * Can be used in place of add_post_meta().
 *
 * @since 1.5.0
 *
 * @param int    $post_id    Post ID.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @return int               The meta ID on success, 0 on failure.
 */
function update_post_meta(int $post_id, string $meta_key, mixed $meta_value): int {
	return update_metadata('post', $post_id, $meta_key, $meta_value);
}

//
// User meta functions.
//

/**
 * Adds meta data to a user.
 *
 * @param int    $user_id    User ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @return int   Meta ID on success, 0 on failure.
 */
function add_user_meta(int $user_id, string $meta_key, mixed $meta_value): int {
	return add_metadata('user', $user_id, $meta_key, $meta_value);
}

/**
 * Remove metadata matching criteria from a user.
 *
 * @param int    $user_id    User ID
 * @param string $meta_key   Metadata name.
 * @return int   Meta ID on success, 0 on failure.
 */
function delete_user_meta(int $user_id, string $meta_key): int {
	return delete_metadata('user', $user_id, $meta_key);
}

/**
 * Retrieve user meta field for a user.
 *
 * @param int    $user_id User ID.
 * @param string $key     The meta key to retrieve.
 * @return mixed          The value of the meta field.
 */
function get_user_meta(int $user_id, string $meta_key): mixed {
	return get_metadata('user', $user_id, $meta_key);
}

/**
 * Update user meta field based on user ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and user ID.
 *
 * If the meta field for the user does not exist, it will be added.
 *
 * @param int    $user_id    User ID.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
 * @return int   Meta ID on success, 0 on failure.
 */
function update_user_meta(int $user_id, string $meta_key, mixed $meta_value): int {
	return update_metadata('user', $user_id, $meta_key, $meta_value);
}
