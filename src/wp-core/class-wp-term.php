<?php

/**
 * Taxonomy API: WP_Term class
 *
 * @package WordPress
 * @subpackage Taxonomy
 * @since 4.4.0
 */

/**
 * Core class used to implement the WP_Term object.
 *
 * @since 4.4.0
 *
 * @property-read object $data Sanitized term data.
 */
final class WP_Term extends WP_Object {

	/**
	 * Term ID.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public $term_id;

	/**
	 * The term's name.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $name = '';

	/**
	 * The term's slug.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $slug = '';

	/**
	 * The term's term_group.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public $term_group = '';

	/**
	 * The term's taxonomy name.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $taxonomy = '';

	/**
	 * The term's description.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $description = '';

	/**
	 * ID of a term's parent term.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public $parent = 0;

	/**
	 * Cached object count for this term.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public $count = 0;

	/**
	 * Stores the term object's sanitization level.
	 *
	 * Does not correspond to a database field.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public $filter = 'raw';

	/**
	 * Retrieve WP_Term instance.
	 *
	 * @param int $term  term data will be fetched from the database
	 *
	 * @return WP_Term|false WP_Term instance on success, false for miscellaneous failure.
	 */
	public static function get_instance(int $term_id) {
		$term_obj = parent::get_instance($term_id);
		$term_obj->filter($term_obj->filter);

		return $term_obj;
	}

	/**
	 * get wpdb handler instance
	 *
	 */
	protected static function get_wpdb_handler(): object {
		return WP_Core\Model\WPDB_Handler_Term::get_instance();
	}

	/**
	 * Sanitizes term fields, according to the filter type provided.
	 *
	 * @since 4.4.0
	 *
	 * @param string $filter Filter context. Accepts 'edit', 'db', 'display', 'attribute', 'js', 'rss', or 'raw'.
	 */
	public function filter($filter) {
		static::sanitize_term($this, $filter);
	}

	/**
	 * Getter.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key Property to get.
	 * @return mixed Property value.
	 */
	public function __get($key) {
		switch ($key) {
			case 'data':
				$data    = new stdClass();
				$columns = ['term_id', 'name', 'slug', 'term_group', 'taxonomy', 'description', 'parent', 'count'];
				foreach ($columns as $column) {
					$data->{$column} = isset($this->{$column}) ? $this->{$column} : null;
				}

				return static::sanitize_term($data, 'raw');
		}
	}

	/**
	 * Sanitize all term fields.
	 *
	 * Relies on sanitize_term_field() to sanitize the term. The difference is that
	 * this function will sanitize **all** fields. The context is based
	 * on sanitize_term_field().
	 *
	 * The `$term` is expected to be either an array or an object.
	 *
	 * @since 2.3.0
	 *
	 * @param array|object $term     The term to check.
	 * @param string       $context  Optional. Context in which to sanitize the term.
	 *                               Accepts 'raw', 'edit', 'db', 'display', 'rss',
	 *                               'attribute', or 'js'. Default 'display'.
	 * @return array|object Term with all fields sanitized.
	 */
	public static function sanitize_term($term, $context = 'display') {
		$fields = ['term_id', 'name', 'description', 'slug', 'count', 'parent', 'term_group', 'object_id'];

		$do_object = is_object($term);

		foreach ((array) $fields as $field) {
			if ($do_object) {
				if (isset($term->$field)) {
					$term->$field = static::sanitize_term_field($field, $term->$field, $context);
				}
			} else {
				if (isset($term[$field])) {
					$term[$field] = static::sanitize_term_field($field, $term[$field], $context);
				}
			}
		}

		if ($do_object) {
			$term->filter = $context;
		} else {
			$term['filter'] = $context;
		}

		return $term;
	}

	/**
	 * Cleanse the field value in the term based on the context.
	 *
	 * Passing a term field value through the function should be assumed to have
	 * cleansed the value for whatever context the term field is going to be used.
	 *
	 * If no context or an unsupported context is given, then default filters will
	 * be applied.
	 *
	 * There are enough filters for each context to support a custom filtering
	 * without creating your own filter function. Simply create a function that
	 * hooks into the filter you need.
	 *
	 * @since 2.3.0
	 *
	 * @param string $field    Term field to sanitize.
	 * @param string $value    Search for this term value.
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy name.
	 * @param string $context  Context in which to sanitize the term field.
	 *                         Accepts 'raw', 'edit', 'db', 'display', 'rss',
	 *                         'attribute', or 'js'. Default 'display'.
	 * @return mixed Sanitized field.
	 */
	public static function sanitize_term_field($field, $value, $context) {
		$int_fields = ['parent', 'term_id', 'count', 'term_group', 'object_id'];
		if (in_array($field, $int_fields, true)) {
			$value = (int) $value;
			if ($value < 0) {
				$value = 0;
			}
		}

		$context = strtolower($context);

		if ('raw' === $context) {
			return $value;
		}

		if ('edit' === $context) {
			if ('description' === $field) {
				$value = esc_html($value); // textarea_escaped
			} else {
				$value = esc_attr($value);
			}
		}

		if ('attribute' === $context) {
			$value = esc_attr($value);
		} elseif ('js' === $context) {
			$value = esc_js($value);
		}

		// Restore the type for integer fields after esc_attr().
		if (in_array($field, $int_fields, true)) {
			$value = (int) $value;
		}

		return $value;
	}
}
