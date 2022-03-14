<?php
/**
 * Dependencies API: Scripts functions
 *
 * @since 2.6.0
 *
 * @package WordPress
 * @subpackage Dependencies
 */

/**
 * Initialize $wp_scripts if it has not been set.
 *
 * @global WP_Scripts $wp_scripts
 *
 * @since 4.2.0
 *
 * @return WP_Scripts WP_Scripts instance.
 */
function wp_scripts() {
	return WP_Scripts::get_instance();
}

/**
 * Helper function to output a _doing_it_wrong message when applicable.
 *
 * @ignore
 * @since 4.2.0
 * @since 5.5.0 Added the `$handle` parameter.
 *
 * @param string $function Function name.
 * @param string $handle   Optional. Name of the script or stylesheet that was
 *                         registered or enqueued too early. Default empty.
 */
function _wp_scripts_maybe_doing_it_wrong($function, $handle = '') {
	if (did_action('init') || did_action('wp_enqueue_scripts')) {
		return;
	}

	$message = sprintf(
		/* translators: 1: wp_enqueue_scripts, 2: admin_enqueue_scripts, 3: login_enqueue_scripts */
		__('Scripts and styles should not be registered or enqueued until the %1$s, %2$s, or %3$s hooks.'),
		'<code>wp_enqueue_scripts</code>',
		'<code>admin_enqueue_scripts</code>',
		'<code>login_enqueue_scripts</code>'
	);

	if ($handle) {
		$message .= ' ' . sprintf(
			/* translators: %s: Name of the script or stylesheet. */
			__('This notice was triggered by the %s handle.'),
			'<code>' . $handle . '</code>'
		);
	}

	_doing_it_wrong(
		$function,
		$message,
		'3.3.0'
	);
}

/**
 * Prints scripts in document head that are in the $handles queue.
 *
 * Called by admin-header.php and {@see 'wp_head'} hook. Since it is called by wp_head on every page load,
 * the function does not instantiate the WP_Scripts object unless script names are explicitly passed.
 * Makes use of already-instantiated $wp_scripts global if present. Use provided {@see 'wp_print_scripts'}
 * hook to register/enqueue new scripts.
 *
 * @see WP_Scripts::do_item()
 * @global WP_Scripts $wp_scripts The WP_Scripts object for printing scripts.
 *
 * @since 2.1.0
 *
 * @param string|bool|array $handles Optional. Scripts to be printed. Default 'false'.
 * @return string[] On success, an array of handles of processed WP_Dependencies items; otherwise, an empty array.
 */
function wp_print_scripts($handles = false) {
	/**
	 * Fires before scripts in the $handles queue are printed.
	 *
	 * @since 2.1.0
	 */
	do_action('wp_print_scripts');

	if ('' === $handles) {
		// For 'wp_head'.
		$handles = false;
	}

	_wp_scripts_maybe_doing_it_wrong(__FUNCTION__);

	return wp_scripts()->do_items($handles);
}

/**
 * Register a new script.
 *
 * Registers a script to be enqueued later using the wp_enqueue_script() function.
 *
 * @see WP_Dependencies::add()
 * @see WP_Dependencies::add_data()
 *
 * @since 2.1.0
 * @since 4.3.0 A return value was added.
 *
 * @param string           $handle    Name of the script. Should be unique.
 * @param string|bool      $src       Full URL of the script, or path of the script relative to the WordPress root directory.
 *                                    If source is set to false, script is an alias of other scripts it depends on.
 * @param string[]         $deps      Optional. An array of registered script handles this script depends on. Default empty array.
 * @param string|bool|null $ver       Optional. String specifying script version number, if it has one, which is added to the URL
 *                                    as a query string for cache busting purposes. If version is set to false, a version
 *                                    number is automatically added equal to current installed WordPress version.
 *                                    If set to null, no version is added.
 * @param bool             $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
 *                                    Default 'false'.
 * @return bool Whether the script has been registered. True on success, false on failure.
 */
function wp_register_script($handle, $src, $deps = [], $ver = false, $in_footer = false) {
	_wp_scripts_maybe_doing_it_wrong(__FUNCTION__, $handle);

	$wp_scripts = wp_scripts();

	$registered = $wp_scripts->add($handle, $src, $deps, $ver);
	if ($in_footer) {
		$wp_scripts->add_data($handle, 'group', 1);
	}

	return $registered;
}

/**
 * Remove a registered script.
 *
 * Note: there are intentional safeguards in place to prevent critical admin scripts,
 * such as jQuery core, from being unregistered.
 *
 * @see WP_Dependencies::remove()
 *
 * @since 2.1.0
 *
 * @global string $pagenow
 *
 * @param string $handle Name of the script to be removed.
 */
function wp_deregister_script($handle) {
	wp_scripts()->remove($handle);
}

/**
 * Enqueue a script.
 *
 * Registers the script if $src provided (does NOT overwrite), and enqueues it.
 *
 * @see WP_Dependencies::add()
 * @see WP_Dependencies::add_data()
 * @see WP_Dependencies::enqueue()
 *
 * @since 2.1.0
 *
 * @param string           $handle    Name of the script. Should be unique.
 * @param string           $src       Full URL of the script, or path of the script relative to the WordPress root directory.
 *                                    Default empty.
 * @param string[]         $deps      Optional. An array of registered script handles this script depends on. Default empty array.
 * @param string|bool|null $ver       Optional. String specifying script version number, if it has one, which is added to the URL
 *                                    as a query string for cache busting purposes. If version is set to false, a version
 *                                    number is automatically added equal to current installed WordPress version.
 *                                    If set to null, no version is added.
 * @param bool             $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
 *                                    Default 'false'.
 */
function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) {
	_wp_scripts_maybe_doing_it_wrong(__FUNCTION__, $handle);

	$wp_scripts = wp_scripts();

	if ($src || $in_footer) {
		if ($src) {
			$wp_scripts->add($handle, $src, $deps, $ver);
		}

		if ($in_footer) {
			$wp_scripts->add_data($handle, 'group', 1);
		}
	}

	$wp_scripts->enqueue($handle);
}

/**
 * Remove a previously enqueued script.
 *
 * @see WP_Dependencies::dequeue()
 *
 * @since 3.1.0
 *
 * @param string $handle Name of the script to be removed.
 */
function wp_dequeue_script($handle) {
	_wp_scripts_maybe_doing_it_wrong(__FUNCTION__, $handle);

	wp_scripts()->dequeue($handle);
}

/**
 * Prints the script queue in the HTML head on admin pages.
 *
 * Postpones the scripts that were queued for the footer.
 * print_footer_scripts() is called in the footer to print these scripts.
 *
 * @since 2.8.0
 *
 * @see wp_print_scripts()
 *
 * @global bool $concatenate_scripts
 *
 * @return array
 */
function print_head_scripts() {
	$wp_scripts = wp_scripts();
	$wp_scripts->do_head_items();
}

/**
 * Prints the scripts that were queued for the footer or too late for the HTML head.
 *
 * @since 2.8.0
 *
 * @global WP_Scripts $wp_scripts
 * @global bool       $concatenate_scripts
 *
 * @return array
 */
function print_footer_scripts() {
	$wp_scripts = wp_scripts();
	$wp_scripts->do_footer_items();
}
