<?php

/**
 * Sanitizes a string key.
 *
 * Keys are used as internal identifiers. Lowercase alphanumeric characters,
 * dashes, and underscores are allowed.
 *
 * @since 3.0.0
 *
 * @param string $key String key.
 * @return string Sanitized key.
 */
function sanitize_key($key) {
	$sanitized_key = '';

	if (is_scalar($key)) {
		$sanitized_key = strtolower($key);
		$sanitized_key = preg_replace('/[^a-z0-9_\-]/', '', $sanitized_key);
	}

	/**
	 * Filters a sanitized key string.
	 *
	 * @since 3.0.0
	 *
	 * @param string $sanitized_key Sanitized key.
	 * @param string $key           The key prior to sanitization.
	 */
	return apply_filters('sanitize_key', $sanitized_key, $key);
}

/**
 * Adds slashes to a string or recursively adds slashes to strings within an array.
 *
 * This should be used when preparing data for core API that expects slashed data.
 * This should not be used to escape data going directly into an SQL query.
 *
 * @since 3.6.0
 * @since 5.5.0 Non-string values are left untouched.
 *
 * @param string|array $value String or array of data to slash.
 * @return string|array Slashed `$value`.
 */
function wp_slash($value) {
	if (is_array($value)) {
		$value = array_map('wp_slash', $value);
	}

	if (is_string($value)) {
		return addslashes($value);
	}

	return $value;
}

/**
 * Removes slashes from a string or recursively removes slashes from strings within an array.
 *
 * This should be used to remove slashes from data passed to core API that
 * expects data to be unslashed.
 *
 * @since 3.6.0
 *
 * @param string|array $value String or array of data to unslash.
 * @return string|array Unslashed `$value`.
 */
function wp_unslash($value) {
	return stripslashes_deep($value);
}

/**
 * Navigates through an array, object, or scalar, and removes slashes from the values.
 *
 * @since 2.0.0
 *
 * @param mixed $value The value to be stripped.
 * @return mixed Stripped value.
 */
function stripslashes_deep($value) {
	return map_deep($value, 'stripslashes_from_strings_only');
}

/**
 * Callback function for `stripslashes_deep()` which strips slashes from strings.
 *
 * @since 4.4.0
 *
 * @param mixed $value The array or string to be stripped.
 * @return mixed The stripped value.
 */
function stripslashes_from_strings_only($value) {
	return is_string($value) ? stripslashes($value) : $value;
}

/**
 * Maps a function to all non-iterable elements of an array or an object.
 *
 * This is similar to `array_walk_recursive()` but acts upon objects too.
 *
 * @since 4.4.0
 *
 * @param mixed    $value    The array, object, or scalar.
 * @param callable $callback The function to map onto $value.
 * @return mixed The value with the callback applied to all non-arrays and non-objects inside it.
 */
function map_deep($value, $callback) {
	if (is_array($value)) {
		foreach ($value as $index => $item) {
			$value[$index] = map_deep($item, $callback);
		}
	} elseif (is_object($value)) {
		$object_vars = get_object_vars($value);
		foreach ($object_vars as $property_name => $property_value) {
			$value->$property_name = map_deep($property_value, $callback);
		}
	} else {
		$value = call_user_func($callback, $value);
	}

	return $value;
}
