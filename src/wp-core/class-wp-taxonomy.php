<?php
/**
 * Taxonomy API: WP_Taxonomy class
 *
 * @package WordPress
 * @subpackage Taxonomy
 * Core class used for interacting with taxonomies.
 *
 * @since 4.7.0
 */
final class WP_Taxonomy {
	/**
	 * Taxonomy key.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	public $name;

	/**
	 * Name of the taxonomy shown in the menu. Usually plural.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	public $label;

	/**
	 * Labels object for this taxonomy.
	 *
	 * If not set, tag labels are inherited for non-hierarchical types
	 * and category labels for hierarchical ones.
	 *
	 * @see get_taxonomy_labels()
	 *
	 * @since 4.7.0
	 * @var stdClass
	 */
	public $labels;

	/**
	 * A short descriptive summary of what the taxonomy is for.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	public $description = '';

	/**
	 * Whether a taxonomy is intended for use publicly either via the admin interface or by front-end users.
	 *
	 * @since 4.7.0
	 * @var bool
	 */
	public $public = true;

	/**
	 * Whether the taxonomy is publicly queryable.
	 *
	 * @since 4.7.0
	 * @var bool
	 */
	public $publicly_queryable = true;

	/**
	 * Whether the taxonomy is hierarchical.
	 *
	 * @since 4.7.0
	 * @var bool
	 */
	public $hierarchical = false;

	/**
	 * An array of object types this taxonomy is registered for.
	 *
	 * @since 4.7.0
	 * @var array
	 */
	public $object_type = null;

	/**
	 * Capabilities for this taxonomy.
	 *
	 * @since 4.7.0
	 * @var stdClass
	 */
	public $cap;

	/**
	 * Query var string for this taxonomy.
	 *
	 * @since 4.7.0
	 * @var string|false
	 */
	public $query_var;

	/**
	 * Function that will be called when the count is updated.
	 *
	 * @since 4.7.0
	 * @var callable
	 */
	public $update_count_callback;

	/**
	 * The default term name for this taxonomy. If you pass an array you have
	 * to set 'name' and optionally 'slug' and 'description'.
	 *
	 * @since 5.5.0
	 * @var array|string
	 */
	public $default_term;

	/**
	 * Whether terms in this taxonomy should be sorted in the order they are provided to `wp_set_object_terms()`.
	 *
	 * Use this in combination with `'orderby' => 'term_order'` when fetching terms.
	 *
	 * @since 2.5.0
	 * @var bool|null
	 */
	public $sort = null;

	/**
	 * Array of arguments to automatically use inside `wp_get_object_terms()` for this taxonomy.
	 *
	 * @since 2.6.0
	 * @var array|null
	 */
	public $args = null;

	/**
	 * Whether it is a built-in taxonomy.
	 *
	 * @since 4.7.0
	 * @var bool
	 */
	public $_builtin;

	/**
	 * Constructor.
	 *
	 * See the register_taxonomy() function for accepted arguments for `$args`.
	 *
	 * @since 4.7.0
	 *
	 * @global WP $wp Current WordPress environment instance.
	 *
	 * @param string       $taxonomy    Taxonomy key, must not exceed 32 characters.
	 * @param array|string $object_type Name of the object type for the taxonomy object.
	 * @param array|string $args        Optional. Array or query string of arguments for registering a taxonomy.
	 *                                  Default empty array.
	 */
	public function __construct($taxonomy, $object_type, $args = []) {
		$this->name = $taxonomy;

		$this->set_props($object_type, $args);
	}

	/**
	 * Sets taxonomy properties.
	 *
	 * See the register_taxonomy() function for accepted arguments for `$args`.
	 *
	 * @since 4.7.0
	 *
	 * @param array|string $object_type Name of the object type for the taxonomy object.
	 * @param array|string $args        Array or query string of arguments for registering a taxonomy.
	 */
	public function set_props($object_type, $args) {
		$args = wp_parse_args($args);

		/**
		 * Filters the arguments for registering a taxonomy.
		 *
		 * @since 4.4.0
		 *
		 * @param array    $args        Array of arguments for registering a taxonomy.
		 *                              See the register_taxonomy() function for accepted arguments.
		 * @param string   $taxonomy    Taxonomy key.
		 * @param string[] $object_type Array of names of object types for the taxonomy.
		 */
		$args = apply_filters('register_taxonomy_args', $args, $this->name, (array) $object_type);

		$defaults = [
			'labels'                => [],
			'description'           => '',
			'public'                => true,
			'publicly_queryable'    => null,
			'hierarchical'          => false,
			'capabilities'          => [],
			'query_var'             => $this->name,
			'update_count_callback' => '',
			'default_term'          => null,
			'sort'                  => null,
			'args'                  => null,
			'_builtin'              => false,
		];

		$args = array_merge($defaults, $args);

		// If not set, default to the setting for 'public'.
		if (null === $args['publicly_queryable']) {
			$args['publicly_queryable'] = $args['public'];
		}

		$default_caps = [
			'manage_terms' => 'manage_categories',
			'edit_terms'   => 'manage_categories',
			'delete_terms' => 'manage_categories',
			'assign_terms' => 'edit_posts',
		];

		$args['cap'] = (object) array_merge($default_caps, $args['capabilities']);
		unset($args['capabilities']);

		$args['object_type'] = array_unique((array) $object_type);

		$args['name'] = $this->name;

		// Default taxonomy term.
		if (!empty($args['default_term'])) {
			if (!is_array($args['default_term'])) {
				$args['default_term'] = ['name' => $args['default_term']];
			}
			$args['default_term'] = wp_parse_args(
				$args['default_term'],
				[
					'name'        => '',
					'slug'        => '',
					'description' => '',
				]
			);
		}

		foreach ($args as $property_name => $property_value) {
			$this->$property_name = $property_value;
		}

		// $this->labels = get_taxonomy_labels( $this );
		$this->label = $this->name;
	}
}
