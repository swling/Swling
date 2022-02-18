<?php
class WP_Query {

	/**
	 * Query vars set by the user.
	 *
	 * @since 1.5.0
	 * @var array
	 */
	public $query;

	/**
	 * Query vars, after parsing.
	 *
	 * @since 1.5.0
	 * @var array
	 */
	public $query_vars = [];

	/**
	 * Array of post objects or post IDs.
	 *
	 * @since 1.5.0
	 * @var WP_Post[]|int[]
	 */
	public $posts;

	private static $default_query = [
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'tax_query'      => [],
		'fields'         => '',
		'posts_per_page' => 10,
		'no_found_rows'  => false,
		'orderby'        => '',
		'order'          => 'DESC',
	];

	public function __construct(array $query) {
		$this->init_query_flags();
		$this->parse_query($query);
	}

	/**
	 * Resets query flags to false.
	 *
	 * The query flags are what page info WordPress was able to figure out.
	 *
	 * @since 2.0.0
	 */
	private function init_query_flags() {
		$this->is_single            = false;
		$this->is_preview           = false;
		$this->is_page              = false;
		$this->is_archive           = false;
		$this->is_date              = false;
		$this->is_year              = false;
		$this->is_month             = false;
		$this->is_day               = false;
		$this->is_time              = false;
		$this->is_author            = false;
		$this->is_category          = false;
		$this->is_tag               = false;
		$this->is_tax               = false;
		$this->is_search            = false;
		$this->is_feed              = false;
		$this->is_comment_feed      = false;
		$this->is_home              = false;
		$this->is_404               = false;
		$this->is_paged             = false;
		$this->is_admin             = false;
		$this->is_attachment        = false;
		$this->is_singular          = false;
		$this->is_posts_page        = false;
		$this->is_post_type_archive = false;
	}

	public function parse_query($query) {
		$this->query      = $query;
		$this->query_vars = array_merge(static::$default_query, $query);
		$qv               = $this->query_vars;

		if (isset($qv['cat'])) {
			$this->is_archive = true;
		}

		if ($qv['tax_query']) {
			$this->is_archive = true;
			$this->is_tax     = true;
		}
	}

	public function get_posts() {
		global $wpdb;

		// First let's clear some variables.
		$distinct         = '';
		$whichauthor      = '';
		$whichmimetype    = '';
		$where            = '';
		$limits           = '';
		$join             = '';
		$search           = '';
		$groupby          = '';
		$post_status_join = false;
		$page             = 1;
		$orderby          = '';

		$q = &$this->query_vars;
		switch ($q['fields']) {
			case 'ids':
				$fields = "{$wpdb->posts}.ID";
				break;
			case 'id=>parent':
				$fields = "{$wpdb->posts}.ID, {$wpdb->posts}.post_parent";
				break;
			default:
				$fields = "{$wpdb->posts}.*";
		}

		$found_rows = '';
		if (!$q['no_found_rows'] && !empty($limits)) {
			$found_rows = 'SQL_CALC_FOUND_ROWS';
		}

		$where .= $this->get_post_filed_sql();

		// Tax Query
		$this->tax_query = new WP_Tax_Query($q['tax_query']);
		$clauses         = $this->tax_query->get_sql($wpdb->posts, 'ID');
		$join .= $clauses['join'];
		$where .= $clauses['where'];

		// Parse meta query.
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars($q);
		if (!empty($this->meta_query->queries)) {
			$clauses = $this->meta_query->get_sql('post', $wpdb->posts, 'ID', $this);
			$join .= $clauses['join'];
			$where .= $clauses['where'];
		}

		// If 'offset' is provided, it takes precedence over 'paged'.
		if (isset($q['offset']) && is_numeric($q['offset'])) {
			$q['offset'] = absint($q['offset']);
			$pgstrt      = $q['offset'] . ', ';
		} else {
			$pgstrt = absint(($page - 1) * $q['posts_per_page']) . ', ';
		}
		$limits = 'LIMIT ' . $pgstrt . $q['posts_per_page'];

		// Order
		if (empty($q['orderby'])) {
			$orderby = "{$wpdb->posts}.post_date " . $q['order'];
		} elseif (!empty($q['order'])) {
			// $orderby = "{$q['orderby']} {$q['order']}";
		}

		if (!empty($this->tax_query->queries) || !empty($this->meta_query->queries)) {
			$groupby = "{$wpdb->posts}.ID";
		}

		if (!empty($groupby)) {
			$groupby = 'GROUP BY ' . $groupby;
		}
		if (!empty($orderby)) {
			$orderby = 'ORDER BY ' . $orderby;
		}

		$old_request   = "SELECT $found_rows $distinct $fields FROM {$wpdb->posts} $join WHERE 1=1 $where $groupby $orderby $limits";
		$this->request = $old_request;

		// excute sql query
		$this->posts = $wpdb->get_results($this->request);
		return $this->posts;
	}

	private function get_post_filed_sql() {
		global $wpdb;
		$q     = &$this->query_vars;
		$where = '';
		foreach ($q as $post_filed => $value) {
			if (in_array($post_filed, ['post_status', 'post_type'])) {
				$where .= $wpdb->prepare(" AND {$wpdb->posts}.{$post_filed} = %s ", $value);
			} elseif (in_array($post_filed, ['p', 'post_author', 'post_parent'])) {
				$where .= $wpdb->prepare(" AND {$wpdb->posts}.{$post_filed} = %d ", $value);
			}
		}

		return $where;
	}
}
