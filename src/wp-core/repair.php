<?php
/**
 * 为在不修改wp原文件的前提下引入文件，需要临时性补充缺失的wp函数或类
 * 本文件为开发时期临时文件，后期相关代码稳固后，应废弃对本文件的依赖
 */
function wp_load_translations_early() {}

function wp_debug_backtrace_summary() {}

function is_multisite() {
	return false;
}

/**
 * @uses dbDelta
 */
function wp_should_upgrade_global_tables() {
	return true;
}

function __() {}

function is_wp_error() {
	return false;
}

// ***************************************************************************************************************** //
/**
 * Set the mbstring internal encoding to a binary safe encoding when func_overload
 * is enabled.
 *
 * When mbstring.func_overload is in use for multi-byte encodings, the results from
 * strlen() and similar functions respect the utf8 characters, causing binary data
 * to return incorrect lengths.
 *
 * This function overrides the mbstring encoding to a binary-safe encoding, and
 * resets it to the users expected encoding afterwards through the
 * `reset_mbstring_encoding` function.
 *
 * It is safe to recursively call this function, however each
 * `mbstring_binary_safe_encoding()` call must be followed up with an equal number
 * of `reset_mbstring_encoding()` calls.
 *
 *                    Default false.
 * @see reset_mbstring_encoding()
 * @since 3.7.0
 *
 * @param bool $reset Optional. Whether to reset the encoding back to a previously-set encoding.
 */
function mbstring_binary_safe_encoding($reset = false) {
	static $encodings  = [];
	static $overloaded = null;

	if (is_null($overloaded)) {
		if (function_exists('mb_internal_encoding')
			&& ((int) ini_get('mbstring.func_overload') & 2) // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecated
		) {
			$overloaded = true;
		} else {
			$overloaded = false;
		}
	}

	if (false === $overloaded) {
		return;
	}

	if (!$reset) {
		$encoding = mb_internal_encoding();
		array_push($encodings, $encoding);
		mb_internal_encoding('ISO-8859-1');
	}

	if ($reset && $encodings) {
		$encoding = array_pop($encodings);
		mb_internal_encoding($encoding);
	}
}

/**
 * Reset the mbstring internal encoding to a users previously set encoding.
 *
 * @see mbstring_binary_safe_encoding()
 * @since 3.7.0
 */
function reset_mbstring_encoding() {
	mbstring_binary_safe_encoding(true);
}

/**
 * Convert a value to non-negative integer.
 *
 * @since 2.5.0
 *
 * @param mixed $maybeint Data you wish to have converted to a non-negative integer.
 * @return int A non-negative integer.
 */
function absint($maybeint) {
	return abs((int) $maybeint);
}

/**
 * Merges user defined arguments into defaults array.
 *
 * This function is used throughout WordPress to allow for both string or array
 * to be merged into another array.
 *
 * @since 2.2.0
 * @since 2.3.0 `$args` can now also be an object.
 *
 * @param string|array|object $args     Value to merge with $defaults.
 * @param array               $defaults Optional. Array that serves as the defaults.
 *                                      Default empty array.
 * @return array Merged user defined values with defaults.
 */
function wp_parse_args($args, $defaults = []) {
	if (is_object($args)) {
		$parsed_args = get_object_vars($args);
	} elseif (is_array($args)) {
		$parsed_args = &$args;
	} else {
		wp_parse_str($args, $parsed_args);
	}

	if (is_array($defaults) && $defaults) {
		return array_merge($defaults, $parsed_args);
	}
	return $parsed_args;
}

/**
 * Parses a string into variables to be stored in an array.
 *
 * @since 2.2.1
 *
 * @param string $string The string to be parsed.
 * @param array  $array  Variables will be stored in this array.
 */
function wp_parse_str($string, &$array) {
	parse_str((string) $string, $array);

	/**
	 * Filters the array of variables derived from a parsed string.
	 *
	 * @since 2.2.1
	 *
	 * @param array $array The array populated with variables.
	 */
	$array = apply_filters('wp_parse_str', $array);
}

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
