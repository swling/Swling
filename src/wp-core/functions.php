<?php
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
