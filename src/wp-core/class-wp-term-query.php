<?php

/**
 * Taxonomy API: WP_Term_Query class.
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 4.6.0
 */

/**
 * Class used for querying terms.
 *
 * @since 4.6.0
 *
 * @see WP_Term_Query::__construct() for accepted arguments.
 */
class WP_Term_Query {

	/**
	 * SQL string used to perform database query.
	 *
	 * @since 4.6.0
	 * @var string
	 */
	public $request;

	/**
	 * Metadata query container.
	 *
	 * @since 4.6.0
	 * @var WP_Meta_Query A meta query instance.
	 */
	public $meta_query = false;

	/**
	 * Metadata query clauses.
	 *
	 * @since 4.6.0
	 * @var array
	 */
	protected $meta_query_clauses;

	/**
	 * SQL query clauses.
	 *
	 * @since 4.6.0
	 * @var array
	 */
	protected $sql_clauses = [
		'distinct' => '',
		'from'     => '',
		'join'     => '',
		'where'    => 'WHERE 1=1 ',
		'orderby'  => '',
		'limits'   => '',
	];

	/**
	 * Query vars set by the user.
	 *
	 * @since 4.6.0
	 * @var array
	 */
	public $query_vars = [];

	/**
	 * Default values for query vars.
	 *
	 * @since 4.6.0
	 * @var array
	 */
	public $query_var_defaults;

	/**
	 * List of terms located by the query.
	 *
	 * @since 4.6.0
	 * @var array
	 */
	public $terms;

	/**
	 * Constructor.
	 *
	 * Sets up the term query, based on the query vars passed.
	 *
	 * @since 4.6.0
	 * @since 4.6.0 Introduced 'term_taxonomy_id' parameter.
	 * @since 4.7.0 Introduced 'object_ids' parameter.
	 * @since 4.9.0 Added 'slug__in' support for 'orderby'.
	 * @since 5.1.0 Introduced the 'meta_compare_key' parameter.
	 * @since 5.3.0 Introduced the 'meta_type_key' parameter.
	 *
	 * @param string|array $query {
	 *     Optional. Array or query string of term query parameters. Default empty.
	 *
	 *     @type string|string[] $taxonomy               Taxonomy name, or array of taxonomy names, to which results
	 *                                                   should be limited.
	 *     @type int|int[]       $object_ids             Object ID, or array of object IDs. Results will be
	 *                                                   limited to terms associated with these objects.
	 *     @type string          $orderby                Field(s) to order terms by. Accepts:
	 *                                                   - Term fields ('name', 'slug', 'term_group', 'term_id', 'id',
	 *                                                     'description', 'parent', 'term_order'). Unless `$object_ids`
	 *                                                     is not empty, 'term_order' is treated the same as 'term_id'.
	 *                                                   - 'count' to use the number of objects associated with the term.
	 *                                                   - 'include' to match the 'order' of the `$include` param.
	 *                                                   - 'slug__in' to match the 'order' of the `$slug` param.
	 *                                                   - 'meta_value'
	 *                                                   - 'meta_value_num'.
	 *                                                   - The value of `$meta_key`.
	 *                                                   - The array keys of `$meta_query`.
	 *                                                   - 'none' to omit the ORDER BY clause.
	 *                                                   Default 'name'.
	 *     @type string          $order                  Whether to order terms in ascending or descending order.
	 *                                                   Accepts 'ASC' (ascending) or 'DESC' (descending).
	 *                                                   Default 'ASC'.
	 *     @type bool|int        $hide_empty             Whether to hide terms not assigned to any posts. Accepts
	 *                                                   1|true or 0|false. Default 1|true.
	 *     @type int[]|string    $include                Array or comma/space-separated string of term IDs to include.
	 *                                                   Default empty array.
	 *     @type int[]|string    $exclude                Array or comma/space-separated string of term IDs to exclude.
	 *                                                   If `$include` is non-empty, `$exclude` is ignored.
	 *                                                   Default empty array.
	 *     @type int[]|string    $exclude_tree           Array or comma/space-separated string of term IDs to exclude
	 *                                                   along with all of their descendant terms. If `$include` is
	 *                                                   non-empty, `$exclude_tree` is ignored. Default empty array.
	 *     @type int|string      $number                 Maximum number of terms to return. Accepts ''|0 (all) or any
	 *                                                   positive number. Default ''|0 (all). Note that `$number` may
	 *                                                   not return accurate results when coupled with `$object_ids`.
	 *                                                   See #41796 for details.
	 *     @type int             $offset                 The number by which to offset the terms query. Default empty.
	 *     @type string          $fields                 Term fields to query for. Accepts:
	 *                                                   - 'all' Returns an array of complete term objects (`WP_Term[]`).
	 *                                                   - 'all_with_object_id' Returns an array of term objects
	 *                                                     with the 'object_id' param (`WP_Term[]`). Works only
	 *                                                     when the `$object_ids` parameter is populated.
	 *                                                   - 'ids' Returns an array of term IDs (`int[]`).
	 *                                                   - 'tt_ids' Returns an array of term taxonomy IDs (`int[]`).
	 *                                                   - 'names' Returns an array of term names (`string[]`).
	 *                                                   - 'slugs' Returns an array of term slugs (`string[]`).
	 *                                                   - 'count' Returns the number of matching terms (`int`).
	 *                                                   - 'id=>parent' Returns an associative array of parent term IDs,
	 *                                                      keyed by term ID (`int[]`).
	 *                                                   - 'id=>name' Returns an associative array of term names,
	 *                                                      keyed by term ID (`string[]`).
	 *                                                   - 'id=>slug' Returns an associative array of term slugs,
	 *                                                      keyed by term ID (`string[]`).
	 *                                                   Default 'all'.
	 *     @type bool            $count                  Whether to return a term count. If true, will take precedence
	 *                                                   over `$fields`. Default false.
	 *     @type string|string[] $name                   Name or array of names to return term(s) for.
	 *                                                   Default empty.
	 *     @type string|string[] $slug                   Slug or array of slugs to return term(s) for.
	 *                                                   Default empty.
	 *     @type int|int[]       $term_taxonomy_id       Term taxonomy ID, or array of term taxonomy IDs,
	 *                                                   to match when querying terms.
	 *     @type bool            $hierarchical           Whether to include terms that have non-empty descendants
	 *                                                   (even if `$hide_empty` is set to true). Default true.
	 *     @type string          $search                 Search criteria to match terms. Will be SQL-formatted with
	 *                                                   wildcards before and after. Default empty.
	 *     @type string          $name__like             Retrieve terms with criteria by which a term is LIKE
	 *                                                   `$name__like`. Default empty.
	 *     @type string          $description__like      Retrieve terms where the description is LIKE
	 *                                                   `$description__like`. Default empty.
	 *     @type bool            $pad_counts             Whether to pad the quantity of a term's children in the
	 *                                                   quantity of each term's "count" object variable.
	 *                                                   Default false.
	 *     @type string          $get                    Whether to return terms regardless of ancestry or whether the
	 *                                                   terms are empty. Accepts 'all' or '' (disabled).
	 *                                                   Default ''.
	 *     @type int             $child_of               Term ID to retrieve child terms of. If multiple taxonomies
	 *                                                   are passed, `$child_of` is ignored. Default 0.
	 *     @type int             $parent                 Parent term ID to retrieve direct-child terms of.
	 *                                                   Default empty.
	 *     @type bool            $childless              True to limit results to terms that have no children.
	 *                                                   This parameter has no effect on non-hierarchical taxonomies.
	 *                                                   Default false.
	 *     @type string          $cache_domain           Unique cache key to be produced when this query is stored in
	 *                                                   an object cache. Default 'core'.
	 *     @type bool            $update_term_meta_cache Whether to prime meta caches for matched terms. Default true.
	 *     @type string|string[] $meta_key               Meta key or keys to filter by.
	 *     @type string|string[] $meta_value             Meta value or values to filter by.
	 *     @type string          $meta_compare           MySQL operator used for comparing the meta value.
	 *                                                   See WP_Meta_Query::__construct for accepted values and default value.
	 *     @type string          $meta_compare_key       MySQL operator used for comparing the meta key.
	 *                                                   See WP_Meta_Query::__construct for accepted values and default value.
	 *     @type string          $meta_type              MySQL data type that the meta_value column will be CAST to for comparisons.
	 *                                                   See WP_Meta_Query::__construct for accepted values and default value.
	 *     @type string          $meta_type_key          MySQL data type that the meta_key column will be CAST to for comparisons.
	 *                                                   See WP_Meta_Query::__construct for accepted values and default value.
	 *     @type array           $meta_query             An associative array of WP_Meta_Query arguments.
	 *                                                   See WP_Meta_Query::__construct for accepted values.
	 * }
	 */
	public function __construct($query = '') {
		$this->query_var_defaults = [
			'taxonomy'               => null,
			'object_ids'             => null,
			'orderby'                => 'name',
			'order'                  => 'ASC',
			'hide_empty'             => true,
			'include'                => [],
			'exclude'                => [],
			'exclude_tree'           => [],
			'number'                 => '',
			'offset'                 => '',
			'fields'                 => 'all',
			'count'                  => false,
			'name'                   => '',
			'slug'                   => '',
			'term_taxonomy_id'       => '',
			'hierarchical'           => true,
			'search'                 => '',
			'name__like'             => '',
			'description__like'      => '',
			'pad_counts'             => false,
			'get'                    => '',
			'child_of'               => 0,
			'parent'                 => '',
			'childless'              => false,
			'cache_domain'           => 'core',
			'update_term_meta_cache' => true,
			'meta_query'             => '',
			'meta_key'               => '',
			'meta_value'             => '',
			'meta_type'              => '',
			'meta_compare'           => '',
		];

		$this->parse_query(wp_parse_args($query, $this->query_var_defaults));
	}

	/**
	 * Parse arguments passed to the term query with default query parameters.
	 *
	 * @since 4.6.0
	 *
	 * @param string|array $query WP_Term_Query arguments. See WP_Term_Query::__construct()
	 */
	public function parse_query($query = '') {
		if (empty($query)) {
			$query = $this->query_vars;
		}

		$taxonomies = isset($query['taxonomy']) ? (array) $query['taxonomy'] : null;

		/**
		 * Filters the terms query default arguments.
		 *
		 * Use {@see 'get_terms_args'} to filter the passed arguments.
		 *
		 * @since 4.4.0
		 *
		 * @param array    $defaults   An array of default get_terms() arguments.
		 * @param string[] $taxonomies An array of taxonomy names.
		 */
		$this->query_var_defaults = apply_filters('get_terms_defaults', $this->query_var_defaults, $taxonomies);

		$query = wp_parse_args($query, $this->query_var_defaults);

		$query['number'] = absint($query['number']);
		$query['offset'] = absint($query['offset']);

		// 'parent' overrides 'child_of'.
		if (0 < (int) $query['parent']) {
			$query['child_of'] = false;
		}

		if ('all' === $query['get']) {
			$query['childless']    = false;
			$query['child_of']     = 0;
			$query['hide_empty']   = 0;
			$query['hierarchical'] = false;
			$query['pad_counts']   = false;
		}

		$query['taxonomy'] = $taxonomies;

		$this->query_vars = $query;

		/**
		 * Fires after term query vars have been parsed.
		 *
		 * @since 4.6.0
		 *
		 * @param WP_Term_Query $query Current instance of WP_Term_Query.
		 */
		do_action('parse_term_query', $this);
	}

	/**
	 * Sets up the query and retrieves the results.
	 *
	 * The return type varies depending on the value passed to `$args['fields']`. See
	 * WP_Term_Query::get_terms() for details.
	 *
	 * @since 4.6.0
	 *
	 * @param string|array $query Array or URL query string of parameters.
	 * @return WP_Term[]|int[]|string[]|string Array of terms, or number of terms as numeric string
	 *                                         when 'count' is passed as a query var.
	 */
	public function query(array $args = []) {
		if (!empty($args)) {
			$this->parse_query($args);
		}

		return $this->get_terms();
	}

	/**
	 * Retrieves the query results.
	 *
	 * The return type varies depending on the value passed to `$args['fields']`.
	 *
	 * The following will result in an array of `WP_Term` objects being returned:
	 *
	 *   - 'all'
	 *   - 'all_with_object_id'
	 *
	 * The following will result in a numeric string being returned:
	 *
	 *   - 'count'
	 *
	 * The following will result in an array of text strings being returned:
	 *
	 *   - 'id=>name'
	 *   - 'id=>slug'
	 *   - 'names'
	 *   - 'slugs'
	 *
	 * The following will result in an array of numeric strings being returned:
	 *
	 *   - 'id=>parent'
	 *
	 * The following will result in an array of integers being returned:
	 *
	 *   - 'ids'
	 *   - 'tt_ids'
	 *
	 * @since 4.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return WP_Term[]|int[]|string[]|string Array of terms, or number of terms as numeric string
	 *                                         when 'count' is passed as a query var.
	 */
	public function get_terms() {
		// Join Object IDs
		$this->parse_object_ids();

		// General Query
		$this->parse_where();

		// Meta Query
		$this->parse_meta_query();

		// Search Name / Slug
		$search = $this->query_vars['search'] ?? '';
		if ($search) {
			$this->get_search_sql($search);
		}

		// Order By
		$this->parse_orderby();

		// limits
		$this->parse_limits();

		/**
		 * Fires before terms are retrieved.
		 *
		 * @since 4.6.0
		 *
		 * @param WP_Term_Query $query Current instance of WP_Term_Query (passed by reference).
		 */
		do_action_ref_array('pre_get_terms', [ & $this]);

		/**
		 * Filters the terms query SQL clauses.
		 *
		 * @since 3.1.0
		 *
		 * @param string[] $pieces     Array of query SQL clauses.
		 * @param string[] $taxonomies An array of taxonomy names.
		 * @param array    $args       An array of term query arguments.
		 */
		// $clauses = $this->sql_clauses;
		// $clauses = apply_filters( 'terms_clauses', $this->sql_clauses, $taxonomies, $args );

		global $wpdb;
		$fields                    = $wpdb->terms . '.*';
		$this->sql_clauses['from'] = "$wpdb->terms";

		// Get Cache
		$cache = $this->get_query_cache();
		if (false !== $cache) {
			if ('all' === $fields || 'all_with_object_id' === $fields) {
				$cache = $this->populate_terms($cache);
			}

			$this->terms = $cache;
			return $this->terms;
		}

		extract($this->sql_clauses);
		$this->request = "SELECT {$distinct} {$fields} FROM {$from} {$join} {$where} {$orderby} {$limits}";
		$terms         = $wpdb->get_results($this->request);

		// Set Cache
		$this->set_query_cache($terms);

		// Filter Fields
		if ('t_ids' === $this->query_vars['fields']) {
			foreach ($terms as $term) {
				$_terms[] = (int) $term->term_id;
			}
		}
		if (!empty($_terms)) {
			$terms = $_terms;
		}

		/**
		 * Filters the terms array before the query takes place.
		 *
		 * Return a non-null value to bypass WordPress' default term queries.
		 *
		 * @since 5.3.0
		 *
		 * @param array|null    $terms Return an array of term data to short-circuit WP's term query,
		 *                             or null to allow WP queries to run normally.
		 * @param WP_Term_Query $query The WP_Term_Query instance, passed by reference.
		 */
		$this->terms = apply_filters_ref_array('terms_pre_query', [$terms, &$this]);

		return $this->terms;
	}

	protected function parse_object_ids() {
		global $wpdb;
		$object_ids = (array) $this->query_vars['object_ids'];
		if (empty($object_ids)) {
			return;
		}

		$this->sql_clauses['join'] .= " INNER JOIN {$wpdb->term_relationships} AS tr ON (tr.term_taxonomy_id = {$wpdb->terms}.term_id)";
		$ids = implode(', ', array_map('intval', $object_ids));
		$this->sql_clauses['where'] .= " AND tr.object_id IN ($ids)";
	}

	protected function parse_where() {
		global $wpdb;
		$qv = &$this->query_vars;

		foreach ($qv as $key => $value) {
			if (empty($value)) {
				continue;
			}

			// get terms by term string column
			if (in_array($key, ['taxonomy', 'slug', 'name'])) {
				if (is_array($value)) {
					$this->sql_clauses['where'] .= " AND {$key} IN ('" . implode("', '", array_map('esc_sql', $value)) . "')";
				} else {
					$this->sql_clauses['where'] .= $wpdb->prepare(" AND {$wpdb->terms}.{$key} = %s ", $value);
				}
				continue;
			}

			// get terms by term int column
			if (in_array($key, ['parent', 'count', 'term_id', 'term_group'])) {
				if (is_array($value)) {
					$this->sql_clauses['where'] .= " AND {$key} IN ('" . implode("', '", array_map('intval', $value)) . "')";
				} else {
					$this->sql_clauses['where'] .= $wpdb->prepare(" AND {$wpdb->terms}.{$key} = %d ", $value);
				}
				continue;
			}
		}
	}

	private function parse_meta_query() {
		global $wpdb;
		$qv               = &$this->query_vars;
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars($qv);
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars($qv);

		if (!empty($this->meta_query->queries)) {
			$clauses = $this->meta_query->get_sql('term', $wpdb->terms, 'term_id', $this);
			$this->sql_clauses['join'] .= $clauses['join'];
			$this->sql_clauses['where'] .= $clauses['where'];
		}
	}

	protected function parse_limits() {
		$qv     = &$this->query_vars;
		$number = $qv['number'];
		$offset = $qv['offset'];

		// Don't limiqve query results when we have to descend the family tree.
		if ($number) {
			if ($offset) {
				$limits = 'LIMIT ' . $offset . ',' . $number;
			} else {
				$limits = 'LIMIT ' . $number;
			}
		} else {
			$limits = '';
		}

		$this->sql_clauses['limits'] = $limits;
	}

	/**
	 * Parse and sanitize 'orderby' keys passed to the term query.
	 *
	 * @since 4.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $orderby_raw Alias for the field to order by.
	 * @return string|false Value to used in the ORDER clause. False otherwise.
	 */
	protected function parse_orderby() {
		$qv = &$this->query_vars;

		$orderby            = $qv['orderby'];
		$order              = $this->parse_order($qv['order']);
		$_orderby           = strtolower($orderby);
		$maybe_orderby_meta = false;

		if (in_array($_orderby, ['term_id', 'name', 'slug', 'count', 'parent', 'taxonomy', 'term_group'], true)) {
			$orderby = "$_orderby";
		} elseif ('term_order' === $_orderby) {
			$orderby = 'tr.term_order';
		} elseif ('none' === $_orderby) {
			$orderby = '';
		} else {
			$orderby = 'name';

			// This may be a value of orderby related to meta.
			$maybe_orderby_meta = true;
		}

		/**
		 * Filters the ORDERBY clause of the terms query.
		 *
		 * @since 2.8.0
		 *
		 * @param string   $orderby    `ORDERBY` clause of the terms query.
		 * @param array    $args       An array of term query arguments.
		 * @param string[] $taxonomies An array of taxonomy names.
		 */
		$orderby = apply_filters('get_terms_orderby', $orderby, $qv, $qv['taxonomy']);

		// Run after the 'get_terms_orderby' filter for backward compatibility.
		if ($maybe_orderby_meta) {
			$maybe_orderby_meta = $this->parse_orderby_meta($_orderby);
			if ($maybe_orderby_meta) {
				$orderby = $maybe_orderby_meta;
			}
		}

		if ($orderby) {
			$orderby = "ORDER BY $orderby";
		}

		$this->sql_clauses['orderby'] = $orderby ? "$orderby $order" : '';
	}

	/**
	 * Generate the ORDER BY clause for an 'orderby' param that is potentially related to a meta query.
	 *
	 * @since 4.6.0
	 *
	 * @param string $orderby_raw Raw 'orderby' value passed to WP_Term_Query.
	 * @return string ORDER BY clause.
	 */
	protected function parse_orderby_meta($orderby_raw) {
		$orderby = '';

		// Tell the meta query to generate its SQL, so we have access to table aliases.
		$this->meta_query->get_sql('term', 't', 'term_id');
		$meta_clauses = $this->meta_query->get_clauses();
		if (!$meta_clauses || !$orderby_raw) {
			return $orderby;
		}

		$allowed_keys       = [];
		$primary_meta_key   = null;
		$primary_meta_query = reset($meta_clauses);
		if (!empty($primary_meta_query['key'])) {
			$primary_meta_key = $primary_meta_query['key'];
			$allowed_keys[]   = $primary_meta_key;
		}
		$allowed_keys[] = 'meta_value';
		$allowed_keys[] = 'meta_value_num';
		$allowed_keys   = array_merge($allowed_keys, array_keys($meta_clauses));

		if (!in_array($orderby_raw, $allowed_keys, true)) {
			return $orderby;
		}

		switch ($orderby_raw) {
			case $primary_meta_key:
			case 'meta_value':
				if (!empty($primary_meta_query['type'])) {
					$orderby = "CAST({$primary_meta_query['alias']}.meta_value AS {$primary_meta_query['cast']})";
				} else {
					$orderby = "{$primary_meta_query['alias']}.meta_value";
				}
				break;

			case 'meta_value_num':
				$orderby = "{$primary_meta_query['alias']}.meta_value+0";
				break;

			default:
				if (array_key_exists($orderby_raw, $meta_clauses)) {
					// $orderby corresponds to a meta_query clause.
					$meta_clause = $meta_clauses[$orderby_raw];
					$orderby     = "CAST({$meta_clause['alias']}.meta_value AS {$meta_clause['cast']})";
				}
				break;
		}

		return $orderby;
	}

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 *
	 * @since 4.6.0
	 *
	 * @param string $order The 'order' query variable.
	 * @return string The sanitized 'order' query variable.
	 */
	protected function parse_order($order) {
		if (!is_string($order) || empty($order)) {
			return 'DESC';
		}

		if ('ASC' === strtoupper($order)) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}

	/**
	 * Used internally to generate a SQL string related to the 'search' parameter.
	 *
	 * @since 4.6.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $string
	 * @return string
	 */
	protected function get_search_sql($string) {
		global $wpdb;

		$like = '%' . $wpdb->esc_like($string) . '%';

		$this->sql_clauses['where'] .= $wpdb->prepare('AND ((name LIKE %s) OR (slug LIKE %s))', $like, $like);
	}

	/**
	 * Creates an array of term objects from an array of term IDs.
	 *
	 * Also discards invalid term objects.
	 *
	 * @since 4.9.8
	 *
	 * @param array $term_ids Term IDs.
	 * @return array
	 */
	protected function populate_terms($term_ids) {
		$terms = [];

		if (!is_array($term_ids)) {
			return $terms;
		}

		foreach ($term_ids as $key => $term_id) {
			$term = get_term($term_id);
			if ($term instanceof WP_Term) {
				$terms[$key] = $term;
			}
		}

		return $terms;
	}

	protected function set_query_cache($terms) {
		$cache_key = $this->build_cache_key();
		wp_cache_set($cache_key, $terms, 'terms', DAY_IN_SECONDS);
	}

	protected function get_query_cache() {
		$cache_key = $this->build_cache_key();
		return wp_cache_get($cache_key, 'terms');
	}

	protected function build_cache_key(): string{
		// $args can be anything. Only use the args defined in defaults to compute the key.
		// $key = md5(serialize(wp_array_slice_assoc($args, array_keys($this->query_var_defaults))) . serialize($taxonomies) . $this->request);

		ksort($this->sql_clauses);
		$key          = md5(serialize($this->sql_clauses));
		$last_changed = wp_cache_get_last_changed('terms');
		return "get_terms:$key:$last_changed";
	}
}
