<?php
/**
 * General template tags that can go anywhere in a template.
 *
 * @package WordPress
 * @subpackage Template
 */

/**
 * Load header template.
 *
 * Includes the header template for a theme or if a name is specified then a
 * specialised header will be included.
 *
 * For the parameter, if the file is called "header-special.php" then specify
 * "special".
 *
 * @since 1.5.0
 * @since 5.5.0 A return value was added.
 * @since 5.5.0 The `$args` parameter was added.
 *
 * @param string $name The name of the specialised header.
 * @param array  $args Optional. Additional arguments passed to the header template.
 *                     Default empty array.
 * @return void|false Void on success, false if the template does not exist.
 */
function get_header($name = null, $args = []) {
	/**
	 * Fires before the header template file is loaded.
	 *
	 * @since 2.1.0
	 * @since 2.8.0 The `$name` parameter was added.
	 * @since 5.5.0 The `$args` parameter was added.
	 *
	 * @param string|null $name Name of the specific header file to use. Null for the default header.
	 * @param array       $args Additional arguments passed to the header template.
	 */
	do_action('get_header', $name, $args);

	$templates = [];
	$name      = (string) $name;
	if ('' !== $name) {
		$templates[] = "header-{$name}.php";
	}

	$templates[] = 'header.php';

	if (!locate_template($templates, true, true, $args)) {
		return false;
	}
}

/**
 * Load footer template.
 *
 * Includes the footer template for a theme or if a name is specified then a
 * specialised footer will be included.
 *
 * For the parameter, if the file is called "footer-special.php" then specify
 * "special".
 *
 * @since 1.5.0
 * @since 5.5.0 A return value was added.
 * @since 5.5.0 The `$args` parameter was added.
 *
 * @param string $name The name of the specialised footer.
 * @param array  $args Optional. Additional arguments passed to the footer template.
 *                     Default empty array.
 * @return void|false Void on success, false if the template does not exist.
 */
function get_footer($name = null, $args = []) {
	/**
	 * Fires before the footer template file is loaded.
	 *
	 * @since 2.1.0
	 * @since 2.8.0 The `$name` parameter was added.
	 * @since 5.5.0 The `$args` parameter was added.
	 *
	 * @param string|null $name Name of the specific footer file to use. Null for the default footer.
	 * @param array       $args Additional arguments passed to the footer template.
	 */
	do_action('get_footer', $name, $args);

	$templates = [];
	$name      = (string) $name;
	if ('' !== $name) {
		$templates[] = "footer-{$name}.php";
	}

	$templates[] = 'footer.php';

	if (!locate_template($templates, true, true, $args)) {
		return false;
	}
}

/**
 * Load sidebar template.
 *
 * Includes the sidebar template for a theme or if a name is specified then a
 * specialised sidebar will be included.
 *
 * For the parameter, if the file is called "sidebar-special.php" then specify
 * "special".
 *
 * @since 1.5.0
 * @since 5.5.0 A return value was added.
 * @since 5.5.0 The `$args` parameter was added.
 *
 * @param string $name The name of the specialised sidebar.
 * @param array  $args Optional. Additional arguments passed to the sidebar template.
 *                     Default empty array.
 * @return void|false Void on success, false if the template does not exist.
 */
function get_sidebar($name = null, $args = []) {
	/**
	 * Fires before the sidebar template file is loaded.
	 *
	 * @since 2.2.0
	 * @since 2.8.0 The `$name` parameter was added.
	 * @since 5.5.0 The `$args` parameter was added.
	 *
	 * @param string|null $name Name of the specific sidebar file to use. Null for the default sidebar.
	 * @param array       $args Additional arguments passed to the sidebar template.
	 */
	do_action('get_sidebar', $name, $args);

	$templates = [];
	$name      = (string) $name;
	if ('' !== $name) {
		$templates[] = "sidebar-{$name}.php";
	}

	$templates[] = 'sidebar.php';

	if (!locate_template($templates, true, true, $args)) {
		return false;
	}
}

/**
 * Loads a template part into a template.
 *
 * Provides a simple mechanism for child themes to overload reusable sections of code
 * in the theme.
 *
 * Includes the named template part for a theme or if a name is specified then a
 * specialised part will be included. If the theme contains no {slug}.php file
 * then no template will be included.
 *
 * The template is included using require, not require_once, so you may include the
 * same template part multiple times.
 *
 * For the $name parameter, if the file is called "{slug}-special.php" then specify
 * "special".
 *
 * @since 3.0.0
 * @since 5.5.0 A return value was added.
 * @since 5.5.0 The `$args` parameter was added.
 *
 * @param string $slug The slug name for the generic template.
 * @param string $name The name of the specialised template.
 * @param array  $args Optional. Additional arguments passed to the template.
 *                     Default empty array.
 * @return void|false Void on success, false if the template does not exist.
 */
function get_template_part($slug, $name = null, $args = []) {
	/**
	 * Fires before the specified template part file is loaded.
	 *
	 * The dynamic portion of the hook name, `$slug`, refers to the slug name
	 * for the generic template part.
	 *
	 * @since 3.0.0
	 * @since 5.5.0 The `$args` parameter was added.
	 *
	 * @param string      $slug The slug name for the generic template.
	 * @param string|null $name The name of the specialized template.
	 * @param array       $args Additional arguments passed to the template.
	 */
	do_action("get_template_part_{$slug}", $slug, $name, $args);

	$templates = [];
	$name      = (string) $name;
	if ('' !== $name) {
		$templates[] = "{$slug}-{$name}.php";
	}

	$templates[] = "{$slug}.php";

	/**
	 * Fires before an attempt is made to locate and load a template part.
	 *
	 * @since 5.2.0
	 * @since 5.5.0 The `$args` parameter was added.
	 *
	 * @param string   $slug      The slug name for the generic template.
	 * @param string   $name      The name of the specialized template.
	 * @param string[] $templates Array of template files to search for, in order.
	 * @param array    $args      Additional arguments passed to the template.
	 */
	do_action('get_template_part', $slug, $name, $templates, $args);

	if (!locate_template($templates, true, false, $args)) {
		return false;
	}
}

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches in the STYLESHEETPATH before TEMPLATEPATH and wp-includes/theme-compat
 * so that themes which inherit from a parent theme can just overload one file.
 *
 * @since 2.7.0
 * @since 5.5.0 The `$args` parameter was added.
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool         $load           If true the template file will be loaded if it is found.
 * @param bool         $require_once   Whether to require_once or require. Has no effect if `$load` is false.
 *                                     Default true.
 * @param array        $args           Optional. Additional arguments passed to the template.
 *                                     Default empty array.
 * @return string The template filename if one is located.
 */
function locate_template($template_names, $load = false, $require_once = true, $args = []) {
	$located = '';
	foreach ((array) $template_names as $template_name) {
		if (!$template_name) {
			continue;
		}

		if (file_exists(TEMPLATEPATH . '/' . $template_name)) {
			$located = TEMPLATEPATH . '/' . $template_name;
			break;
		}
	}

	if ($load && '' !== $located) {
		load_template($located, $require_once, $args);
	}

	return $located;
}

/**
 * Require the template file with WordPress environment.
 *
 * The globals are set up for the template file to ensure that the WordPress
 * environment is available from within the function. The query variables are
 * also available.
 *
 * @since 1.5.0
 * @since 5.5.0 The `$args` parameter was added.
 *
 * @global array      $posts
 * @global WP_Post    $post          Global post object.
 * @global bool       $wp_did_header
 * @global WP_Query   $wp_query      WordPress Query object.
 * @global WP_Rewrite $wp_rewrite    WordPress rewrite component.
 * @global wpdb       $wpdb          WordPress database abstraction object.
 * @global string     $wp_version
 * @global WP         $wp            Current WordPress environment instance.
 * @global int        $id
 * @global WP_Comment $comment       Global comment object.
 * @global int        $user_ID
 *
 * @param string $_template_file Path to template file.
 * @param bool   $require_once   Whether to require_once or require. Default true.
 * @param array  $args           Optional. Additional arguments passed to the template.
 *                               Default empty array.
 */
function load_template($_template_file, $require_once = true, $args = []) {
	global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;

	if (is_array($wp_query->query_vars)) {
		/*
			 * This use of extract() cannot be removed. There are many possible ways that
			 * templates could depend on variables that it creates existing, and no way to
			 * detect and deprecate it.
			 *
			 * Passing the EXTR_SKIP flag is the safest option, ensuring globals and
			 * function variables cannot be overwritten.
		*/
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract($wp_query->query_vars, EXTR_SKIP);
	}

	if (isset($s)) {
		$s = esc_attr($s);
	}

	if ($require_once) {
		require_once $_template_file;
	} else {
		require $_template_file;
	}
}
