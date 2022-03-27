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
class WP_Term_Query extends WP_Query_Abstract {

	protected $table_name        = 'terms';
	protected $primary_id_column = 'term_id';
	protected $meta_type         = 'term';
	protected $int_column        = ['parent', 'count', 'term_id', 'term_group'];
	protected $str_column        = ['taxonomy', 'slug', 'name'];
	protected $search_column     = ['name', 'slug'];
	protected $default_order_by  = ['name'];

	/**
	 * Default values for query vars.
	 *
	 * @since 4.6.0
	 * @var array
	 */
	public $query_var_defaults;

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
	public function parse_query(array $query) {
		$this->query_var_defaults = [
			'taxonomy'               => '',
			'object_ids'             => [],
			'orderby'                => 'name',
			'order'                  => 'ASC',
			'hide_empty'             => true,
			'include'                => [],
			'exclude'                => [],
			'exclude_tree'           => [],
			'number'                 => '',
			'offset'                 => '',
			'fields'                 => 'all',
			'count'                  => '',
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
			'no_found_rows'          => false,
		];

		$query      = wp_parse_args($query, $this->query_var_defaults);
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
	 * parse term relationships query
	 *
	 */
	protected function parse_relationship_query() {
		global $wpdb;
		$object_ids = (array) $this->query_vars['object_ids'];
		if (empty($object_ids)) {
			return;
		}

		$this->join .= " INNER JOIN {$wpdb->term_relationships} AS tr ON (tr.term_taxonomy_id = {$wpdb->terms}.term_id)";
		$ids = implode(', ', array_map('intval', $object_ids));
		$this->where .= " AND tr.object_id IN ($ids)";
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
}
