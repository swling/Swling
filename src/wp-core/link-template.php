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
function get_site_url(): string {
	return WP_SITEURL;
}

/**
 * Retrieves the URL for the current site where the front end is accessible.
 *
 * Returns the 'home' option with the appropriate protocol. The protocol will be 'https'
 * if is_ssl() evaluates to true; otherwise, it will be the same as the 'home' option.
 * If `$scheme` is 'http' or 'https', is_ssl() is overridden.
 *
 * @since 3.0.0
 *
 * @param string      $path   Optional. Path relative to the home URL. Default empty.
 * @return string Home URL link with optional path appended.
 */
function home_url(string $path = ''): string {
	return get_home_url($path);
}

/**
 * Retrieves the URL for a given site where the front end is accessible.
 *
 * Returns the 'home' option with the appropriate protocol. The protocol will be 'https'
 * if is_ssl() evaluates to true; otherwise, it will be the same as the 'home' option.
 * If `$scheme` is 'http' or 'https', is_ssl() is overridden.
 *
 * @since 3.0.0
 *
 * @param string      $path    Optional. Path relative to the home URL. Default empty.
 * @param string|null $scheme  Optional. Scheme to give the home URL context. Accepts
 *                             'http', 'https', 'relative', 'rest', or null. Default null.
 * @return string Home URL link with optional path appended.
 */
function get_home_url(string $path = ''): string{
	$url = get_site_url();

	if ($path && is_string($path)) {
		$url .= '/' . ltrim($path, '/');
	}

	/**
	 * Filters the home URL.
	 *
	 * @since 3.0.0
	 *
	 * @param string      $url         The complete home URL including scheme and path.
	 * @param string      $path        Path relative to the home URL. Blank string if no path is specified.
	 */
	return apply_filters('home_url', $url, $path);
}

/**
 * Retrieves the URL to a REST endpoint on a site.
 *
 * Note: The returned URL is NOT escaped.
 *
 * @since 4.4.0
 *
 * @todo Check if this is even necessary
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 *
 * @param string   $path    Optional. REST route. Default '/'.
 * @return string Full URL to the endpoint.
 */
function get_rest_url(string $path = '/'): string{
	$path = '/' . ltrim($path, '/');
	$path = Controller\Dispatcher_Api::get_api_prefix() . $path;
	$url  = get_home_url($path);

	/**
	 * Filters the REST URL.
	 *
	 * Use this filter to adjust the url returned by the get_rest_url() function.
	 *
	 * @since 4.4.0
	 *
	 * @param string   $url     REST URL.
	 * @param string   $path    REST route.
	 */
	return apply_filters('rest_url', $url, $path);
}

/**
 * Retrieves the URL to a REST endpoint.
 *
 * Note: The returned URL is NOT escaped.
 *
 * @since 4.4.0
 *
 * @param string $path   Optional. REST route. Default empty.
 * @return string Full URL to the endpoint.
 */
function rest_url($path = '') {
	return get_rest_url($path);
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

/**
 * Retrieves the full permalink for the current post or post ID.
 *
 * @since 1.0.0
 *
 * @param int|WP_Post $post      Optional. Post ID or post object. Default is the global `$post`.
 * @param bool        $leavename Optional. Whether to keep post name or page name. Default false.
 * @return string The permalink URL.
 */
function get_permalink(WP_Post $post, $leavename = false): string {

	if ('page' == $post->post_type) {
		$path = $post->post_name;
	} else {
		$path = $post->post_type . '/' . $post->ID;
	}

	$permalink = get_home_url($path);

	/**
	 * Filters the permalink for a post.
	 *
	 * Only applies to posts with post_type of 'post'.
	 *
	 * @since 1.5.0
	 *
	 * @param string  $permalink The post's permalink.
	 * @param WP_Post $post      The post in question.
	 * @param bool    $leavename Whether to keep the post name.
	 */
	return apply_filters('post_link', $permalink, $post, $leavename);
}

/**
 * Generate a permalink for a taxonomy term archive.
 *
 * @param WP_Term $term The term object.
 *
 * @return string URL of the taxonomy term archive on success.
 */
function get_term_link(WP_Term $term): string{
	$path = $term->taxonomy . '/' . $term->slug;
	return get_home_url($path);
}
