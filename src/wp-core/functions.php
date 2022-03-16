<?php
/**
 * Serialize data, if needed.
 *
 * @since 2.0.5
 *
 * @param string|array|object $data Data that might be serialized.
 * @return mixed A scalar data.
 */
function maybe_serialize($data) {
	if (is_array($data) || is_object($data)) {
		return serialize($data);
	}

	/**
	 * Double serialization is required for backward compatibility.
	 * See https://core.trac.wordpress.org/ticket/12930
	 * Also the world will end. See WP 3.6.1.
	 */
	if (is_serialized($data, false)) {
		return serialize($data);
	}

	return $data;
}

/**
 * Unserialize data only if it was serialized.
 *
 * @since 2.0.0
 *
 * @param string $data Data that might be unserialized.
 * @return mixed Unserialized data can be any type.
 */
function maybe_unserialize($data) {
	if (is_serialized($data)) {
		// Don't attempt to unserialize data that wasn't serialized going in.
		return @unserialize(trim($data));
	}

	return $data;
}

/**
 * Check value to find if it was serialized.
 *
 * If $data is not an string, then returned value will always be false.
 * Serialized data is always a string.
 *
 * @since 2.0.5
 *
 * @param string $data   Value to check to see if was serialized.
 * @param bool   $strict Optional. Whether to be strict about the end of the string. Default true.
 * @return bool False if not serialized and true if it was.
 */
function is_serialized($data, $strict = true) {
	// If it isn't a string, it isn't serialized.
	if (!is_string($data)) {
		return false;
	}
	$data = trim($data);
	if ('N;' === $data) {
		return true;
	}
	if (strlen($data) < 4) {
		return false;
	}
	if (':' !== $data[1]) {
		return false;
	}
	if ($strict) {
		$lastc = substr($data, -1);
		if (';' !== $lastc && '}' !== $lastc) {
			return false;
		}
	} else {
		$semicolon = strpos($data, ';');
		$brace     = strpos($data, '}');
		// Either ; or } must exist.
		if (false === $semicolon && false === $brace) {
			return false;
		}
		// But neither must be in the first X characters.
		if (false !== $semicolon && $semicolon < 3) {
			return false;
		}
		if (false !== $brace && $brace < 4) {
			return false;
		}
	}
	$token = $data[0];
	switch ($token) {
		case 's':
			if ($strict) {
				if ('"' !== substr($data, -2, 1)) {
					return false;
				}
			} elseif (false === strpos($data, '"')) {
				return false;
			}
		// Or else fall through.
		case 'a':
		case 'O':
			return (bool) preg_match("/^{$token}:[0-9]+:/s", $data);
		case 'b':
		case 'i':
		case 'd':
			$end = $strict ? '$' : '';
			return (bool) preg_match("/^{$token}:[0-9.E+-]+;$end/", $data);
	}
	return false;
}

/**
 * Check whether serialized data is of string type.
 *
 * @since 2.0.5
 *
 * @param string $data Serialized data.
 * @return bool False if not a serialized string, true if it is.
 */
function is_serialized_string($data) {
	// if it isn't a string, it isn't a serialized string.
	if (!is_string($data)) {
		return false;
	}
	$data = trim($data);
	if (strlen($data) < 4) {
		return false;
	} elseif (':' !== $data[1]) {
		return false;
	} elseif (';' !== substr($data, -1)) {
		return false;
	} elseif ('s' !== $data[0]) {
		return false;
	} elseif ('"' !== substr($data, -2, 1)) {
		return false;
	} else {
		return true;
	}
}

/**
 * Converts a comma- or space-separated list of scalar values to an array.
 *
 * @since 5.1.0
 *
 * @param array|string $list List of values.
 * @return array Array of values.
 */
function wp_parse_list($list) {
	if (!is_array($list)) {
		return preg_split('/[\s,]+/', $list, -1, PREG_SPLIT_NO_EMPTY);
	}

	return $list;
}

/**
 * Cleans up an array, comma- or space-separated list of IDs.
 *
 * @since 3.0.0
 * @since 5.1.0 Refactored to use wp_parse_list().
 *
 * @param array|string $list List of IDs.
 * @return int[] Sanitized array of IDs.
 */
function wp_parse_id_list($list) {
	$list = wp_parse_list($list);

	return array_unique(array_map('absint', $list));
}

/**
 * Extract a slice of an array, given a list of keys.
 *
 * @since 3.1.0
 *
 * @param array $array The original array.
 * @param array $keys  The list of keys.
 * @return array The array slice.
 */
function wp_array_slice_assoc($array, $keys) {
	$slice = [];

	foreach ($keys as $key) {
		if (isset($array[$key])) {
			$slice[$key] = $array[$key];
		}
	}

	return $slice;
}

/**
 * Return a comma-separated string of functions that have been called to get
 * to the current point in code.
 *
 * @since 3.4.0
 *
 * @see https://core.trac.wordpress.org/ticket/19589
 *
 * @param string $ignore_class Optional. A class to ignore all function calls within - useful
 *                             when you want to just give info about the callee. Default null.
 * @param int    $skip_frames  Optional. A number of stack frames to skip - useful for unwinding
 *                             back to the source of the issue. Default 0.
 * @param bool   $pretty       Optional. Whether or not you want a comma separated string or raw
 *                             array returned. Default true.
 * @return string|array Either a string containing a reversed comma separated trace or an array
 *                      of individual calls.
 */
function wp_debug_backtrace_summary($ignore_class = null, $skip_frames = 0, $pretty = true) {
	static $truncate_paths;

	$trace       = debug_backtrace(false);
	$caller      = [];
	$check_class = !is_null($ignore_class);
	$skip_frames++; // Skip this function.

	if (!isset($truncate_paths)) {
		$truncate_paths = [
			wp_normalize_path(WP_CONTENT_DIR),
			wp_normalize_path(ABSPATH),
		];
	}

	foreach ($trace as $call) {
		if ($skip_frames > 0) {
			$skip_frames--;
		} elseif (isset($call['class'])) {
			if ($check_class && $ignore_class == $call['class']) {
				continue; // Filter out calls.
			}

			$caller[] = "{$call['class']}{$call['type']}{$call['function']}";
		} else {
			if (in_array($call['function'], ['do_action', 'apply_filters', 'do_action_ref_array', 'apply_filters_ref_array'], true)) {
				$caller[] = "{$call['function']}('{$call['args'][0]}')";
			} elseif (in_array($call['function'], ['include', 'include_once', 'require', 'require_once'], true)) {
				$filename = isset($call['args'][0]) ? $call['args'][0] : '';
				$caller[] = $call['function'] . "('" . str_replace($truncate_paths, '', wp_normalize_path($filename)) . "')";
			} else {
				$caller[] = $call['function'];
			}
		}
	}
	if ($pretty) {
		return implode(', ', array_reverse($caller));
	} else {
		return $caller;
	}
}

/**
 * Normalize a filesystem path.
 *
 * On windows systems, replaces backslashes with forward slashes
 * and forces upper-case drive letters.
 * Allows for two leading slashes for Windows network shares, but
 * ensures that all other duplicate slashes are reduced to a single.
 *
 * @since 3.9.0
 * @since 4.4.0 Ensures upper-case drive letters on Windows systems.
 * @since 4.5.0 Allows for Windows network shares.
 * @since 4.9.7 Allows for PHP file wrappers.
 *
 * @param string $path Path to normalize.
 * @return string Normalized path.
 */
function wp_normalize_path($path) {
	$wrapper = '';

	if (wp_is_stream($path)) {
		list($wrapper, $path) = explode('://', $path, 2);

		$wrapper .= '://';
	}

	// Standardise all paths to use '/'.
	$path = str_replace('\\', '/', $path);

	// Replace multiple slashes down to a singular, allowing for network shares having two slashes.
	$path = preg_replace('|(?<=.)/+|', '/', $path);

	// Windows paths should uppercase the drive letter.
	if (':' === substr($path, 1, 1)) {
		$path = ucfirst($path);
	}

	return $wrapper . $path;
}

/**
 * Test if a given path is a stream URL
 *
 * @since 3.5.0
 *
 * @param string $path The resource path or URL.
 * @return bool True if the path is a stream URL.
 */
function wp_is_stream($path) {
	$scheme_separator = strpos($path, '://');

	if (false === $scheme_separator) {
		// $path isn't a stream.
		return false;
	}

	$stream = substr($path, 0, $scheme_separator);

	return in_array($stream, stream_get_wrappers(), true);
}

/**
 * Retrieve IDs that are not already present in the cache.
 *
 * @since 3.4.0
 * @access private
 *
 * @param int[]  $object_ids Array of IDs.
 * @param string $cache_key  The cache bucket to check against.
 * @return int[] Array of IDs not present in the cache.
 */
function _get_non_cached_ids($object_ids, $cache_key) {
	$non_cached_ids = [];
	$cache_values   = wp_cache_get_multiple($object_ids, $cache_key);

	foreach ($cache_values as $id => $value) {
		if (!$value) {
			$non_cached_ids[] = (int) $id;
		}
	}

	return $non_cached_ids;
}

/**
 * Filters a list of objects, based on a set of key => value arguments.
 *
 * Retrieves the objects from the list that match the given arguments.
 * Key represents property name, and value represents property value.
 *
 * If an object has more properties than those specified in arguments,
 * that will not disqualify it. When using the 'AND' operator,
 * any missing properties will disqualify it.
 *
 * When using the `$field` argument, this function can also retrieve
 * a particular field from all matching objects, whereas wp_list_filter()
 * only does the filtering.
 *
 * @since 3.0.0
 * @since 4.7.0 Uses `WP_List_Util` class.
 *
 * @param array       $list     An array of objects to filter.
 * @param array       $args     Optional. An array of key => value arguments to match
 *                              against each object. Default empty array.
 * @param string      $operator Optional. The logical operation to perform. 'AND' means
 *                              all elements from the array must match. 'OR' means only
 *                              one element needs to match. 'NOT' means no elements may
 *                              match. Default 'AND'.
 * @param bool|string $field    Optional. A field from the object to place instead
 *                              of the entire object. Default false.
 * @return array A list of objects or object fields.
 */
function wp_filter_object_list($list, $args = [], $operator = 'and', $field = false) {
	if (!is_array($list)) {
		return [];
	}

	$util = new WP_List_Util($list);

	$util->filter($args, $operator);

	if ($field) {
		$util->pluck($field);
	}

	return $util->get_output();
}

/**
 * Plucks a certain field out of each object or array in an array.
 *
 * This has the same functionality and prototype of
 * array_column() (PHP 5.5) but also supports objects.
 *
 * @since 3.1.0
 * @since 4.0.0 $index_key parameter added.
 * @since 4.7.0 Uses `WP_List_Util` class.
 *
 * @param array      $list      List of objects or arrays.
 * @param int|string $field     Field from the object to place instead of the entire object.
 * @param int|string $index_key Optional. Field from the object to use as keys for the new array.
 *                              Default null.
 * @return array Array of found values. If `$index_key` is set, an array of found values with keys
 *               corresponding to `$index_key`. If `$index_key` is null, array keys from the original
 *               `$list` will be preserved in the results.
 */
function wp_list_pluck($list, $field, $index_key = null) {
	$util = new WP_List_Util($list);

	return $util->pluck($field, $index_key);
}

/**
 * Temporarily suspend cache additions.
 *
 * Stops more data being added to the cache, but still allows cache retrieval.
 * This is useful for actions, such as imports, when a lot of data would otherwise
 * be almost uselessly added to the cache.
 *
 * Suspension lasts for a single page load at most. Remember to call this
 * function again if you wish to re-enable cache adds earlier.
 *
 * @since 3.3.0
 *
 * @param bool $suspend Optional. Suspends additions if true, re-enables them if false.
 * @return bool The current suspend setting
 */
function wp_suspend_cache_addition($suspend = null) {
	static $_suspend = false;

	if (is_bool($suspend)) {
		$_suspend = $suspend;
	}

	return $_suspend;
}

/**
 * Mark a function as deprecated and inform when it has been used.
 *
 * There is a {@see 'hook deprecated_function_run'} that will be called that can be used
 * to get the backtrace up to what file and function called the deprecated
 * function.
 *
 * The current behavior is to trigger a user error if `WP_DEBUG` is true.
 *
 * This function is to be used in every function that is deprecated.
 *
 * @since 2.5.0
 * @since 5.4.0 This function is no longer marked as "private".
 * @since 5.4.0 The error type is now classified as E_USER_DEPRECATED (used to default to E_USER_NOTICE).
 *
 * @param string $function    The function that was called.
 * @param string $version     The version of WordPress that deprecated the function.
 * @param string $replacement Optional. The function that should have been called. Default empty.
 */
function _deprecated_function($function, $version, $replacement = '') {

	/**
	 * Fires when a deprecated function is called.
	 *
	 * @since 2.5.0
	 *
	 * @param string $function    The function that was called.
	 * @param string $replacement The function that should have been called.
	 * @param string $version     The version of WordPress that deprecated the function.
	 */
	do_action('deprecated_function_run', $function, $replacement, $version);

	/**
	 * Filters whether to trigger an error for deprecated functions.
	 *
	 * @since 2.5.0
	 *
	 * @param bool $trigger Whether to trigger the error for deprecated functions. Default true.
	 */
	if (WP_DEBUG && apply_filters('deprecated_function_trigger_error', true)) {
		if ($replacement) {
			trigger_error(
				sprintf(
					'%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.',
					$function,
					$version,
					$replacement
				),
				E_USER_DEPRECATED
			);
		} else {
			trigger_error(
				sprintf(
					'%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.',
					$function,
					$version
				),
				E_USER_DEPRECATED
			);
		}
	}
}

/**
 * Gets last changed date for the specified cache group.
 *
 * @since 4.7.0
 *
 * @param string $group Where the cache contents are grouped.
 * @return string UNIX timestamp with microseconds representing when the group was last changed.
 */
function wp_cache_get_last_changed(string $group): string{
	$last_changed = wp_cache_get('last_changed', $group);

	if (!$last_changed) {
		$last_changed = microtime();
		wp_cache_set('last_changed', $last_changed, $group);
	}

	return $last_changed;
}

/**
 * Delete last changed date for the specified cache group.
 *
 *
 * @param string $group Where the cache contents are grouped.
 * @return bool True on successful removal, false on failure.
 */
function wp_cache_delete_last_changed(string $group): bool {
	return wp_cache_delete('last_changed', $group);
}

/**
 * Retrieve the number of database queries during the WordPress execution.
 *
 * @since 2.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @return int Number of database queries.
 */
function get_num_queries() {
	global $wpdb;
	return $wpdb->num_queries;
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
 * Build URL query based on an associative and, or indexed array.
 *
 * This is a convenient function for easily building url queries. It sets the
 * separator to '&' and uses _http_build_query() function.
 *
 * @since 2.3.0
 *
 * @see _http_build_query() Used to build the query
 * @link https://www.php.net/manual/en/function.http-build-query.php for more on what
 *       http_build_query() does.
 *
 * @param array $data URL-encode key/value pairs.
 * @return string URL-encoded string.
 */
function build_query($data) {
	return _http_build_query($data, null, '&', '', false);
}

/**
 * From php.net (modified by Mark Jaquith to behave like the native PHP5 function).
 *
 * @since 3.2.0
 * @access private
 *
 * @see https://www.php.net/manual/en/function.http-build-query.php
 *
 * @param array|object $data      An array or object of data. Converted to array.
 * @param string       $prefix    Optional. Numeric index. If set, start parameter numbering with it.
 *                                Default null.
 * @param string       $sep       Optional. Argument separator; defaults to 'arg_separator.output'.
 *                                Default null.
 * @param string       $key       Optional. Used to prefix key name. Default empty.
 * @param bool         $urlencode Optional. Whether to use urlencode() in the result. Default true.
 * @return string The query string.
 */
function _http_build_query($data, $prefix = null, $sep = null, $key = '', $urlencode = true) {
	$ret = [];

	foreach ((array) $data as $k => $v) {
		if ($urlencode) {
			$k = urlencode($k);
		}
		if (is_int($k) && null != $prefix) {
			$k = $prefix . $k;
		}
		if (!empty($key)) {
			$k = $key . '%5B' . $k . '%5D';
		}
		if (null === $v) {
			continue;
		} elseif (false === $v) {
			$v = '0';
		}

		if (is_array($v) || is_object($v)) {
			array_push($ret, _http_build_query($v, '', $sep, $k, $urlencode));
		} elseif ($urlencode) {
			array_push($ret, $k . '=' . urlencode($v));
		} else {
			array_push($ret, $k . '=' . $v);
		}
	}

	if (null === $sep) {
		$sep = ini_get('arg_separator.output');
	}

	return implode($sep, $ret);
}

/**
 * Retrieves a modified URL query string.
 *
 * You can rebuild the URL and append query variables to the URL query by using this function.
 * There are two ways to use this function; either a single key and value, or an associative array.
 *
 * Using a single key and value:
 *
 *     add_query_arg( 'key', 'value', 'http://example.com' );
 *
 * Using an associative array:
 *
 *     add_query_arg( array(
 *         'key1' => 'value1',
 *         'key2' => 'value2',
 *     ), 'http://example.com' );
 *
 * Omitting the URL from either use results in the current URL being used
 * (the value of `$_SERVER['REQUEST_URI']`).
 *
 * Values are expected to be encoded appropriately with urlencode() or rawurlencode().
 *
 * Setting any query variable's value to boolean false removes the key (see remove_query_arg()).
 *
 * Important: The return value of add_query_arg() is not escaped by default. Output should be
 * late-escaped with esc_url() or similar to help prevent vulnerability to cross-site scripting
 * (XSS) attacks.
 *
 * @since 1.5.0
 * @since 5.3.0 Formalized the existing and already documented parameters
 *              by adding `...$args` to the function signature.
 *
 * @param string|array $key   Either a query variable key, or an associative array of query variables.
 * @param string       $value Optional. Either a query variable value, or a URL to act upon.
 * @param string       $url   Optional. A URL to act upon.
 * @return string New URL query string (unescaped).
 */
function add_query_arg(...$args) {
	if (is_array($args[0])) {
		if (count($args) < 2 || false === $args[1]) {
			$uri = $_SERVER['REQUEST_URI'];
		} else {
			$uri = $args[1];
		}
	} else {
		if (count($args) < 3 || false === $args[2]) {
			$uri = $_SERVER['REQUEST_URI'];
		} else {
			$uri = $args[2];
		}
	}

	$frag = strstr($uri, '#');
	if ($frag) {
		$uri = substr($uri, 0, -strlen($frag));
	} else {
		$frag = '';
	}

	if (0 === stripos($uri, 'http://')) {
		$protocol = 'http://';
		$uri      = substr($uri, 7);
	} elseif (0 === stripos($uri, 'https://')) {
		$protocol = 'https://';
		$uri      = substr($uri, 8);
	} else {
		$protocol = '';
	}

	if (strpos($uri, '?') !== false) {
		list($base, $query) = explode('?', $uri, 2);
		$base .= '?';
	} elseif ($protocol || strpos($uri, '=') === false) {
		$base  = $uri . '?';
		$query = '';
	} else {
		$base  = '';
		$query = $uri;
	}

	wp_parse_str($query, $qs);
	$qs = urlencode_deep($qs); // This re-URL-encodes things that were already in the query string.
	if (is_array($args[0])) {
		foreach ($args[0] as $k => $v) {
			$qs[$k] = $v;
		}
	} else {
		$qs[$args[0]] = $args[1];
	}

	foreach ($qs as $k => $v) {
		if (false === $v) {
			unset($qs[$k]);
		}
	}

	$ret = build_query($qs);
	$ret = trim($ret, '?');
	$ret = preg_replace('#=(&|$)#', '$1', $ret);
	$ret = $protocol . $base . $ret . $frag;
	$ret = rtrim($ret, '?');
	$ret = str_replace('?#', '#', $ret);
	return $ret;
}

/**
 * Removes an item or items from a query string.
 *
 * @since 1.5.0
 *
 * @param string|string[] $key   Query key or keys to remove.
 * @param false|string    $query Optional. When false uses the current URL. Default false.
 * @return string New URL query string.
 */
function remove_query_arg($key, $query = false) {
	if (is_array($key)) {
		// Removing multiple keys.
		foreach ($key as $k) {
			$query = add_query_arg($k, false, $query);
		}
		return $query;
	}
	return add_query_arg($key, false, $query);
}
