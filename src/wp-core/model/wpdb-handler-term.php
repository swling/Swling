<?php
namespace WP_Core\Model;

use Exception;
use WP_Core\Utility\Singleton_Trait;

/**
 * @see wpdb->terms
 *
 */
class WPDB_Handler_Term extends WPDB_Row {

	protected $table_name          = 'terms';
	protected $object_name         = 'term';
	protected $primary_id_column   = 'term_id';
	protected $required_columns    = ['name', 'slug', 'taxonomy'];
	protected $object_cache_fields = ['term_id'];

	use Singleton_Trait;

	protected function check_insert_data(array $data): array{
		// Term common check
		static::common_check($data);

		$taxonomy = $data['taxonomy'];
		$parent   = (int) $data['parent'];

		// All Taxonomy ：check slug
		if (get_term_by('slug', $data['slug'], $taxonomy)) {
			throw new Exception(__('A term with the slug provided already exists in this taxonomy.'));
		}

		// hierarchical Taxonomy ：check siblings name
		if (is_taxonomy_hierarchical($taxonomy)) {
			$siblings = get_terms(
				[
					'taxonomy' => $taxonomy,
					'parent'   => $parent,
					'name'     => $data['name'],
				]
			);

			if ($siblings) {
				throw new Exception(__('A term with the name provided already exists with this parent.'));
			}

			// none hierarchical Taxonomy ：check name
		} elseif (get_term_by('name', $data['name'], $taxonomy)) {
			throw new Exception(__('A term with the name provided already exists.'));
		}

		return $data;
	}

	protected function check_update_data(array $data): array{
		// Term common check
		static::common_check($data);

		$slug     = $data['slug'];
		$taxonomy = $data['taxonomy'];
		$parent   = (int) $data['parent'];
		$term_id  = $data['term_id'];

		// Check for duplicate slug.
		$duplicate = get_term_by('slug', $slug, $taxonomy);
		if ($duplicate and $duplicate->term_id != $term_id) {
			throw new Exception('duplicate_term_slug ' . $slug);
		}

		// hierarchical Taxonomy ：check siblings duplicate name
		if (is_taxonomy_hierarchical($taxonomy)) {
			$siblings = get_terms(
				[
					'taxonomy' => $taxonomy,
					'parent'   => $parent,
					'name'     => $data['name'],
				]
			);

			foreach ($siblings as $term) {
				if ($term->term_id != $term_id) {
					throw new Exception(__('A term with the name provided already exists with this parent.'));
				}
			}

			// none hierarchical Taxonomy ：check duplicate name
		} else {
			$duplicate = get_term_by('name', $data['name'], $taxonomy);
			if ($duplicate and $duplicate->term_id != $term_id) {
				throw new Exception(__('A term with the name provided already exists.'));
			}
		}

		return $data;
	}

	/**
	 * Term common check
	 */
	private function common_check(array $data) {
		$taxonomy = $data['taxonomy'] ?? '';
		$parent   = (int) ($data['parent'] ?? 0);

		if ($taxonomy and !taxonomy_exists($taxonomy)) {
			throw new Exception(__('Invalid taxonomy.'));
		}

		if ($parent > 0 and !$this->get($parent)) {
			throw new Exception(__('Parent term does not exist.'));
		}
	}

	// Update children to point to new parent.
	public function modify_deleted_term_children(object $deleted_term) {
		$wpdb     = &$this->wpdb;
		$term_id  = (int) $deleted_term->term_id;
		$taxonomy = $deleted_term->taxonomy;
		$parent   = $deleted_term->parent;

		// Update children to point to new parent.
		if (!is_taxonomy_hierarchical($taxonomy)) {
			return false;
		}

		$edit_terms = $wpdb->get_results("SELECT * FROM $this->table WHERE `parent` = " . (int) $term_id);
		$wpdb->update($wpdb->terms, compact('parent'), ['parent' => $term_id] + compact('taxonomy'));

		// Delete Cache
		$this->clean_terms_cache($edit_terms);
	}

	/**
	 * Will remove all of the term IDs from the cache.
	 *
	 * @param int|int[] $ids  Single or array of term IDs.
	 */
	private function clean_terms_cache(array $terms) {
		foreach ($terms as $term) {

			if (!is_object($term)) {
				$term = $this->get($term);
			}

			$this->clean_row_cache($term);
		}
	}
}
