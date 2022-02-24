<?php
/**
 * Creates or modifies a taxonomy object.
 *
 * Note: Do not use before the {@see 'init'} hook.
 *
 * A simple function for creating or modifying a taxonomy object based on
 * the parameters given. If modifying an existing taxonomy object, note
 * that the `$object_type` value from the original registration will be
 * overwritten.
 *
 * @since 2.3.0
 * @since 4.2.0 Introduced `show_in_quick_edit` argument.
 * @since 4.4.0 The `show_ui` argument is now enforced on the term editing screen.
 * @since 4.4.0 The `public` argument now controls whether the taxonomy can be queried on the front end.
 * @since 4.5.0 Introduced `publicly_queryable` argument.
 * @since 4.7.0 Introduced `show_in_rest`, 'rest_base' and 'rest_controller_class'
 *              arguments to register the Taxonomy in REST API.
 * @since 5.1.0 Introduced `meta_box_sanitize_cb` argument.
 * @since 5.4.0 Added the registered taxonomy object as a return value.
 * @since 5.5.0 Introduced `default_term` argument.
 * @since 5.9.0 Introduced `rest_namespace` argument.
 *
 * @global WP_Taxonomy[] $wp_taxonomies Registered taxonomies.
 *
 * @param string       $taxonomy    Taxonomy key, must not exceed 32 characters.
 * @param array|string $object_type Object type or array of object types with which the taxonomy should be associated.
 * @param array|string $args        {
 *     Optional. Array or query string of arguments for registering a taxonomy.
 *
 *     @type string[]      $labels                An array of labels for this taxonomy. By default, Tag labels are
 *                                                used for non-hierarchical taxonomies, and Category labels are used
 *                                                for hierarchical taxonomies. See accepted values in
 *                                                get_taxonomy_labels(). Default empty array.
 *     @type string        $description           A short descriptive summary of what the taxonomy is for. Default empty.
 *     @type bool          $public                Whether a taxonomy is intended for use publicly either via
 *                                                the admin interface or by front-end users. The default settings
 *                                                of `$publicly_queryable`, `$show_ui`, and `$show_in_nav_menus`
 *                                                are inherited from `$public`.
 *     @type bool          $publicly_queryable    Whether the taxonomy is publicly queryable.
 *                                                If not set, the default is inherited from `$public`
 *     @type bool          $hierarchical          Whether the taxonomy is hierarchical. Default false.
 *     @type bool          $show_ui               Whether to generate and allow a UI for managing terms in this taxonomy in
 *                                                the admin. If not set, the default is inherited from `$public`
 *                                                (default true).
 *     @type bool          $show_in_menu          Whether to show the taxonomy in the admin menu. If true, the taxonomy is
 *                                                shown as a submenu of the object type menu. If false, no menu is shown.
 *                                                `$show_ui` must be true. If not set, default is inherited from `$show_ui`
 *                                                (default true).
 *     @type bool          $show_in_nav_menus     Makes this taxonomy available for selection in navigation menus. If not
 *                                                set, the default is inherited from `$public` (default true).
 *     @type bool          $show_in_rest          Whether to include the taxonomy in the REST API. Set this to true
 *                                                for the taxonomy to be available in the block editor.
 *     @type string        $rest_base             To change the base url of REST API route. Default is $taxonomy.
 *     @type string        $rest_namespace        To change the namespace URL of REST API route. Default is wp/v2.
 *     @type string        $rest_controller_class REST API Controller class name. Default is 'WP_REST_Terms_Controller'.
 *     @type bool          $show_tagcloud         Whether to list the taxonomy in the Tag Cloud Widget controls. If not set,
 *                                                the default is inherited from `$show_ui` (default true).
 *     @type bool          $show_in_quick_edit    Whether to show the taxonomy in the quick/bulk edit panel. It not set,
 *                                                the default is inherited from `$show_ui` (default true).
 *     @type bool          $show_admin_column     Whether to display a column for the taxonomy on its post type listing
 *                                                screens. Default false.
 *     @type bool|callable $meta_box_cb           Provide a callback function for the meta box display. If not set,
 *                                                post_categories_meta_box() is used for hierarchical taxonomies, and
 *                                                post_tags_meta_box() is used for non-hierarchical. If false, no meta
 *                                                box is shown.
 *     @type callable      $meta_box_sanitize_cb  Callback function for sanitizing taxonomy data saved from a meta
 *                                                box. If no callback is defined, an appropriate one is determined
 *                                                based on the value of `$meta_box_cb`.
 *     @type string[]      $capabilities {
 *         Array of capabilities for this taxonomy.
 *
 *         @type string $manage_terms Default 'manage_categories'.
 *         @type string $edit_terms   Default 'manage_categories'.
 *         @type string $delete_terms Default 'manage_categories'.
 *         @type string $assign_terms Default 'edit_posts'.
 *     }
 *     @type bool|array    $rewrite {
 *         Triggers the handling of rewrites for this taxonomy. Default true, using $taxonomy as slug. To prevent
 *         rewrite, set to false. To specify rewrite rules, an array can be passed with any of these keys:
 *
 *         @type string $slug         Customize the permastruct slug. Default `$taxonomy` key.
 *         @type bool   $with_front   Should the permastruct be prepended with WP_Rewrite::$front. Default true.
 *         @type bool   $hierarchical Either hierarchical rewrite tag or not. Default false.
 *         @type int    $ep_mask      Assign an endpoint mask. Default `EP_NONE`.
 *     }
 *     @type string|bool   $query_var             Sets the query var key for this taxonomy. Default `$taxonomy` key. If
 *                                                false, a taxonomy cannot be loaded at `?{query_var}={term_slug}`. If a
 *                                                string, the query `?{query_var}={term_slug}` will be valid.
 *     @type callable      $update_count_callback Works much like a hook, in that it will be called when the count is
 *                                                updated. Default _update_post_term_count() for taxonomies attached
 *                                                to post types, which confirms that the objects are published before
 *                                                counting them. Default _update_generic_term_count() for taxonomies
 *                                                attached to other object types, such as users.
 *     @type string|array  $default_term {
 *         Default term to be used for the taxonomy.
 *
 *         @type string $name         Name of default term.
 *         @type string $slug         Slug for default term. Default empty.
 *         @type string $description  Description for default term. Default empty.
 *     }
 *     @type bool          $sort                  Whether terms in this taxonomy should be sorted in the order they are
 *                                                provided to `wp_set_object_terms()`. Default null which equates to false.
 *     @type array         $args                  Array of arguments to automatically use inside `wp_get_object_terms()`
 *                                                for this taxonomy.
 *     @type bool          $_builtin              This taxonomy is a "built-in" taxonomy. INTERNAL USE ONLY!
 *                                                Default false.
 * }
 * @return WP_Taxonomy|WP_Error The registered taxonomy object on success, WP_Error object on failure.
 */
function register_taxonomy($taxonomy, $object_type, $args = []) {
	global $wp_taxonomies;

	if (!is_array($wp_taxonomies)) {
		$wp_taxonomies = [];
	}

	$args = wp_parse_args($args);

	if (empty($taxonomy) || strlen($taxonomy) > 32) {
		_doing_it_wrong(__FUNCTION__, __('Taxonomy names must be between 1 and 32 characters in length.'), '4.2.0');
		return new WP_Error('taxonomy_length_invalid', __('Taxonomy names must be between 1 and 32 characters in length.'));
	}

	$taxonomy_object          = new WP_Taxonomy($taxonomy, $object_type, $args);
	$wp_taxonomies[$taxonomy] = $taxonomy_object;

	/**
	 * Fires after a taxonomy is registered.
	 *
	 * @since 3.3.0
	 *
	 * @param string       $taxonomy    Taxonomy slug.
	 * @param array|string $object_type Object type or array of object types.
	 * @param array        $args        Array of taxonomy registration arguments.
	 */
	do_action('registered_taxonomy', $taxonomy, $object_type, (array) $taxonomy_object);

	return $taxonomy_object;
}

/**
 * Core Taxonomy API
 *
 * @package WordPress
 * @subpackage Taxonomy
 */

//
// Taxonomy registration.
//

/**
 * Creates the initial taxonomies.
 *
 * This function fires twice: in wp-settings.php before plugins are loaded (for
 * backward compatibility reasons), and again on the {@see 'init'} action. We must
 * avoid registering rewrite rules before the {@see 'init'} action.
 *
 * @since 2.8.0
 * @since 5.9.0 Added `'wp_template_part_area'` taxonomy.
 *
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 */
function create_initial_taxonomies() {
	register_taxonomy(
		'category',
		'post',
		[
			'hierarchical'      => true,
			'query_var'         => 'category_name',
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'_builtin'          => true,
			'capabilities'      => [
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'edit_categories',
				'delete_terms' => 'delete_categories',
				'assign_terms' => 'assign_categories',
			],
		]
	);

	register_taxonomy(
		'post_tag',
		'post',
		[
			'hierarchical'      => false,
			'query_var'         => 'tag',
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'_builtin'          => true,
			'capabilities'      => [
				'manage_terms' => 'manage_post_tags',
				'edit_terms'   => 'edit_post_tags',
				'delete_terms' => 'delete_post_tags',
				'assign_terms' => 'assign_post_tags',
			],
		]
	);
}

/**
 * Retrieves a list of registered taxonomy names or objects.
 *
 * @since 3.0.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param array  $args     Optional. An array of `key => value` arguments to match against the taxonomy objects.
 *                         Default empty array.
 * @param string $output   Optional. The type of output to return in the array. Accepts either taxonomy 'names'
 *                         or 'objects'. Default 'names'.
 * @param string $operator Optional. The logical operation to perform. Accepts 'and' or 'or'. 'or' means only
 *                         one element from the array needs to match; 'and' means all elements must match.
 *                         Default 'and'.
 * @return string[]|WP_Taxonomy[] An array of taxonomy names or objects.
 */
function get_taxonomies($args = [], $output = 'names', $operator = 'and') {
	global $wp_taxonomies;

	$field = ('names' === $output) ? 'name' : false;

	return wp_filter_object_list($wp_taxonomies, $args, $operator, $field);
}

/**
 * Return the names or objects of the taxonomies which are registered for the requested object or object type, such as
 * a post object or post type name.
 *
 * Example:
 *
 *     $taxonomies = get_object_taxonomies( 'post' );
 *
 * This results in:
 *
 *     Array( 'category', 'post_tag' )
 *
 * @since 2.3.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param string|string[]|WP_Post $object Name of the type of taxonomy object, or an object (row from posts)
 * @param string                  $output Optional. The type of output to return in the array. Accepts either
 *                                        'names' or 'objects'. Default 'names'.
 * @return string[]|WP_Taxonomy[] The names or objects of all taxonomies of `$object_type`.
 */
function get_object_taxonomies($object, $output = 'names') {
	global $wp_taxonomies;

	$object = (array) $object;

	$taxonomies = [];
	foreach ((array) $wp_taxonomies as $tax_name => $tax_obj) {
		if (array_intersect($object, (array) $tax_obj->object_type)) {
			if ('names' === $output) {
				$taxonomies[] = $tax_name;
			} else {
				$taxonomies[$tax_name] = $tax_obj;
			}
		}
	}

	return $taxonomies;
}

/**
 * Retrieves the taxonomy object of $taxonomy.
 *
 * The get_taxonomy function will first check that the parameter string given
 * is a taxonomy object and if it is, it will return it.
 *
 * @since 2.3.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param string $taxonomy Name of taxonomy object to return.
 * @return WP_Taxonomy|false The Taxonomy Object or false if $taxonomy doesn't exist.
 */
function get_taxonomy($taxonomy) {
	global $wp_taxonomies;

	if (!taxonomy_exists($taxonomy)) {
		return false;
	}

	return $wp_taxonomies[$taxonomy];
}

/**
 * Determines whether the taxonomy name exists.
 *
 * Formerly is_taxonomy(), introduced in 2.3.0.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 3.0.0
 *
 * @global WP_Taxonomy[] $wp_taxonomies The registered taxonomies.
 *
 * @param string $taxonomy Name of taxonomy object.
 * @return bool Whether the taxonomy exists.
 */
function taxonomy_exists($taxonomy) {
	global $wp_taxonomies;

	return isset($wp_taxonomies[$taxonomy]);
}

/**
 * Determines whether the taxonomy object is hierarchical.
 *
 * Checks to make sure that the taxonomy is an object first. Then Gets the
 * object, and finally returns the hierarchical value in the object.
 *
 * A false return value might also mean that the taxonomy does not exist.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 2.3.0
 *
 * @param string $taxonomy Name of taxonomy object.
 * @return bool Whether the taxonomy is hierarchical.
 */
function is_taxonomy_hierarchical($taxonomy) {
	if (!taxonomy_exists($taxonomy)) {
		return false;
	}

	$taxonomy = get_taxonomy($taxonomy);
	return $taxonomy->hierarchical;
}
