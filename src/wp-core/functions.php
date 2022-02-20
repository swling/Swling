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
