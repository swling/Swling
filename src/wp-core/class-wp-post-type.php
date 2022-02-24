<?php
/**
 * Post API: WP_Post_Type class
 *
 * @package WordPress
 * @subpackage Post
 * @since 4.6.0
 */

/**
 * Core class used for interacting with post types.
 *
 * @since 4.6.0
 *
 * @see register_post_type()
 */
final class WP_Post_Type {
	/**
	 * Post type key.
	 *
	 * @since 4.6.0
	 * @var string $name
	 */
	public $name;

	/**
	 * Name of the post type shown in the menu. Usually plural.
	 *
	 * @since 4.6.0
	 * @var string $label
	 */
	public $label;

	/**
	 * Labels object for this post type.
	 *
	 * If not set, post labels are inherited for non-hierarchical types
	 * and page labels for hierarchical ones.
	 *
	 * @see get_post_type_labels()
	 *
	 * @since 4.6.0
	 * @var stdClass $labels
	 */
	public $labels;

	/**
	 * A short descriptive summary of what the post type is.
	 *
	 * Default empty.
	 *
	 * @since 4.6.0
	 * @var string $description
	 */
	public $description = '';

	/**
	 * Whether a post type is intended for use publicly either via the admin interface or by front-end users.
	 *
	 * While the default settings of $exclude_from_search, $publicly_queryable, $show_ui, and $show_in_nav_menus
	 * are inherited from public, each does not rely on this relationship and controls a very specific intention.
	 *
	 * Default false.
	 *
	 * @since 4.6.0
	 * @var bool $public
	 */
	public $public = false;

	/**
	 * Whether the post type is hierarchical (e.g. page).
	 *
	 * Default false.
	 *
	 * @since 4.6.0
	 * @var bool $hierarchical
	 */
	public $hierarchical = false;

	/**
	 * Whether to exclude posts with this post type from front end search
	 * results.
	 *
	 * Default is the opposite value of $public.
	 *
	 * @since 4.6.0
	 * @var bool $exclude_from_search
	 */
	public $exclude_from_search = null;

	/**
	 * Whether queries can be performed on the front end for the post type as part of `parse_request()`.
	 *
	 * Endpoints would include:
	 * - `?post_type={post_type_key}`
	 * - `?{post_type_key}={single_post_slug}`
	 * - `?{post_type_query_var}={single_post_slug}`
	 *
	 * Default is the value of $public.
	 *
	 * @since 4.6.0
	 * @var bool $publicly_queryable
	 */
	public $publicly_queryable = null;

	/**
	 * Whether to generate and allow a UI for managing this post type in the admin.
	 *
	 * Default is the value of $public.
	 *
	 * @since 4.6.0
	 * @var bool $show_ui
	 */
	public $show_ui = null;

	/**
	 * The string to use to build the read, edit, and delete capabilities.
	 *
	 * May be passed as an array to allow for alternative plurals when using
	 * this argument as a base to construct the capabilities, e.g.
	 * array( 'story', 'stories' ). Default 'post'.
	 *
	 * @since 4.6.0
	 * @var string $capability_type
	 */
	public $capability_type = 'post';

	/**
	 * An array of taxonomy identifiers that will be registered for the post type.
	 *
	 * Taxonomies can be registered later with `register_taxonomy()` or `register_taxonomy_for_object_type()`.
	 *
	 * Default empty array.
	 *
	 * @since 4.6.0
	 * @var array $taxonomies
	 */
	public $taxonomies = [];

	/**
	 * Whether there should be post type archives, or if a string, the archive slug to use.
	 *
	 * Will generate the proper rewrite rules if $rewrite is enabled. Default false.
	 *
	 * @since 4.6.0
	 * @var bool|string $has_archive
	 */
	public $has_archive = false;

	/**
	 * Sets the query_var key for this post type.
	 *
	 * Defaults to $post_type key. If false, a post type cannot be loaded at `?{query_var}={post_slug}`.
	 * If specified as a string, the query `?{query_var_string}={post_slug}` will be valid.
	 *
	 * @since 4.6.0
	 * @var string|bool $query_var
	 */
	public $query_var;

	/**
	 * Whether to allow this post type to be exported.
	 *
	 * Default true.
	 *
	 * @since 4.6.0
	 * @var bool $can_export
	 */
	public $can_export = true;

	/**
	 * Whether to delete posts of this type when deleting a user.
	 *
	 * - If true, posts of this type belonging to the user will be moved to Trash when the user is deleted.
	 * - If false, posts of this type belonging to the user will *not* be trashed or deleted.
	 * - If not set (the default), posts are trashed if post type supports the 'author' feature.
	 *   Otherwise posts are not trashed or deleted.
	 *
	 * Default null.
	 *
	 * @since 4.6.0
	 * @var bool $delete_with_user
	 */
	public $delete_with_user = null;

	/**
	 * Whether this post type is a native or "built-in" post_type.
	 *
	 * Default false.
	 *
	 * @since 4.6.0
	 * @var bool $_builtin
	 */
	public $_builtin = false;

	/**
	 * URL segment to use for edit link of this post type.
	 *
	 * Default 'post.php?post=%d'.
	 *
	 * @since 4.6.0
	 * @var string $_edit_link
	 */
	public $_edit_link = 'post.php?post=%d';

	/**
	 * Post type capabilities.
	 *
	 * @since 4.6.0
	 * @var stdClass $cap
	 */
	public $cap;

	/**
	 * Constructor.
	 *
	 * See the register_post_type() function for accepted arguments for `$args`.
	 *
	 * Will populate object properties from the provided arguments and assign other
	 * default properties based on that information.
	 *
	 * @since 4.6.0
	 *
	 * @see register_post_type()
	 *
	 * @param string       $post_type Post type key.
	 * @param array|string $args      Optional. Array or string of arguments for registering a post type.
	 *                                Default empty array.
	 */
	public function __construct($post_type, $args = []) {
		$this->name = $post_type;

		$this->set_props($args);
	}

	/**
	 * Sets post type properties.
	 *
	 * See the register_post_type() function for accepted arguments for `$args`.
	 *
	 * @since 4.6.0
	 *
	 * @param array|string $args Array or string of arguments for registering a post type.
	 */
	public function set_props($args) {
		$args = wp_parse_args($args);

		/**
		 * Filters the arguments for registering a post type.
		 *
		 * @since 4.4.0
		 *
		 * @param array  $args      Array of arguments for registering a post type.
		 *                          See the register_post_type() function for accepted arguments.
		 * @param string $post_type Post type key.
		 */
		$args = apply_filters('register_post_type_args', $args, $this->name);

		$has_edit_link = !empty($args['_edit_link']);

		// Args prefixed with an underscore are reserved for internal use.
		$defaults = [
			'labels'              => [],
			'description'         => '',
			'public'              => false,
			'hierarchical'        => false,
			'exclude_from_search' => null,
			'publicly_queryable'  => null,
			'show_ui'             => null,
			'capability_type'     => 'post',
			'capabilities'        => [],
			'taxonomies'          => [],
			'has_archive'         => false,
			'query_var'           => true,
			'delete_with_user'    => null,
			'_builtin'            => false,
			'_edit_link'          => 'post.php?post=%d',
		];

		$args = array_merge($defaults, $args);

		$args['name'] = $this->name;

		// If not set, default to the setting for 'public'.
		if (null === $args['publicly_queryable']) {
			$args['publicly_queryable'] = $args['public'];
		}

		// If not set, default to the setting for 'public'.
		if (null === $args['show_ui']) {
			$args['show_ui'] = $args['public'];
		}

		// If not set, default to true if not public, false if public.
		if (null === $args['exclude_from_search']) {
			$args['exclude_from_search'] = !$args['public'];
		}

		// If there's no specified edit link and no UI, remove the edit link.
		if (!$args['show_ui'] && !$has_edit_link) {
			$args['_edit_link'] = '';
		}

		// $this->cap = get_post_type_capabilities((object) $args);
		// unset($args['capabilities']);

		if (is_array($args['capability_type'])) {
			$args['capability_type'] = $args['capability_type'][0];
		}

		if (true === $args['query_var']) {
			$args['query_var'] = $this->name;
		}

		foreach ($args as $property_name => $property_value) {
			$this->$property_name = $property_value;
		}

		// $this->labels = get_post_type_labels( $this );
		$this->label = $this->name;
	}

	/**
	 * Registers the taxonomies for the post type.
	 *
	 * @since 4.6.0
	 */
	public function register_taxonomies() {
		foreach ($this->taxonomies as $taxonomy) {
			register_taxonomy_for_object_type($taxonomy, $this->name);
		}
	}
}
