<?php
/**
 * Creates the initial post types when 'init' action is fired.
 *
 * See {@see 'init'}.
 *
 * @since 2.9.0
 */
function create_initial_post_types() {
	register_post_type(
		'post',
		[
			'labels'           => [
				'name_admin_bar' => _x('Post', 'add new from admin bar'),
			],
			'public'           => true,
			'_builtin'         => true, /* internal use only. don't use this when registering your own post type. */
			'_edit_link' => 'post.php?post=%d', /* internal use only. don't use this when registering your own post type. */
			'capability_type' => 'post',
			'hierarchical'     => false,
			'query_var'        => false,
			'delete_with_user' => true,
		]
	);

	register_post_type(
		'page',
		[
			'labels'             => [
				'name_admin_bar' => _x('Page', 'add new from admin bar'),
			],
			'public'             => true,
			'publicly_queryable' => false,
			'_builtin'           => true, /* internal use only. don't use this when registering your own post type. */
			'_edit_link' => 'post.php?post=%d', /* internal use only. don't use this when registering your own post type. */
			'capability_type' => 'page',
			'hierarchical'       => true,
			'query_var'          => false,
			'delete_with_user'   => true,
		]
	);

	register_post_status(
		'publish',
		[
			'label'    => _x('Published', 'post status'),
			'public'   => true,
			'_builtin' => true, /* internal use only. */
			/* translators: %s: Number of published posts. */
			'label_count' => _n_noop(
				'Published <span class="count">(%s)</span>',
				'Published <span class="count">(%s)</span>'
			),
		]
	);

	register_post_status(
		'future',
		[
			'label'     => _x('Scheduled', 'post status'),
			'protected' => true,
			'_builtin'  => true, /* internal use only. */
			/* translators: %s: Number of scheduled posts. */
			'label_count' => _n_noop(
				'Scheduled <span class="count">(%s)</span>',
				'Scheduled <span class="count">(%s)</span>'
			),
		]
	);

	register_post_status(
		'draft',
		[
			'label'         => _x('Draft', 'post status'),
			'protected'     => true,
			'_builtin'      => true, /* internal use only. */
			/* translators: %s: Number of draft posts. */
			'label_count' => _n_noop(
				'Draft <span class="count">(%s)</span>',
				'Drafts <span class="count">(%s)</span>'
			),
			'date_floating' => true,
		]
	);

	register_post_status(
		'pending',
		[
			'label'         => _x('Pending', 'post status'),
			'protected'     => true,
			'_builtin'      => true, /* internal use only. */
			/* translators: %s: Number of pending posts. */
			'label_count' => _n_noop(
				'Pending <span class="count">(%s)</span>',
				'Pending <span class="count">(%s)</span>'
			),
			'date_floating' => true,
		]
	);

	register_post_status(
		'private',
		[
			'label'    => _x('Private', 'post status'),
			'private'  => true,
			'_builtin' => true, /* internal use only. */
			/* translators: %s: Number of private posts. */
			'label_count' => _n_noop(
				'Private <span class="count">(%s)</span>',
				'Private <span class="count">(%s)</span>'
			),
		]
	);

	register_post_status(
		'trash',
		[
			'label'                     => _x('Trash', 'post status'),
			'internal'                  => true,
			'_builtin'                  => true, /* internal use only. */
			/* translators: %s: Number of trashed posts. */
			'label_count' => _n_noop(
				'Trash <span class="count">(%s)</span>',
				'Trash <span class="count">(%s)</span>'
			),
			'show_in_admin_status_list' => true,
		]
	);

	register_post_status(
		'auto-draft',
		[
			'label'    => 'auto-draft',
			'internal' => true,
			'_builtin' => true, /* internal use only. */
			'date_floating' => true,
		]
	);

	register_post_status(
		'inherit',
		[
			'label'    => 'inherit',
			'internal' => true,
			'_builtin' => true, /* internal use only. */
			'exclude_from_search' => false,
		]
	);
}

/**
 * Registers a post type.
 *
 * Note: Post type registrations should not be hooked before the
 * {@see 'init'} action. Also, any taxonomy connections should be
 * registered via the `$taxonomies` argument to ensure consistency
 * when hooks such as {@see 'parse_query'} or {@see 'pre_get_posts'}
 * are used.
 *
 * Post types can support any number of built-in core features such
 * as meta boxes, custom fields, post thumbnails, post statuses,
 * comments, and more. See the `$supports` argument for a complete
 * list of supported features.
 *
 * @since 2.9.0
 * @since 3.0.0 The `show_ui` argument is now enforced on the new post screen.
 * @since 4.4.0 The `show_ui` argument is now enforced on the post type listing
 *              screen and post editing screen.
 * @since 4.6.0 Post type object returned is now an instance of `WP_Post_Type`.
 * @since 4.7.0 Introduced `show_in_rest`, `rest_base` and `rest_controller_class`
 *              arguments to register the post type in REST API.
 * @since 5.0.0 The `template` and `template_lock` arguments were added.
 * @since 5.3.0 The `supports` argument will now accept an array of arguments for a feature.
 * @since 5.9.0 The `rest_namespace` argument was added.
 *
 * @global array $wp_post_types List of post types.
 *
 * @param string       $post_type Post type key. Must not exceed 20 characters and may
 *                                only contain lowercase alphanumeric characters, dashes,
 *                                and underscores. See sanitize_key().
 * @param array|string $args {
 *     Array or string of arguments for registering a post type.
 *
 *     @type string       $label                 Name of the post type shown in the menu. Usually plural.
 *                                               Default is value of $labels['name'].
 *     @type string[]     $labels                An array of labels for this post type. If not set, post
 *                                               labels are inherited for non-hierarchical types and page
 *                                               labels for hierarchical ones. See get_post_type_labels() for a full
 *                                               list of supported labels.
 *     @type string       $description           A short descriptive summary of what the post type is.
 *                                               Default empty.
 *     @type bool         $public                Whether a post type is intended for use publicly either via
 *                                               the admin interface or by front-end users. While the default
 *                                               settings of $exclude_from_search, $publicly_queryable, $show_ui,
 *                                               and $show_in_nav_menus are inherited from $public, each does not
 *                                               rely on this relationship and controls a very specific intention.
 *                                               Default false.
 *     @type bool         $hierarchical          Whether the post type is hierarchical (e.g. page). Default false.
 *     @type bool         $exclude_from_search   Whether to exclude posts with this post type from front end search
 *                                               results. Default is the opposite value of $public.
 *     @type bool         $publicly_queryable    Whether queries can be performed on the front end for the post type
 *                                               as part of parse_request(). Endpoints would include:
 *                                               * ?post_type={post_type_key}
 *                                               * ?{post_type_key}={single_post_slug}
 *                                               * ?{post_type_query_var}={single_post_slug}
 *                                               If not set, the default is inherited from $public.
 *     @type bool         $show_ui               Whether to generate and allow a UI for managing this post type in the
 *                                               admin. Default is value of $public.
 *     @type bool|string  $show_in_menu          Where to show the post type in the admin menu. To work, $show_ui
 *                                               must be true. If true, the post type is shown in its own top level
 *                                               menu. If false, no menu is shown. If a string of an existing top
 *                                               level menu ('tools.php' or 'edit.php?post_type=page', for example), the
 *                                               post type will be placed as a sub-menu of that.
 *                                               Default is value of $show_ui.
 *     @type bool         $show_in_nav_menus     Makes this post type available for selection in navigation menus.
 *                                               Default is value of $public.
 *     @type bool         $show_in_admin_bar     Makes this post type available via the admin bar. Default is value
 *                                               of $show_in_menu.
 *     @type bool         $show_in_rest          Whether to include the post type in the REST API. Set this to true
 *                                               for the post type to be available in the block editor.
 *     @type string       $rest_base             To change the base URL of REST API route. Default is $post_type.
 *     @type string       $rest_namespace        To change the namespace URL of REST API route. Default is wp/v2.
 *     @type string       $rest_controller_class REST API controller class name. Default is 'WP_REST_Posts_Controller'.
 *     @type int          $menu_position         The position in the menu order the post type should appear. To work,
 *                                               $show_in_menu must be true. Default null (at the bottom).
 *     @type string       $menu_icon             The URL to the icon to be used for this menu. Pass a base64-encoded
 *                                               SVG using a data URI, which will be colored to match the color scheme
 *                                               -- this should begin with 'data:image/svg+xml;base64,'. Pass the name
 *                                               of a Dashicons helper class to use a font icon, e.g.
 *                                               'dashicons-chart-pie'. Pass 'none' to leave div.wp-menu-image empty
 *                                               so an icon can be added via CSS. Defaults to use the posts icon.
 *     @type string|array $capability_type       The string to use to build the read, edit, and delete capabilities.
 *                                               May be passed as an array to allow for alternative plurals when using
 *                                               this argument as a base to construct the capabilities, e.g.
 *                                               array('story', 'stories'). Default 'post'.
 *     @type string[]     $capabilities          Array of capabilities for this post type. $capability_type is used
 *                                               as a base to construct capabilities by default.
 *                                               See get_post_type_capabilities().
 *     @type bool         $map_meta_cap          Whether to use the internal default meta capability handling.
 *                                               Default false.
 *     @type array        $supports              Core feature(s) the post type supports. Serves as an alias for calling
 *                                               add_post_type_support() directly. Core features include 'title',
 *                                               'editor', 'comments', 'revisions', 'trackbacks', 'author', 'excerpt',
 *                                               'page-attributes', 'thumbnail', 'custom-fields', and 'post-formats'.
 *                                               Additionally, the 'revisions' feature dictates whether the post type
 *                                               will store revisions, and the 'comments' feature dictates whether the
 *                                               comments count will show on the edit screen. A feature can also be
 *                                               specified as an array of arguments to provide additional information
 *                                               about supporting that feature.
 *                                               Example: `array( 'my_feature', array( 'field' => 'value' ) )`.
 *                                               Default is an array containing 'title' and 'editor'.
 *     @type callable     $register_meta_box_cb  Provide a callback function that sets up the meta boxes for the
 *                                               edit form. Do remove_meta_box() and add_meta_box() calls in the
 *                                               callback. Default null.
 *     @type string[]     $taxonomies            An array of taxonomy identifiers that will be registered for the
 *                                               post type. Taxonomies can be registered later with register_taxonomy()
 *                                               or register_taxonomy_for_object_type().
 *                                               Default empty array.
 *     @type bool|string  $has_archive           Whether there should be post type archives, or if a string, the
 *                                               archive slug to use. Will generate the proper rewrite rules if
 *                                               $rewrite is enabled. Default false.
 *     @type bool|array   $rewrite               {
 *         Triggers the handling of rewrites for this post type. To prevent rewrite, set to false.
 *         Defaults to true, using $post_type as slug. To specify rewrite rules, an array can be
 *         passed with any of these keys:
 *
 *         @type string $slug       Customize the permastruct slug. Defaults to $post_type key.
 *         @type bool   $with_front Whether the permastruct should be prepended with WP_Rewrite::$front.
 *                                  Default true.
 *         @type bool   $feeds      Whether the feed permastruct should be built for this post type.
 *                                  Default is value of $has_archive.
 *         @type bool   $pages      Whether the permastruct should provide for pagination. Default true.
 *         @type int    $ep_mask    Endpoint mask to assign. If not specified and permalink_epmask is set,
 *                                  inherits from $permalink_epmask. If not specified and permalink_epmask
 *                                  is not set, defaults to EP_PERMALINK.
 *     }
 *     @type string|bool  $query_var             Sets the query_var key for this post type. Defaults to $post_type
 *                                               key. If false, a post type cannot be loaded at
 *                                               ?{query_var}={post_slug}. If specified as a string, the query
 *                                               ?{query_var_string}={post_slug} will be valid.
 *     @type bool         $can_export            Whether to allow this post type to be exported. Default true.
 *     @type bool         $delete_with_user      Whether to delete posts of this type when deleting a user.
 *                                               * If true, posts of this type belonging to the user will be moved
 *                                                 to Trash when the user is deleted.
 *                                               * If false, posts of this type belonging to the user will *not*
 *                                                 be trashed or deleted.
 *                                               * If not set (the default), posts are trashed if post type supports
 *                                                 the 'author' feature. Otherwise posts are not trashed or deleted.
 *                                               Default null.
 *     @type array        $template              Array of blocks to use as the default initial state for an editor
 *                                               session. Each item should be an array containing block name and
 *                                               optional attributes. Default empty array.
 *     @type string|false $template_lock         Whether the block template should be locked if $template is set.
 *                                               * If set to 'all', the user is unable to insert new blocks,
 *                                                 move existing blocks and delete blocks.
 *                                               * If set to 'insert', the user is able to move existing blocks
 *                                                 but is unable to insert new blocks and delete blocks.
 *                                               Default false.
 *     @type bool         $_builtin              FOR INTERNAL USE ONLY! True if this post type is a native or
 *                                               "built-in" post_type. Default false.
 *     @type string       $_edit_link            FOR INTERNAL USE ONLY! URL segment to use for edit link of
 *                                               this post type. Default 'post.php?post=%d'.
 * }
 * @return WP_Post_Type|WP_Error The registered post type object on success,
 *                               WP_Error object on failure.
 */
function register_post_type($post_type, $args = []) {
	global $wp_post_types;

	if (!is_array($wp_post_types)) {
		$wp_post_types = [];
	}

	// Sanitize post type name.
	$post_type = sanitize_key($post_type);

	if (empty($post_type) || strlen($post_type) > 20) {
		_doing_it_wrong(__FUNCTION__, __('Post type names must be between 1 and 20 characters in length.'), '4.2.0');
		return new WP_Error('post_type_length_invalid', __('Post type names must be between 1 and 20 characters in length.'));
	}

	$post_type_object = new WP_Post_Type($post_type, $args);

	$wp_post_types[$post_type] = $post_type_object;

	$post_type_object->register_taxonomies();

	/**
	 * Fires after a post type is registered.
	 *
	 * @since 3.3.0
	 * @since 4.6.0 Converted the `$post_type` parameter to accept a `WP_Post_Type` object.
	 *
	 * @param string       $post_type        Post type.
	 * @param WP_Post_Type $post_type_object Arguments used to register the post type.
	 */
	do_action('registered_post_type', $post_type, $post_type_object);

	return $post_type_object;
}

/**
 * Whether the post type is hierarchical.
 *
 * A false return value might also mean that the post type does not exist.
 *
 * @since 3.0.0
 *
 * @see get_post_type_object()
 *
 * @param string $post_type Post type name
 * @return bool Whether post type is hierarchical.
 */
function is_post_type_hierarchical($post_type) {
	if (!post_type_exists($post_type)) {
		return false;
	}

	$post_type = get_post_type_object($post_type);
	return $post_type->hierarchical;
}

/**
 * Determines whether a post type is registered.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 3.0.0
 *
 * @see get_post_type_object()
 *
 * @param string $post_type Post type name.
 * @return bool Whether post type is registered.
 */
function post_type_exists($post_type) {
	return (bool) get_post_type_object($post_type);
}

/**
 * Retrieves the post type of the current post or of a given post.
 *
 * @since 2.1.0
 *
 * @param int|WP_Post|null $post Optional. Post ID or post object. Default is global $post.
 * @return string|false          Post type on success, false on failure.
 */
function get_post_type($post = null) {
	$post = get_post($post);
	if ($post) {
		return $post->post_type;
	}

	return false;
}

/**
 * Retrieves a post type object by name.
 *
 * @since 3.0.0
 * @since 4.6.0 Object returned is now an instance of `WP_Post_Type`.
 *
 * @global array $wp_post_types List of post types.
 *
 * @see register_post_type()
 *
 * @param string $post_type The name of a registered post type.
 * @return WP_Post_Type|null WP_Post_Type object if it exists, null otherwise.
 */
function get_post_type_object($post_type) {
	global $wp_post_types;

	if (!is_scalar($post_type) || empty($wp_post_types[$post_type])) {
		return null;
	}

	return $wp_post_types[$post_type];
}

/**
 * Get a list of all registered post type objects.
 *
 * @since 2.9.0
 *
 * @global array $wp_post_types List of post types.
 *
 * @see register_post_type() for accepted arguments.
 *
 * @param array|string $args     Optional. An array of key => value arguments to match against
 *                               the post type objects. Default empty array.
 * @param string       $output   Optional. The type of output to return. Accepts post type 'names'
 *                               or 'objects'. Default 'names'.
 * @param string       $operator Optional. The logical operation to perform. 'or' means only one
 *                               element from the array needs to match; 'and' means all elements
 *                               must match; 'not' means no elements may match. Default 'and'.
 * @return string[]|WP_Post_Type[] An array of post type names or objects.
 */
function get_post_types($args = [], $output = 'names', $operator = 'and') {
	global $wp_post_types;

	$field = ('names' === $output) ? 'name' : false;

	return wp_filter_object_list($wp_post_types, $args, $operator, $field);
}

/**
 * Register a post status. Do not use before init.
 *
 * A simple function for creating or modifying a post status based on the
 * parameters given. The function will accept an array (second optional
 * parameter), along with a string for the post status name.
 *
 * Arguments prefixed with an _underscore shouldn't be used by plugins and themes.
 *
 * @since 3.0.0
 *
 * @global stdClass[] $wp_post_statuses Inserts new post status object into the list
 *
 * @param string       $post_status Name of the post status.
 * @param array|string $args {
 *     Optional. Array or string of post status arguments.
 *
 *     @type bool|string $label                     A descriptive name for the post status marked
 *                                                  for translation. Defaults to value of $post_status.
 *     @type bool|array  $label_count               Descriptive text to use for nooped plurals.
 *                                                  Default array of $label, twice.
 *     @type bool        $exclude_from_search       Whether to exclude posts with this post status
 *                                                  from search results. Default is value of $internal.
 *     @type bool        $_builtin                  Whether the status is built-in. Core-use only.
 *                                                  Default false.
 *     @type bool        $public                    Whether posts of this status should be shown
 *                                                  in the front end of the site. Default false.
 *     @type bool        $internal                  Whether the status is for internal use only.
 *                                                  Default false.
 *     @type bool        $protected                 Whether posts with this status should be protected.
 *                                                  Default false.
 *     @type bool        $private                   Whether posts with this status should be private.
 *                                                  Default false.
 *     @type bool        $publicly_queryable        Whether posts with this status should be publicly-
 *                                                  queryable. Default is value of $public.
 *     @type bool        $show_in_admin_all_list    Whether to include posts in the edit listing for
 *                                                  their post type. Default is the opposite value
 *                                                  of $internal.
 *     @type bool        $show_in_admin_status_list Show in the list of statuses with post counts at
 *                                                  the top of the edit listings,
 *                                                  e.g. All (12) | Published (9) | My Custom Status (2)
 *                                                  Default is the opposite value of $internal.
 *     @type bool        $date_floating             Whether the post has a floating creation date.
 *                                                  Default to false.
 * }
 * @return object
 */
function register_post_status($post_status, $args = []) {
	global $wp_post_statuses;

	if (!is_array($wp_post_statuses)) {
		$wp_post_statuses = [];
	}

	// Args prefixed with an underscore are reserved for internal use.
	$defaults = [
		'label'                     => false,
		'label_count'               => false,
		'exclude_from_search'       => null,
		'_builtin'                  => false,
		'public'                    => null,
		'internal'                  => null,
		'protected'                 => null,
		'private'                   => null,
		'publicly_queryable'        => null,
		'show_in_admin_status_list' => null,
		'show_in_admin_all_list'    => null,
		'date_floating'             => null,
	];
	$args = wp_parse_args($args, $defaults);
	$args = (object) $args;

	$post_status = sanitize_key($post_status);
	$args->name  = $post_status;

	// Set various defaults.
	if (null === $args->public && null === $args->internal && null === $args->protected && null === $args->private) {
		$args->internal = true;
	}

	if (null === $args->public) {
		$args->public = false;
	}

	if (null === $args->private) {
		$args->private = false;
	}

	if (null === $args->protected) {
		$args->protected = false;
	}

	if (null === $args->internal) {
		$args->internal = false;
	}

	if (null === $args->publicly_queryable) {
		$args->publicly_queryable = $args->public;
	}

	if (null === $args->exclude_from_search) {
		$args->exclude_from_search = $args->internal;
	}

	if (null === $args->show_in_admin_all_list) {
		$args->show_in_admin_all_list = !$args->internal;
	}

	if (null === $args->show_in_admin_status_list) {
		$args->show_in_admin_status_list = !$args->internal;
	}

	if (null === $args->date_floating) {
		$args->date_floating = false;
	}

	if (false === $args->label) {
		$args->label = $post_status;
	}

	if (false === $args->label_count) {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle,WordPress.WP.I18n.NonSingularStringLiteralPlural
		$args->label_count = _n_noop($args->label, $args->label);
	}

	$wp_post_statuses[$post_status] = $args;

	return $args;
}

/**
 * Retrieve a post status object by name.
 *
 * @since 3.0.0
 *
 * @global stdClass[] $wp_post_statuses List of post statuses.
 *
 * @see register_post_status()
 *
 * @param string $post_status The name of a registered post status.
 * @return stdClass|null A post status object.
 */
function get_post_status_object($post_status) {
	global $wp_post_statuses;

	if (empty($wp_post_statuses[$post_status])) {
		return null;
	}

	return $wp_post_statuses[$post_status];
}

/**
 * Get a list of post statuses.
 *
 * @since 3.0.0
 *
 * @global stdClass[] $wp_post_statuses List of post statuses.
 *
 * @see register_post_status()
 *
 * @param array|string $args     Optional. Array or string of post status arguments to compare against
 *                               properties of the global `$wp_post_statuses objects`. Default empty array.
 * @param string       $output   Optional. The type of output to return, either 'names' or 'objects'. Default 'names'.
 * @param string       $operator Optional. The logical operation to perform. 'or' means only one element
 *                               from the array needs to match; 'and' means all elements must match.
 *                               Default 'and'.
 * @return string[]|stdClass[] A list of post status names or objects.
 */
function get_post_stati($args = [], $output = 'names', $operator = 'and') {
	global $wp_post_statuses;

	$field = ('names' === $output) ? 'name' : false;

	return wp_filter_object_list($wp_post_statuses, $args, $operator, $field);
}

################################################################################## 以下为重构函数

/**
 * Insert or update a post.
 *
 * If the $postarr parameter has 'ID' set to a value, then post will be updated.
 *
 * @since 1.0.0
 *
 * @param array $postarr {
 *     An array of elements that make up a post to update or insert.
 *
 * @return int The post ID on success. The value 0 on failure.
 */
function wp_insert_post(array $postarr): int{
	$handler = new Model\WPDB_Handler_Post;
	return $handler->insert($postarr);
}

/**
 * Retrieves post data given a post ID or post object.
 *
 * @since 1.5.1
 *
 * @global WP_Post $post Global post object.
 *
 * @param int|WP_Post|null $post   Optional. Post ID. Defaults to global $post.
 * @return WP_Post|false           a `WP_Post` instance is returned or false on failure.
 */
function get_post(int $post_id = 0) {
	if (empty($post) && isset($GLOBALS['post'])) {
		$post = $GLOBALS['post'];
	}

	return WP_Post::get_instance($post_id);
}

/**
 * Update a post with new post data.
 *
 * The date does not have to be set for drafts. You can set the date and it will
 * not be overridden.
 *
 * @since 1.0.0
 * @param array|object $postarr          Optional. Post data. Arrays are expected to be escaped,
 *                                       objects are not. See wp_insert_post() for accepted arguments.
 *                                       Default array.
 * @return int The post ID on success. The value 0 on failure.
 */
function wp_update_post(array $postarr): int{
	$handler = new Model\WPDB_Handler_Post;
	return $handler->update($postarr);
}

/**
 * Trash or delete a post or page.
 * @param int  $postid       Post ID
 * @param bool $force_delete Optional. Whether to bypass Trash and force deletion.
 *
 * @return int Post ID, 0 on error.
 */
function wp_delete_post(int $post_id, bool $force_delete = false): int{
	$post = get_post($post_id);
	if (!$post) {
		return 0;
	}

	$handler = new Model\WPDB_Handler_Post;
	return $handler->delete($post->ID);
}
