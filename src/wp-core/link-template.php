<?php

/**
 * Retrieves the URL for a given site where WordPress application files
 * (e.g. wp-blog-header.php or the wp-admin/ folder) are accessible.
 *
 * Returns the 'site_url' option with the appropriate protocol, 'https' if
 * is_ssl() and 'http' otherwise. If `$scheme` is 'http' or 'https',
 * `is_ssl()` is overridden.
 *
 * @since 3.0.0
 *
 * @param int|null    $blog_id Optional. Site ID. Default null (current site).
 * @param string      $path    Optional. Path relative to the site URL. Default empty.
 * @param string|null $scheme  Optional. Scheme to give the site URL context. Accepts
 *                             'http', 'https', 'login', 'login_post', 'admin', or
 *                             'relative'. Default null.
 * @return string Site URL link with optional path appended.
 */
function get_site_url() {
	return WP_SITEURL;
}

/**
 * Retrieves a URL within the plugins or mu-plugins directory.
 *
 * Defaults to the plugins directory URL if no arguments are supplied.
 *
 * @since 2.6.0
 *
 * @param string $path   Optional. Extra path appended to the end of the URL, including
 *                       the relative directory if $plugin is supplied. Default empty.
 * @param string $plugin Optional. A full path to a file inside a plugin or mu-plugin.
 *                       The URL will be relative to its directory. Default empty.
 *                       Typically this is done by passing `__FILE__` as the argument.
 * @return string Plugins URL link with optional paths appended.
 */
function plugins_url($path = '', $plugin = '') {

	$path   = wp_normalize_path($path);
	$plugin = wp_normalize_path($plugin);
	$url    = WP_PLUGIN_URL;

	if ($plugin) {
		$path_arr = explode(WP_PLUGIN_DIR, $plugin, 2);
		$folder   = isset($path_arr[1]) ? dirname($path_arr[1]) : '';
		$url .= '/' . ltrim($folder, '/');
	}

	if ($path && is_string($path)) {
		$url .= '/' . ltrim($path, '/');
	}

	/**
	 * Filters the URL to the plugins directory.
	 *
	 * @since 2.8.0
	 *
	 * @param string $url    The complete URL to the plugins directory including scheme and path.
	 * @param string $path   Path relative to the URL to the plugins directory. Blank string
	 *                       if no path is specified.
	 * @param string $plugin The plugin file path to be relative to. Blank string if no plugin
	 *                       is specified.
	 */
	return apply_filters('plugins_url', $url, $path, $plugin);
}
