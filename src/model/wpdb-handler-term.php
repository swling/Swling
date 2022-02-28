<?php
namespace Model;

use Exception;

/**
 * @see Wwpdb->terms
 *
 */
class WPDB_Handler_Term extends WPDB_Handler_Abstract {

	protected $table_name          = 'terms';
	protected $object_name         = 'term';
	protected $primary_id_column   = 'term_id';
	protected $required_columns    = ['name', 'slug', 'taxonomy'];
	protected $object_cache_fields = ['term_id'];

	protected function check_insert_data(array $data) {
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
	}

	protected function check_update_data(array $data) {
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
	}

	/**
	 * Term common check
	 */
	private function common_check(array $data) {
		$taxonomy = $data['taxonomy'];
		$parent   = (int) $data['parent'];

		if (!taxonomy_exists($taxonomy)) {
			throw new Exception(__('Invalid taxonomy.'));
		}

		if ($parent > 0 and !$this->get($parent)) {
			throw new Exception(__('Parent term does not exist.'));
		}
	}
}
