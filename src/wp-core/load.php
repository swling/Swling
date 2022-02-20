<?php

/**
 * Toggle `$_wp_using_ext_object_cache` on and off without directly
 * touching global.
 *
 * @since 3.7.0
 *
 * @global bool $_wp_using_ext_object_cache
 *
 * @param bool $using Whether external object cache is being used.
 * @return bool The current 'using' setting.
 */
function wp_using_ext_object_cache($using = null) {
	global $_wp_using_ext_object_cache;
	$current_using = $_wp_using_ext_object_cache;
	if (null !== $using) {
		$_wp_using_ext_object_cache = $using;
	}
	return $current_using;
}

/**
 * Start the WordPress object cache.
 *
 * If an object-cache.php file exists in the wp-content directory,
 * it uses that drop-in as an external object cache.
 *
 * @since 3.0.0
 * @access private
 *
 * @global array $wp_filter Stores all of the filters.
 */
function wp_start_object_cache() {
	global $wp_filter;
	static $first_init = true;

	// Only perform the following checks once.

	/**
	 * Filters whether to enable loading of the object-cache.php drop-in.
	 *
	 * This filter runs before it can be used by plugins. It is designed for non-web
	 * runtimes. If false is returned, object-cache.php will never be loaded.
	 *
	 * @since 5.8.0
	 *
	 * @param bool $enable_object_cache Whether to enable loading object-cache.php (if present).
	 *                                  Default true.
	 */
	if ($first_init && apply_filters('enable_loading_object_cache_dropin', true)) {
		if (!function_exists('wp_cache_init')) {
			/*
				 * This is the normal situation. First-run of this function. No
				 * caching backend has been loaded.
				 *
				 * We try to load a custom caching backend, and then, if it
				 * results in a wp_cache_init() function existing, we note
				 * that an external object cache is being used.
			*/
			if (file_exists(WP_CONTENT_DIR . '/object-cache.php')) {
				require_once WP_CONTENT_DIR . '/object-cache.php';
				if (function_exists('wp_cache_init')) {
					wp_using_ext_object_cache(true);
				}

				// Re-initialize any hooks added manually by object-cache.php.
				if ($wp_filter) {
					$wp_filter = WP_Hook::build_preinitialized_hooks($wp_filter);
				}
			}
		} elseif (!wp_using_ext_object_cache() && file_exists(WP_CONTENT_DIR . '/object-cache.php')) {
			/*
				 * Sometimes advanced-cache.php can load object-cache.php before
				 * this function is run. This breaks the function_exists() check
				 * above and can result in wp_using_ext_object_cache() returning
				 * false when actually an external cache is in use.
			*/
			wp_using_ext_object_cache(true);
		}
	}

	if (!wp_using_ext_object_cache()) {
		require_once ABSPATH . WPINC . '/cache.php';
	}

	// require_once ABSPATH . WPINC . '/cache-compat.php';

	/*
		 * If cache supports reset, reset instead of init if already
		 * initialized. Reset signals to the cache that global IDs
		 * have changed and it may need to update keys and cleanup caches.
	*/
	if (!$first_init && function_exists('wp_cache_switch_to_blog')) {
		wp_cache_switch_to_blog(get_current_blog_id());
	} elseif (function_exists('wp_cache_init')) {
		wp_cache_init();
	}

	if (function_exists('wp_cache_add_global_groups')) {
		wp_cache_add_global_groups(['users', 'userlogins', 'usermeta', 'user_meta', 'useremail', 'userslugs', 'site-transient', 'site-options', 'blog-lookup', 'blog-details', 'site-details', 'rss', 'global-posts', 'blog-id-cache', 'networks', 'sites', 'blog_meta']);
		wp_cache_add_non_persistent_groups(['counts', 'plugins']);
	}

	$first_init = false;
}
