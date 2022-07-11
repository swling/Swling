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
 * @return int|WP_Error          The new term ID., WP_Error otherwise.
 */
function wp_insert_term(string $term, string $taxonomy, array $args = []) {
	$defaults = [
		'description' => '',
		'parent'      => 0,
		'slug'        => $term,
	];
	$args = wp_parse_args($args, $defaults);

	$args['name']        = $term;
	$args['taxonomy']    = $taxonomy;
	$args['description'] = (string) $args['description'];
	unset($args['count']);

	try {
		$handler = WP_Core\Model\WPDB_Handler_Term::get_instance();
		return $handler->insert($args);
	} catch (Exception $e) {
		return new WP_Error(__FUNCTION__, $e->getMessage());
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
		$handler = WP_Core\Model\WPDB_Handler_Term::get_instance();
		return $handler->update($args);
	} catch (Exception $e) {
		return new WP_Error(__FUNCTION__, $e->getMessage());
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
	$handler = WP_Core\Model\WPDB_Handler_Term::get_instance();
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
	$exists       = count($query->get_results());
	if ($exists) {
		return $exists;
	}

	// Term Query name
	unset($args['slug']);
	$args['name'] = trim(wp_unslash($term));
	$query        = new WP_Term_Query($args);
	return count($query->get_results());
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
				return new WP_Error(__FUNCTION__, __('Invalid taxonomy.'));
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

/**
 * Create Term and Taxonomy Relationships.
 *
 * Relates an object (post, link etc) to a term and taxonomy type. Creates the
 * term and taxonomy relationship if it doesn't already exist. Creates a term if
 * it doesn't exist (using the slug).
 *
 * A relationship means that the term is grouped in or belongs to the taxonomy.
 * A term has no meaning until it is given context by defining which taxonomy it
 * exists under.
 *
 * @since 2.3.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int              $object_id The object to relate to.
 * @param array $terms     array of either term slugs or IDs.
 *                                    Will replace all existing related terms in this taxonomy. Passing an
 *                                    empty value will remove all related terms.
 * @param string           $taxonomy  The context in which to relate the term to the object.
 * @param bool             $append    Optional. If false will delete difference of terms. Default false.
 * @return array|WP_Error Term taxonomy IDs of the affected terms or WP_Error on failure.
 */
function wp_set_object_terms(int $object_id, array $terms, string $taxonomy, bool $append = false) {
	try {
		$handler = WP_Core\Model\Term_Relationships_Handler::get_instance();
		return $handler->set_object_terms($object_id, $terms, $taxonomy, $append);
	} catch (Exception $e) {
		return new WP_Error(__FUNCTION__, $e->getMessage());
	}
}

/**
 * Retrieves the terms associated with the given object(s), in the supplied taxonomies.
 *
 * @param int|int[]       $object_ids The ID(s) of the object(s) to retrieve.
 * @param string|string[] $taxonomies The taxonomy names to retrieve terms from.
 * @param array|string    $args       See WP_Term_Query::__construct() for supported arguments.
 * @return WP_Term[]|WP_Error Array of terms or empty array if no terms found.
 *                            WP_Error if any of the taxonomies don't exist.
 */
function wp_get_object_terms(int $object_id, string $taxonomy, array $args = []) {
	try {
		$handler = WP_Core\Model\Term_Relationships_Handler::get_instance();
		return $handler->get_object_terms($object_id, $taxonomy, $args);
	} catch (Exception $e) {
		return new WP_Error(__FUNCTION__, $e->getMessage());
	}
}

/**
 * Retrieves the terms of the taxonomy that are attached to the post.
 *
 * @since 2.5.0
 *
 * @param int         $post_id     Post ID.
 * @param string      $taxonomy Taxonomy name.
 * @return WP_Term[]|false|WP_Error Array of WP_Term objects on success, false if there are no terms
 *                                  or the post does not exist, WP_Error on failure.
 */
function get_the_terms(int $post_id, string $taxonomy) {
	$post = get_post($post_id);
	if (!$post) {
		return false;
	}

	$terms = wp_get_object_terms($post->ID, $taxonomy);
	return $terms;
}

/**
 * Add term(s) associated with a given object.
 *
 * @since 3.6.0
 *
 * @param int              $object_id The ID of the object to which the terms will be added.
 * @param string|int|array $terms     The slug(s) or ID(s) of the term(s) to add.
 * @param array|string     $taxonomy  Taxonomy name.
 * @return array|WP_Error Term taxonomy IDs of the affected terms.
 */
function wp_add_object_terms(int $object_id, array $terms, string $taxonomy) {
	return wp_set_object_terms($object_id, $terms, $taxonomy, true);
}

/**
 * Remove term(s) associated with a given object.
 *
 * @since 3.6.0
 *
 * @global wpdb $wpdb WordPress database abstraction object.
 *
 * @param int              $object_id The ID of the object from which the terms will be removed.
 * @param string|int|array $terms     The slug(s) or ID(s) of the term(s) to remove.
 * @param string           $taxonomy  Taxonomy name.
 * @return bool|WP_Error True on success, false or WP_Error on failure.
 */
function wp_remove_object_terms(int $object_id, array $terms, string $taxonomy) {
	try {
		$handler = WP_Core\Model\Term_Relationships_Handler::get_instance();
		return $handler->remove_object_terms($object_id, $terms, $taxonomy);
	} catch (Exception $e) {
		return new WP_Error(__FUNCTION__, $e->getMessage());
	}
}

/**
 * Determine if the given object is associated with any of the given terms.
 *
 * The given terms are checked against the object's terms' term_ids, names and slugs.
 * Terms given as integers will only be checked against the object's terms' term_ids.
 * If no terms are given, determines if object is associated with any terms in the given taxonomy.
 *
 * @since 2.7.0
 *
 * @param int                       $object_id ID of the object (post ID, link ID, ...).
 * @param string                    $taxonomy  Single taxonomy name.
 * @param int|string|int[]|string[] $terms     Optional. Term ID, name, slug, or array of such
 *                                             to check against. Default null.
 * @return bool|WP_Error WP_Error on input error.
 */
function is_object_in_term(int $object_id, string $taxonomy, $terms = null) {
	try {
		$handler = WP_Core\Model\Term_Relationships_Handler::get_instance();
		return $handler->is_object_in_term($object_id, $taxonomy, $terms);
	} catch (Exception $e) {
		return new WP_Error(__FUNCTION__, $e->getMessage());
	}
}

/**
 * Will unlink the object from the taxonomy or taxonomies.
 *
 * Will remove all relationships between the object and any terms in
 * a particular taxonomy or taxonomies. Does not remove the term or
 * taxonomy itself.
 *
 * @param int          $object_id  The term object ID that refers to the term.
 * @param string|array $taxonomies List of taxonomy names or single taxonomy name.
 */
function wp_delete_object_term_relationships(object $object, array $taxonomies = []) {
	$object_id  = (int) $object->ID;
	$taxonomies = $taxonomies ?: get_object_taxonomies($object->post_type);

	foreach ((array) $taxonomies as $taxonomy) {
		$term_ids = wp_get_object_terms($object_id, $taxonomy, ['fields' => 'ids']);
		$term_ids = array_map('intval', $term_ids);
		wp_remove_object_terms($object_id, $term_ids, $taxonomy);
	}
}

function wp_delete_term_object_relationships(object $term) {
	try {
		$handler = WP_Core\Model\Term_Relationships_Handler::get_instance();
		return $handler->delete_term_object_relationships($term);
	} catch (Exception $e) {
		return new WP_Error(__FUNCTION__, $e->getMessage());
	}
}

// Update children to point to new parent.
function wp_modify_deleted_term_children(object $deleted_term) {
	try {
		$handler = WP_Core\Model\WPDB_Handler_Term::get_instance();
		return $handler->modify_deleted_term_children($deleted_term);
	} catch (Exception $e) {
		return new WP_Error(__FUNCTION__, $e->getMessage());
	}
}
