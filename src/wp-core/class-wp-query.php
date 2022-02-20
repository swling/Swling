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
	 * Taxonomy query, as passed to get_tax_sql().
	 *
	 * @since 3.1.0
	 * @var WP_Tax_Query A taxonomy query instance.
	 */
	public $tax_query;

	/**
	 * Metadata query container.
	 *
	 * @since 3.2.0
	 * @var WP_Meta_Query A meta query instance.
	 */
	public $meta_query = false;

	/**
	 * Date query container.
	 *
	 * @since 3.7.0
	 * @var WP_Date_Query A date query instance.
	 */
	public $date_query = false;

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

	// $wpdb query args
	private $wpdb             = '';
	private $fields           = '';
	private $found_rows       = '';
	private $distinct         = '';
	private $whichauthor      = '';
	private $whichmimetype    = '';
	private $where            = '';
	private $limits           = '';
	private $join             = '';
	private $search           = '';
	private $groupby          = '';
	private $post_status_join = false;
	private $page             = 1;
	private $orderby          = '';

	public function __construct(array $query) {
		global $wpdb;
		$this->wpdb = $wpdb;

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

		/**
		 * Fires after the query variable object is created, but before the actual query is run.
		 *
		 * Note: If using conditional tags, use the method versions within the passed instance
		 * (e.g. $this->is_main_query() instead of is_main_query()). This is because the functions
		 * like is_main_query() test against the global $wp_query instance, not the passed one.
		 *
		 * @since 2.0.0
		 *
		 * @param WP_Query $query The WP_Query instance (passed by reference).
		 */
		do_action_ref_array('pre_get_posts', [ & $this]);

		$wpdb = &$this->wpdb;
		$q    = &$this->query_vars;
		switch ($q['fields']) {
			case 'ids':
				$this->fields = "{$wpdb->posts}.ID";
				break;
			case 'id=>parent':
				$this->fields = "{$wpdb->posts}.ID, {$wpdb->posts}.post_parent";
				break;
			default:
				$this->fields = "{$wpdb->posts}.*";
		}

		if (!$q['no_found_rows'] && !empty($limits)) {
			$this->found_rows = 'SQL_CALC_FOUND_ROWS';
		}

		// Post Field Query
		$this->handle_post_field_query();

		// Tax Query
		$this->handle_tax_query();

		// Parse meta query.
		$this->handle_meta_query();

		// Handle complex date queries.
		$this->handle_date_query();

		// If 'offset' is provided, it takes precedence over 'paged'.
		$this->handle_limits();

		// Order
		$this->handle_orderby();

		// Groupby
		$this->handle_groupby();

		$old_request   = "SELECT $this->found_rows $this->distinct $this->fields FROM {$wpdb->posts} $this->join WHERE 1=1 $this->where $this->groupby $this->orderby $this->limits";
		$this->request = $old_request;

		// excute sql query
		$this->posts = $wpdb->get_results($this->request);
		return $this->posts;
	}

	/**
	 * IN 比 OR 快？
	 * @link https://stackoverflow.com/questions/782915/mysql-or-vs-in-performance
	 *
	 * 尚未执行 SQL 数据清洗
	 **/
	private function handle_post_field_query() {
		$wpdb = &$this->wpdb;
		$q    = &$this->query_vars;
		foreach ($q as $post_filed => $value) {
			if (in_array($post_filed, ['post_name', 'post_type', 'post_status', 'post_mime_type'])) {
				if (is_array($value)) {
					$value = array_map('esc_sql', $value);
					$value = "'" . implode("','", $value) . "'";
					$this->where .= "AND {$wpdb->posts}.{$post_filed} IN ($value)";
				} else {
					$this->where .= $wpdb->prepare(" AND {$wpdb->posts}.{$post_filed} = %s ", $value);
				}
			} elseif (in_array($post_filed, ['ID', 'post_author', 'post_parent'])) {
				if (is_array($value)) {
					$value = implode(',', $value);
					$this->where .= " AND {$wpdb->posts}.{$post_filed} IN ($value) ";
				} else {
					$this->where .= $wpdb->prepare(" AND {$wpdb->posts}.{$post_filed} = %d ", $value);
				}
			}
		}
	}

	private function handle_tax_query() {
		$wpdb            = &$this->wpdb;
		$q               = &$this->query_vars;
		$this->tax_query = new WP_Tax_Query($q['tax_query']);
		$clauses         = $this->tax_query->get_sql($wpdb->posts, 'ID');
		$this->join .= $clauses['join'];
		$this->where .= $clauses['where'];
	}

	private function handle_meta_query() {
		$wpdb             = &$this->wpdb;
		$q                = &$this->query_vars;
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars($q);
		if (!empty($this->meta_query->queries)) {
			$clauses = $this->meta_query->get_sql('post', $wpdb->posts, 'ID', $this);
			$this->join .= $clauses['join'];
			$this->where .= $clauses['where'];
		}
	}

	private function handle_date_query() {
		$wpdb = &$this->wpdb;
		$q    = &$this->query_vars;
		if (!empty($q['date_query'])) {
			$this->date_query = new WP_Date_Query($q['date_query']);
			$this->where .= $this->date_query->get_sql();
		}
	}

	private function handle_orderby() {
		$wpdb = &$this->wpdb;
		$q    = &$this->query_vars;
		if (empty($q['orderby'])) {
			$this->orderby = "{$wpdb->posts}.post_date " . $q['order'];
		} elseif (!empty($q['order'])) {
			// $orderby = "{$q['orderby']} {$q['order']}";
		}

		if (!empty($this->orderby)) {
			$this->orderby = 'ORDER BY ' . $this->orderby;
		}
	}

	private function handle_limits() {
		$q = &$this->query_vars;
		if (isset($q['offset']) && is_numeric($q['offset'])) {
			$q['offset'] = absint($q['offset']);
			$pgstrt      = $q['offset'] . ', ';
		} else {
			$pgstrt = absint(($this->page - 1) * $q['posts_per_page']) . ', ';
		}
		$this->limits = 'LIMIT ' . $pgstrt . $q['posts_per_page'];
	}

	private function handle_groupby() {
		$wpdb = &$this->wpdb;
		if (!empty($this->tax_query->queries) || !empty($this->meta_query->queries)) {
			$this->groupby = "{$wpdb->posts}.ID";
		}

		if (!empty($this->groupby)) {
			$this->groupby = 'GROUP BY ' . $this->groupby;
		}
	}

}
