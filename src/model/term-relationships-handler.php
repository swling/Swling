<?php
namespace Model;

use Exception;
use Model\WPDB_Handler_Term;

/**
 * @see wpdb->term_relationships
 *
 */
class Term_Relationships_Handler {

	protected $wpdb;
	protected $table_name = 'term_relationships';
	protected $table;

	/**
	 * Constructer
	 *
	 * Init
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		$table_name  = $this->table_name;
		$this->table = $wpdb->$table_name;
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
	 * @param int              $object_id The object to relate to.
	 * @param array $terms     array of either term slugs or IDs.
	 *                                    Will replace all existing related terms in this taxonomy. Passing an
	 *                                    empty value will remove all related terms.
	 * @param string           $taxonomy  The context in which to relate the term to the object.
	 * @param bool             $append    Optional. If false will delete difference of terms. Default false.
	 * @return array|WP_Error Term taxonomy IDs of the affected terms or WP_Error on failure.
	 */
	public function set_object_terms(int $object_id, array $terms, string $taxonomy, bool $append = false) {
		$wpdb = &$this->wpdb;
		if (!taxonomy_exists($taxonomy)) {
			throw new Exception(__('Invalid taxonomy.'));
		}

		if (!$append) {
			$old_t_ids = $this->get_object_terms($object_id, $taxonomy, ['fields' => 'ids']);
		} else {
			$old_t_ids = [];
		}

		$t_ids      = [];
		$new_tt_ids = [];
		foreach ((array) $terms as $term) {
			if ('' === trim($term)) {
				continue;
			}

			$term_info = static::get_term_by_id_or_slug($term, $taxonomy);
			if (!$term_info) {
				// Skip if a non-existent term ID is passed.
				if (is_int($term)) {
					continue;
				}

				$new_term_id = wp_insert_term($term, $taxonomy);
			}

			if (is_wp_error($term_info)) {
				return $term_info;
			}

			$term_id        = $new_term_id ?? $term_info->term_id;
			$t_ids[]        = $term_id;
			$object_in_term = $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM $this->table WHERE object_id = %d AND term_taxonomy_id = %d", $object_id, $term_id));
			if ($object_in_term) {
				continue;
			}

			$wpdb->insert(
				$this->table,
				[
					'object_id'        => $object_id,
					'term_taxonomy_id' => $term_id,
				]
			);

			$new_tt_ids[] = $term_id;
		}

		if ($new_tt_ids) {
			$this->update_term_count($new_tt_ids, $taxonomy);
		}

		if (!$append) {
			$delete_tt_ids = array_diff($old_t_ids, $t_ids);
			if ($delete_tt_ids) {
				$in_delete_tt_ids = "'" . implode("', '", $delete_tt_ids) . "'";
				$delete_term_ids  = $wpdb->get_col($wpdb->prepare("SELECT term_id FROM $wpdb->terms WHERE taxonomy = %s AND term_id IN ($in_delete_tt_ids)", $taxonomy));
				$delete_term_ids  = array_map('intval', $delete_term_ids);

				$this->remove_object_terms($object_id, $delete_term_ids, $taxonomy);
			}
		}

		// cache
		$this->clean_object_term_cache($object_id, $taxonomy);

		/**
		 * Fires after an object's terms have been set.
		 *
		 * @param int    $object_id  Object ID.
		 * @param array  $terms      An array of object term IDs or slugs.
		 * @param array  $tt_ids     An array of term taxonomy IDs.
		 * @param string $taxonomy   Taxonomy slug.
		 * @param bool   $append     Whether to append new terms to the old terms.
		 * @param array  $old_t_ids Old array of term taxonomy IDs.
		 */
		do_action('set_object_terms', $object_id, $terms, $t_ids, $taxonomy, $append, $old_t_ids);

		return $t_ids;
	}

	public function get_object_terms(int $object_id, string $taxonomy, array $args = []) {
		$single_cache = empty($args);

		if ($single_cache) {
			$terms = $this->get_object_term_cache($object_id, $taxonomy);
			if (false !== $terms) {
				return $terms;
			}
		}

		$args['taxonomy']   = $taxonomy;
		$args['object_ids'] = [$object_id];
		$terms              = get_terms($args);

		if ($single_cache and !is_wp_error($terms)) {
			$this->set_object_term_cache($object_id, $taxonomy, $terms);
		}

		return apply_filters('get_object_terms', $terms, $object_id, $taxonomy);
	}

	/**
	 * Remove term(s) associated with a given object.
	 *
	 * @param int              $object_id The ID of the object from which the terms will be removed.
	 * @param string|int|array $terms     The slug(s) or ID(s) of the term(s) to remove.
	 * @param string           $taxonomy  Taxonomy name.
	 * @return bool            True on success, false on failure.
	 */
	public function remove_object_terms(int $object_id, array $terms, string $taxonomy): bool{
		$wpdb = &$this->wpdb;

		if (!taxonomy_exists($taxonomy)) {
			throw new Exception(__('Invalid taxonomy.'));
		}

		$t_ids = [];
		foreach ($terms as $term) {
			if ('' === trim($term)) {
				continue;
			}

			$term_info = static::get_term_by_id_or_slug($term, $taxonomy);
			if (!$term_info) {
				continue;
			} elseif (is_wp_error($term_info)) {
				return $term_info;
			}

			$t_ids[] = $term_info->term_id;
		}

		if (!$t_ids) {
			return false;
		}

		$in_t_ids = "'" . implode("', '", $t_ids) . "'";

		/**
		 * Fires immediately before an object-term relationship is deleted.
		 *
		 * @param int   $object_id Object ID.
		 * @param array $tt_ids    An array of term taxonomy IDs.
		 * @param string $taxonomy  Taxonomy slug.
		 */
		do_action('delete_term_relationships', $object_id, $t_ids, $taxonomy);

		$deleted = $wpdb->query($wpdb->prepare("DELETE FROM $this->table WHERE object_id = %d AND term_taxonomy_id IN ($in_t_ids)", $object_id));
		if (!$deleted) {
			return false;
		}

		// Cache
		$this->clean_object_term_cache($object_id, $taxonomy);

		/**
		 * Fires immediately after an object-term relationship is deleted.
		 *
		 * @param int    $object_id Object ID.
		 * @param array  $tt_ids    An array of term taxonomy IDs.
		 * @param string $taxonomy  Taxonomy slug.
		 */
		do_action('deleted_term_relationships', $object_id, $t_ids, $taxonomy);

		$this->update_term_count($t_ids, $taxonomy);

		return true;
	}

	/**
	 * Removes a term object relationships from the database.
	 *
	 * @param int          $term     Term ID.
	 *
	 * @return bool True on success
	 */
	public function delete_term_object_relationships(object $term) {
		$term_id    = (int) $term->term_id;
		$taxonomy   = $term->taxonomy;
		$object_ids = (array) $this->wpdb->get_col($this->wpdb->prepare("SELECT object_id FROM $this->table WHERE term_taxonomy_id = %d", $term_id));
		foreach ($object_ids as $object_id) {
			$this->remove_object_terms($object_id, [$term_id], $taxonomy);
		}

		return true;
	}

	/**
	 * Determine if the given object is associated with any of the given terms.
	 *
	 * The given terms are checked against the object's terms' term_ids, names and slugs.
	 * Terms given as integers will only be checked against the object's terms' term_ids.
	 * If no terms are given, determines if object is associated with any terms in the given taxonomy.
	 *
	 * @param int                       $object_id ID of the object (post ID, link ID, ...).
	 * @param string                    $taxonomy  Single taxonomy name.
	 * @param int|string|int[]|string[] $terms     Optional. Term ID, name, slug, or array of such
	 *                                             to check against. Default null.
	 * @return bool|WP_Error WP_Error on input error.
	 */
	public function is_object_in_term(int $object_id, string $taxonomy, $terms = null) {
		if (!$object_id) {
			throw new Exception(__('Invalid object ID.'));
		}

		$object_terms = $this->get_object_terms($object_id, $taxonomy);

		if (empty($object_terms)) {
			return false;
		}

		if (empty($terms)) {
			return (!empty($object_terms));
		}

		$terms = (array) $terms;
		$ints  = array_filter($terms, 'is_int');
		if ($ints) {
			$strs = array_diff($terms, $ints);
		} else {
			$strs = &$terms;
		}

		foreach ($object_terms as $object_term) {
			$term_id = (int) $object_term->term_id;
			// If term is an int, check against term_ids only.
			if ($ints && in_array($term_id, $ints, true)) {
				return true;
			}

			if ($strs) {
				// Only check numeric strings against term_id, to avoid false matches due to type juggling.
				$numeric_strs = array_map('intval', array_filter($strs, 'is_numeric'));
				if (in_array($term_id, $numeric_strs, true)) {
					return true;
				}

				if (in_array($object_term->name, $strs, true)) {
					return true;
				}
				if (in_array($object_term->slug, $strs, true)) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Will update term count based on object types of the current taxonomy.
	 *
	 * @param int[]       $terms    List of Term taxonomy IDs.
	 * @param WP_Taxonomy $taxonomy Current taxonomy object of terms.
	 */
	private function update_term_count(array $terms, string $taxonomy) {
		$wpdb = &$this->wpdb;

		$taxonomy     = get_taxonomy($taxonomy);
		$object_types = (array) $taxonomy->object_type;

		foreach ($object_types as &$object_type) {
			list($object_type) = explode(':', $object_type);
		}

		$object_types = array_unique($object_types);
		if ($object_types) {
			$object_types = esc_sql(array_filter($object_types, 'post_type_exists'));
		}

		$post_statuses = ['publish'];

		/**
		 * Filters the post statuses for updating the term count.
		 *
		 * @param string[]    $post_statuses List of post statuses to include in the count. Default is 'publish'.
		 * @param WP_Taxonomy $taxonomy      Current taxonomy object.
		 */
		$post_statuses = esc_sql(apply_filters('update_post_term_count_statuses', $post_statuses, $taxonomy));

		foreach ($terms as $term) {
			$count = 0;
			if ($object_types) {
				// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.QuotedDynamicPlaceholderGeneration
				$count += (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $this->table, $wpdb->posts WHERE $wpdb->posts.ID = $this->table.object_id AND post_status IN ('" . implode("', '", $post_statuses) . "') AND post_type IN ('" . implode("', '", $object_types) . "') AND term_taxonomy_id = %d", $term));
			}

			$handler = new WPDB_Handler_Term;
			$handler->update(['term_id' => $term, 'count' => $count]);
		}
	}

	private static function get_term_by_id_or_slug($term, $taxonomy) {
		if (is_int($term)) {
			$term_info = get_term($term);
			if (!$term_info) {
				return $term_info;
			}

			if ($taxonomy != $term_info->taxonomy) {
				throw new Exception(__('Term is not in Taxonomy.'));
			}
		} else {
			$term_info = get_term_by('slug', $term, $taxonomy);
		}

		return $term_info;
	}

	private function set_object_term_cache(int $object_id, string $taxonomy, array $terms) {
		wp_cache_set($object_id, $terms, "{$taxonomy}_relationships");
	}

	private function clean_object_term_cache(int $object_id, string $taxonomy) {
		wp_cache_delete($object_id, "{$taxonomy}_relationships");
		wp_cache_delete_last_changed('terms');
		wp_cache_delete_last_changed('term_relationships');
	}

	/**
	 * Retrieves the cached term objects for the given object ID.
	 *
	 * Upstream functions (like get_the_terms() and is_object_in_term()) are
	 * responsible for populating the object-term relationship cache. The current
	 * function only fetches relationship data that is already in the cache.
	 *
	 * @param int    $id       Term object ID, for example a post, comment, or user ID.
	 * @param string $taxonomy Taxonomy name.
	 * @return bool|WP_Term[]|WP_Error Array of `WP_Term` objects, if cached.
	 *                                 False if cache is empty for `$taxonomy` and `$id`.
	 *                                 WP_Error if get_term() returns an error object for any term.
	 */
	private function get_object_term_cache(int $object_id, string $taxonomy) {
		$_term_ids = wp_cache_get($object_id, "{$taxonomy}_relationships");

		// We leave the priming of relationship caches to upstream functions.
		if (false === $_term_ids) {
			return false;
		}

		// Backward compatibility for if a plugin is putting objects into the cache, rather than IDs.
		$term_ids = [];
		foreach ($_term_ids as $term_id) {
			if (is_numeric($term_id)) {
				$term_ids[] = (int) $term_id;
			} elseif (isset($term_id->term_id)) {
				$term_ids[] = (int) $term_id->term_id;
			}
		}

		// Fill the term objects.
		// _prime_term_caches($term_ids);

		$terms = [];
		foreach ($term_ids as $term_id) {
			$term = get_term($term_id, $taxonomy);
			if (is_wp_error($term)) {
				return $term;
			}

			$terms[] = $term;
		}

		return $terms;
	}
}
