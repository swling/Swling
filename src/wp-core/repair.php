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

function __($text): string {
	return $text;
}

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

/**
 * Escapes data for use in a MySQL query.
 *
 * Usually you should prepare queries using wpdb::prepare().
 * Sometimes, spot-escaping is required or useful. One example
 * is preparing an array for use in an IN clause.
 *
 * NOTE: Since 4.8.3, '%' characters will be replaced with a placeholder string,
 * this prevents certain SQLi attacks from taking place. This change in behaviour
 * may cause issues for code that expects the return value of esc_sql() to be useable
 * for other purposes.
 *
 * @since 2.8.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param string|array $data Unescaped data
 * @return string|array Escaped data
 */
function esc_sql($data) {
	global $wpdb;
	return $wpdb->_escape($data);
}

function esc_html($text) {
	return $text;
}

function _doing_it_wrong($function, $message, $version) {
	sprintf(
		'%1$s was called <strong>incorrectly</strong>. %2$s %3$s',
		$function,
		$message,
		$version
	);
}

/**
 * Test if the supplied date is valid for the Gregorian calendar.
 *
 * @since 3.5.0
 *
 * @link https://www.php.net/manual/en/function.checkdate.php
 *
 * @param int    $month       Month number.
 * @param int    $day         Day number.
 * @param int    $year        Year number.
 * @param string $source_date The date to filter.
 * @return bool True if valid date, false if not valid date.
 */
function wp_checkdate($month, $day, $year, $source_date) {
	/**
	 * Filters whether the given date is valid for the Gregorian calendar.
	 *
	 * @since 3.5.0
	 *
	 * @param bool   $checkdate   Whether the given date is valid.
	 * @param string $source_date Date to check.
	 */
	return apply_filters('wp_checkdate', checkdate($month, $day, $year), $source_date);
}

/**
 * Return a MySQL expression for selecting the week number based on the start_of_week option.
 *
 * @ignore
 * @since 3.0.0
 *
 * @param string $column Database column.
 * @return string SQL clause.
 */
function _wp_mysql_week($column) {
	$start_of_week = (int) get_option('start_of_week');
	switch ($start_of_week) {
		case 1:
			return "WEEK( $column, 1 )";
		case 2:
		case 3:
		case 4:
		case 5:
		case 6:
			return "WEEK( DATE_SUB( $column, INTERVAL $start_of_week DAY ), 0 )";
		case 0:
		default:
			return "WEEK( $column, 0 )";
	}
}

/**
 * Retrieves the current time based on specified type.
 *
 *  - The 'mysql' type will return the time in the format for MySQL DATETIME field.
 *  - The 'timestamp' or 'U' types will return the current timestamp or a sum of timestamp
 *    and timezone offset, depending on `$gmt`.
 *  - Other strings will be interpreted as PHP date formats (e.g. 'Y-m-d').
 *
 * If `$gmt` is a truthy value then both types will use GMT time, otherwise the
 * output is adjusted with the GMT offset for the site.
 *
 * @since 1.0.0
 * @since 5.3.0 Now returns an integer if `$type` is 'U'. Previously a string was returned.
 *
 * @param string   $type Type of time to retrieve. Accepts 'mysql', 'timestamp', 'U',
 *                       or PHP date format string (e.g. 'Y-m-d').
 * @param int|bool $gmt  Optional. Whether to use GMT timezone. Default false.
 * @return int|string Integer if `$type` is 'timestamp' or 'U', string otherwise.
 */
function current_time($type, $gmt = 0) {
	// Don't use non-GMT timestamp, unless you know the difference and really need to.
	if ('timestamp' === $type || 'U' === $type) {
		return $gmt ? time() : time() + (int) (get_option('gmt_offset') * HOUR_IN_SECONDS);
	}

	if ('mysql' === $type) {
		$type = 'Y-m-d H:i:s';
	}

	$timezone = $gmt ? new DateTimeZone('UTC') : wp_timezone();
	$datetime = new DateTime('now', $timezone);

	return $datetime->format($type);
}

/**
 * Retrieves the timezone of the site as a `DateTimeZone` object.
 *
 * Timezone can be based on a PHP timezone string or a ±HH:MM offset.
 *
 * @since 5.3.0
 *
 * @return DateTimeZone Timezone object.
 */
function wp_timezone() {
	return new DateTimeZone('Asia/Shanghai');
}
