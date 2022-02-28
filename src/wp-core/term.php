<?php

/**
 * Add a new term to the database.
 *
 * A non-existent term is inserted in the following sequence:
 * 1. The term is added to the term table, then related to the taxonomy.
 * 2. If everything is correct, several actions are fired.
 * 3. The 'term_id_filter' is evaluated.
 * 4. The term cache is cleaned.
 * 5. Several more actions are fired.
 * 6. An array is returned containing the `term_id` and `term_taxonomy_id`.
 *
 * If the 'slug' argument is not empty, then it is checked to see if the term
 * is invalid. If it is not a valid, existing term, it is added and the term_id
 * is given.
 *
 * If the taxonomy is hierarchical, and the 'parent' argument is not empty,
 * the term is inserted and the term_id will be given.
 *
 * Error handling:
 * If `$taxonomy` does not exist or `$term` is empty,
 * a WP_Error object will be returned.
 *
 * If the term already exists on the same hierarchical level,
 * or the term slug and name are not unique, a WP_Error object will be returned.
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @since 2.3.0
 *
 * @param string       $term     The term name to add.
 * @param string       $taxonomy The taxonomy to which to add the term.
 * @param array|string $args {
 *     Optional. Array or query string of arguments for inserting a term.
 *
 *     @type string $alias_of    Slug of the term to make this term an alias of.
 *                               Default empty string. Accepts a term slug.
 *     @type string $description The term description. Default empty string.
 *     @type int    $parent      The id of the parent term. Default 0.
 *     @type string $slug        The term slug to use. Default empty string.
 * }
 * @return array|WP_Error {
 *     An array of the new term data, WP_Error otherwise.
 *
 *     @type int        $term_id          The new term ID.
 *     @type int|string $term_taxonomy_id The new term taxonomy ID. Can be a numeric string.
 * }
 */
function wp_insert_term(string $term, string $taxonomy, array $args = []) {
	$defaults = [
		'description' => '',
		'parent'      => 0,
		'slug'        => '',
	];
	$args = wp_parse_args($args, $defaults);

	$args['name']        = $term;
	$args['taxonomy']    = $taxonomy;
	$args['description'] = (string) $args['description'];
	unset($args['count']);

	try {
		$handler = new Model\WPDB_Handler_Term;
		return $handler->insert($args);
	} catch (Exception $e) {
		return new WP_Error('term_insert_error', $e->getMessage());
	}
}

/**
 * Update term based on arguments provided.
 *
 * The `$args` will indiscriminately override all values with the same field name.
 * Care must be taken to not override important information need to update or
 * update will fail (or perhaps create a new term, neither would be acceptable).
 *
 * Defaults will set 'alias_of', 'description', 'parent', and 'slug' if not
 * defined in `$args` already.
 *
 * 'alias_of' will create a term group, if it doesn't already exist, and
 * update it for the `$term`.
 *
 * If the 'slug' argument in `$args` is missing, then the 'name' will be used.
 * If you set 'slug' and it isn't unique, then a WP_Error is returned.
 * If you don't pass any slug, then a unique one will be created.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int          $term_id  The ID of the term.
 * @param string       $taxonomy The taxonomy of the term.
 * @param array|string $args {
 *     Optional. Array or string of arguments for updating a term.
 *
 *     @type string $alias_of    Slug of the term to make this term an alias of.
 *                               Default empty string. Accepts a term slug.
 *     @type string $description The term description. Default empty string.
 *     @type int    $parent      The id of the parent term. Default 0.
 *     @type string $slug        The term slug to use. Default empty string.
 * }
 * @return array|WP_Error An array containing the `term_id` and `term_taxonomy_id`,
 *                        WP_Error otherwise.
 */
function wp_update_term(int $term_id, string $taxonomy, array $args = []) {
	$term             = (array) get_term($term_id);
	$args['term_id']  = $term_id;
	$args['taxonomy'] = $taxonomy;
	$args             = array_merge($term, $args);
	unset($args['count'], $args['filter']);

	try {
		$handler = new Model\WPDB_Handler_Term;
		return $handler->update($args);
	} catch (Exception $e) {
		return new WP_Error('term_update_error', $e->getMessage());
	}
}

/**
 * Removes a term from the database.
 *
 * If the term is a parent of other terms, then the children will be updated to
 * that term's parent.
 *
 * Metadata associated with the term will be deleted.
 *
 * @param int          $term     Term ID.
 * @return bool|int|WP_Error True on success, false if term does not exist. Zero on attempted
 *                           deletion of default Category. WP_Error if the taxonomy does not exist.
 */
function wp_delete_term(int $term_id) {
	$handler = new Model\WPDB_Handler_Term;
	return $handler->delete($term_id);
}

/**
 * Get all Term data from database by Term ID.
 *
 * @param int $term  term data will be fetched from the database
 *
 * @return WP_Term|false WP_Term instance on success, false for miscellaneous failure.
 */
function get_term(int $term_id) {
	return WP_Term::get_instance($term_id);
}

/**
 * Get all Term data from database by Term field and data.
 *
 * Warning: $value is not escaped for 'name' $field. You must do it yourself, if
 * required.
 *
 * If $value does not exist, the return value will be false. If $taxonomy exists
 * and $field and $value combinations exist, the Term will be returned.
 *
 * This function will always return the first term that matches the `$field`-
 * `$value`-`$taxonomy` combination specified in the parameters. If your query
 * is likely to match more than one term (as is likely to be the case when
 * `$field` is 'name', for example), consider using get_terms() instead; that
 * way, you will get all matching terms, and can provide your own logic for
 * deciding which one was intended.
 *
 * @param string     $field    Either 'slug', 'name'
 * @param string|int $value    Search for this term value.
 * @param string     $taxonomy Taxonomy name
 *
 * @return WP_Term|array|false WP_Term instance (or array) on success, depending on the `$output` value.
 *                             False if `$taxonomy` does not exist or `$term` was not found.
 */
function get_term_by(string $field, string $value, string $taxonomy) {
	// No need to perform a query for empty 'slug' or 'name'.
	if ('slug' === $field || 'name' === $field) {
		$value = (string) $value;

		if (0 === strlen($value)) {
			return false;
		}
	}

	$args = [
		'number'  => 1,
		'orderby' => 'none',
	];
	if ($taxonomy) {
		$args['taxonomy'] = $taxonomy;
	}

	switch ($field) {
		case 'slug':
			$args['slug'] = $value;
			break;
		case 'name':
			$args['name'] = $value;
			break;
		default:
			return false;
	}

	$terms = get_terms($args);
	if (is_wp_error($terms) || empty($terms)) {
		return false;
	}

	$term = array_shift($terms);

	return new WP_Term($term);
}

/**
 * Determines whether a taxonomy term exists.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 3.0.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int|string $term     The term to check. Accepts slug or name.
 * @param string     $taxonomy Optional. The taxonomy name to use.
 * @param int        $parent   Optional. ID of parent term under which to confine the exists search.
 * @return mixed Returns null if the term does not exist.
 *               Returns the term ID if no taxonomy is specified and the term ID exists.
 *               Returns an array of the term ID and the term taxonomy ID if the taxonomy is specified and the pairing exists.
 *               Returns 0 if term ID 0 is passed to the function.
 */
function term_exists(string $term, string $taxonomy = '', int $parent = 0): bool {
	if (is_numeric($parent)) {
		$parent = (int) $parent;
	}

	$args = ['parent' => $parent, 'number' => 1];
	if ($taxonomy) {
		$args['taxonomy'] = $taxonomy;
	}

	// Term Query slug
	$args['slug'] = sanitize_title($term);
	$query        = new WP_Term_Query($args);
	$exists       = count($query->get_terms());
	if ($exists) {
		return $exists;
	}

	// Term Query name
	unset($args['slug']);
	$args['name'] = trim(wp_unslash($term));
	$query        = new WP_Term_Query($args);
	return count($query->get_terms());
}

/**
 * Retrieves the terms in a given taxonomy or list of taxonomies.
 *
 * @param array $args       Optional. Array of arguments. See WP_Term_Query::__construct()
 *                                 for information on accepted arguments. Default empty array.

 * @return WP_Term[]|int[]|string[]|string|WP_Error Array of terms, a count thereof as a numeric string,
 *                                                  or WP_Error if any of the taxonomies do not exist.
 *                                                  See the function description for more information.
 */
function get_terms(array $args = []) {
	$defaults = [
		'suppress_filter' => false,
	];

	$args = wp_parse_args($args, $defaults);
	if (isset($args['taxonomy']) && null !== $args['taxonomy']) {
		$args['taxonomy'] = (array) $args['taxonomy'];
	}

	if (!empty($args['taxonomy'])) {
		foreach ($args['taxonomy'] as $taxonomy) {
			if (!taxonomy_exists($taxonomy)) {
				return new WP_Error('invalid_taxonomy', __('Invalid taxonomy.'));
			}
		}
	}

	// Don't pass suppress_filter to WP_Term_Query.
	$suppress_filter = $args['suppress_filter'];
	unset($args['suppress_filter']);

	$term_query = new WP_Term_Query($args);
	$terms      = $term_query->query();

	// Count queries are not filtered, for legacy reasons.
	if (!is_array($terms)) {
		return $terms;
	}

	if ($suppress_filter) {
		return $terms;
	}

	/**
	 * Filters the found terms.
	 *
	 * @since 2.3.0
	 * @since 4.6.0 Added the `$term_query` parameter.
	 *
	 * @param array         $terms      Array of found terms.
	 * @param array|null    $taxonomies An array of taxonomies if known.
	 * @param array         $args       An array of get_terms() arguments.
	 * @param WP_Term_Query $term_query The WP_Term_Query object.
	 */
	return apply_filters('get_terms', $terms, $term_query->query_vars['taxonomy'], $term_query->query_vars, $term_query);
}
