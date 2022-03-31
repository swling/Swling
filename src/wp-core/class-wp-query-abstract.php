<?php
/**
 * Query API: WP_Query class
 *
 * general query var:
 * - 'number'
 * - 'offset'
 * - 'search'
 * - 'fields'
 * - 'paged'
 * - 'orderby'
 * - 'order'
 * - 'tax_query'
 * - 'meta_query'
 * - 'date_query'
 *
 */
abstract class WP_Query_Abstract {

	// Define DB table info in child class
	protected $wpdb              = '';
	protected $table             = '';
	protected $table_name        = '';
	protected $primary_id_column = '';
	protected $date_column       = '';
	protected $meta_type         = '';
	protected $str_column        = [];
	protected $int_column        = [];
	protected $search_column     = [];
	protected $default_order_by  = '';

	/**
	 * Query vars set by the user.
	 *
	 * @var array
	 */
	public $query;

	/**
	 * Query vars, after parsing.
	 *
	 * @var array
	 */
	public $query_vars = [];

	/**
	 * Taxonomy query, as passed to get_tax_sql().
	 *
	 * @var WP_Tax_Query A taxonomy query instance.
	 */
	public $tax_query;

	/**
	 * Metadata query container.
	 *
	 * @var WP_Meta_Query A meta query instance.
	 */
	public $meta_query = false;

	/**
	 * Date query container.
	 *
	 * @var WP_Date_Query A date query instance.
	 */
	public $date_query = false;

	/**
	 * Array of post objects or post IDs.
	 *
	 * @var WP_Post[]|int[]
	 */
	public $results;

	/**
	 * The number of results for the current query.
	 *
	 * @var int
	 */
	public $result_count = 0;

	/**
	 * The number of found results for the current query.
	 *
	 * If limit clause was not used, equals $post_count.
	 *
	 * @var int
	 */
	public $found_results = 0;

	/**
	 * The number of pages.
	 *
	 * @var int
	 */
	public $max_num_pages = 0;

	/**
	 * SQL for the database query.
	 *
	 * @var string
	 */
	public $request;

	// $wpdb query args
	protected $fields     = '';
	protected $found_rows = '';
	protected $distinct   = '';
	protected $where      = '1=1';
	protected $limits     = '';
	protected $join       = '';
	protected $search     = '';
	protected $groupby    = '';
	protected $page       = 1;
	protected $orderby    = '';

	// prevent duplicate queries
	protected $executed = false;

	/**
	 * Constructor.
	 *
	 * Sets up the WordPress query, if parameter is not empty.
	 *
	 * @see $this->parse_query() for all available arguments.
	 *
	 * @param array $query array of vars.
	 */
	public function __construct(array $query) {
		global $wpdb;
		$this->wpdb = $wpdb;

		$table_name  = $this->table_name;
		$this->table = $wpdb->$table_name;

		$this->parse_query($query);
		if (!empty($query)) {
			$this->query();
		}
	}

	/**
	 * Sets up the WordPress query by parsing query string.
	 *
	 * @see WP_Query::parse_query() for all available arguments.
	 *
	 * @param array $query array of query arguments.
	 * @return WP_Post[]|int[] Array of post objects or post IDs.
	 */
	public function query() {
		return $this->get_results();
	}

	/**
	 * Parse a query string and set query type booleans.
	 */
	abstract public function parse_query(array $query);

	/**
	 * Retrieves the value of a query variable.
	 *
	 * @param string $query_var Query variable key.
	 * @param mixed  $default   Optional. Value to return if the query variable is not set. Default empty string.
	 * @return mixed Contents of the query variable.
	 */
	public function get($query_var, $default = '') {
		if (isset($this->query_vars[$query_var])) {
			return $this->query_vars[$query_var];
		}

		return $default;
	}

	/**
	 * Sets the value of a query variable.
	 *
	 * @param string $query_var Query variable key.
	 * @param mixed  $value     Query variable value.
	 */
	public function set($query_var, $value) {
		$this->query_vars[$query_var] = $value;
	}

	/**
	 * Retrieves an array of results based on query variables.
	 */
	public function get_results() {
		// prevent duplicate queries
		if ($this->executed) {
			return $this->results;
		}

		/**
		 * Fires after the query variable object is created, but before the actual query is run.
		 *
		 * Note: If using conditional tags, use the method versions within the passed instance
		 * (e.g. $this->is_main_query() instead of is_main_query()). This is because the functions
		 * like is_main_query() test against the global $wp_query instance, not the passed one.
		 *
		 * @param WP_Query $query The WP_Query instance (passed by reference).
		 */
		do_action_ref_array("pre_get_{$this->table_name}", [ & $this]);

		// Calculate SQL query statement
		$this->calculate_sql();

		// Get Cache
		$cache = $this->get_query_cache();
		if (false !== $cache) {
			$this->results = $this->instantiate_results($cache);
			return $this->results;
		}

		// excute sql query
		$fields = $this->query_vars['fields'];
		if (is_array($fields) or 'all' == $fields or !$fields) {
			$results = $this->wpdb->get_results($this->request);
			$results = $this->instantiate_results($results);
		} else {
			$results = $this->wpdb->get_col($this->request);
		}

		if ($results) {
			$this->result_count = count($results);
			$this->set_found_results($this->query_vars, $this->limits);
		}
		$this->results = $results;

		// prevent duplicate queries
		$this->executed = true;

		// Set Cache
		$this->set_query_cache();

		return $this->results;
	}

	/**
	 * Calculate SQL query statement
	 *
	 */
	private function calculate_sql() {
		if ($this->request) {
			return $this->request;
		}

		$q = &$this->query_vars;

		// Select fileds
		$this->parse_select_fields();

		// Post Field Query
		$this->parse_row_query();

		// Tax Query
		$this->parse_tax_query();

		// Parse meta query.
		$this->parse_meta_query();

		// Handle complex date queries.
		$this->parse_date_query();

		// field __in / __not_in
		$this->parse_in();

		// Other expansion query. Used to expand the query, default is empty.
		$this->parse_expansion_query();

		// Search
		if ('' !== ($q['search'])) {
			$this->parse_search_query($q['search']);
		}

		// If 'offset' is provided, it takes precedence over 'paged'.
		$this->parse_limits();

		// Order
		$this->parse_orderby();

		// Groupby
		$this->parse_groupby();

		if (!$q['no_found_rows'] && !empty($this->limits)) {
			$this->found_rows = 'SQL_CALC_FOUND_ROWS';
		}

		$this->request = "SELECT {$this->found_rows} {$this->distinct} {$this->fields} FROM {$this->table} {$this->join} WHERE {$this->where} {$this->groupby} {$this->orderby} {$this->limits}";
	}

	/**
	 * Set up the amount of found results and the number of pages (if limit clause was used)
	 * for the current query.
	 *
	 * @param array  $q      Query variables.
	 * @param string $limits LIMIT clauses of the query.
	 */
	protected function set_found_results($q, $limits) {
		global $wpdb;

		// Bail if results is an empty array. Continue if results is an empty string,
		// null, or false to accommodate caching plugins that fill results later.
		if ($q['no_found_rows'] || (is_array($this->results) && !$this->results)) {
			return;
		}

		if (!empty($limits)) {
			/**
			 * Filters the query to run for retrieving the found results.
			 *
			 * @param string   $found_results_query The query to run to find the found results.
			 * @param WP_Query $query             The WP_Query instance (passed by reference).
			 */
			$found_results_query = apply_filters_ref_array('found_results_query', ['SELECT FOUND_ROWS()', &$this]);

			$this->found_results = (int) $wpdb->get_var($found_results_query);
		} else {
			if (is_array($this->results)) {
				$this->found_results = count($this->results);
			} else {
				if (null === $this->results) {
					$this->found_results = 0;
				} else {
					$this->found_results = 1;
				}
			}
		}

		/**
		 * Filters the number of found results for the query.
		 *
		 * @param int      $found_results The number of results found.
		 * @param WP_Query $query       The WP_Query instance (passed by reference).
		 */
		$this->found_results = (int) apply_filters_ref_array('found_results', [$this->found_results, &$this]);

		if (!empty($limits)) {
			$this->max_num_pages = ceil($this->found_results / $q['number']);
		}
	}

	protected function parse_select_fields() {
		$q = &$this->query_vars;

		if (!$q['fields'] or 'all' == $q['fields']) {
			$this->fields = '*';
			return;
		}

		if ('ids' == $q['fields']) {
			$this->fields = "$this->primary_id_column";
			return;
		}

		if (is_array($q['fields'])) {
			$this->fields = implode("', '", array_map('esc_sql', $q['fields']));
		} else {
			$this->fields = $q['fields'];
		}
	}

	/**
	 * IN 比 OR 快？
	 * @link https://stackoverflow.com/questions/782915/mysql-or-vs-in-performance
	 *
	 **/
	protected function parse_row_query() {
		global $wpdb;
		$qv = &$this->query_vars;

		foreach ($qv as $key => $value) {
			if ('' === $value or 'any' == $value) {
				continue;
			}

			// get results by string column
			if (in_array($key, $this->str_column)) {
				if (is_array($value)) {
					$this->where .= " AND {$key} IN ('" . implode("', '", array_map('esc_sql', $value)) . "')";
				} else {
					$this->where .= $wpdb->prepare(" AND {$key} = %s ", $value);
				}
				continue;
			}

			// get results by int column
			if (in_array($key, $this->int_column)) {
				if (is_array($value)) {
					$this->where .= " AND {$key} IN ('" . implode("', '", array_map('intval', $value)) . "')";
				} else {
					$this->where .= $wpdb->prepare(" AND {$key} = %d ", $value);
				}
				continue;
			}
		}
	}

	protected function parse_tax_query() {
		$q = &$this->query_vars;
		if (!isset($q['tax_query'])) {
			return;
		}

		$this->tax_query = new WP_Tax_Query($q['tax_query']);
		$clauses         = $this->tax_query->get_sql($this->table, $this->primary_id_column);
		$this->join .= $clauses['join'];
		$this->where .= $clauses['where'];
	}

	protected function parse_meta_query() {
		$q                = &$this->query_vars;
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars($q);
		if (!empty($this->meta_query->queries)) {
			$clauses = $this->meta_query->get_sql($this->meta_type, $this->table, $this->primary_id_column, $this);
			$this->join .= $clauses['join'];
			$this->where .= $clauses['where'];
		}
	}

	protected function parse_date_query() {
		$q = &$this->query_vars;
		if (!empty($q['date_query']) and $this->date_column) {
			$this->date_query = new WP_Date_Query($q['date_query'], $this->date_column);
			$this->where .= $this->date_query->get_sql();
		}
	}

	protected function parse_in() {
		$q = &$this->query_vars;
		foreach ($q as $key => $value) {
			if (!$value) {
				continue;
			}

			if (str_ends_with($key, '__in')) {
				$field         = str_replace('__in', '', $key);
				$sanitized__in = array_map('esc_sql', $value);
				$__in          = implode("','", $sanitized__in);
				$this->where .= " AND {$field} IN ( '$__in' )";
				continue;
			}

			if (str_ends_with($key, '__not_in')) {
				$field         = str_replace('__not_in', '', $key);
				$sanitized__in = array_map('esc_sql', $value);
				$not__in       = implode("','", $sanitized__in);
				$this->where .= " AND {$field} NOT IN ( '$not__in' )";
				continue;
			}
		}
	}

	/**
	 * Other relationships query. Used to expand the query, default is empty.
	 * When needed, define in subclasses.
	 */
	protected function parse_expansion_query() {}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns
	 *
	 * @param string $string
	 */
	protected function parse_search_query(string $string) {
		global $wpdb;

		$like = '%' . $wpdb->esc_like($string) . '%';

		$searches = [];
		foreach ($this->search_column as $col) {
			$searches[] = $wpdb->prepare("$col LIKE %s", $like);
		}

		$this->where .= ' AND (' . implode(' OR ', $searches) . ')';
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

	protected function parse_orderby() {
		$q            = &$this->query_vars;
		$q['orderby'] = $q['orderby'] ?: $this->default_order_by;
		if (!$q['orderby'] or 'none' == $q['orderby']) {
			return;
		}

		$_orderby = strtolower($q['orderby']);
		if ($_orderby == 'meta_value' or $_orderby == 'meta_value_num') {
			$_orderby = $this->parse_orderby_meta($_orderby);
		}

		$order = $this->parse_order($q['order']);
		if (!empty($order)) {
			$orderby = "{$_orderby} {$order}";
		}

		if (!empty($orderby)) {
			$this->orderby = 'ORDER BY ' . $orderby;
		}
	}

	/**
	 * Generate the ORDER BY clause for an 'orderby' param that is potentially related to a meta query.
	 *
	 * @param string $orderby_raw Raw 'orderby' value passed to WP_Term_Query.
	 * @return string ORDER BY clause.
	 */
	protected function parse_orderby_meta($orderby_raw) {
		$orderby = '';

		// Tell the meta query to generate its SQL, so we have access to table aliases.
		$this->meta_query->get_sql($this->meta_type, $this->table, $this->primary_id_column);
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

	protected function parse_limits() {
		$q = &$this->query_vars;

		if (!$q['number']) {
			return;
		}

		if (isset($q['offset']) && is_numeric($q['offset'])) {
			$q['offset'] = absint($q['offset']);
			$pgstrt      = $q['offset'] . ', ';
		} else {
			$page = absint($q['paged'] ?? 0);
			if (!$page) {
				$page = 1;
			}
			$pgstrt = absint(($page - 1) * $q['number']) . ', ';
		}

		$this->limits = 'LIMIT ' . $pgstrt . $q['number'];
	}

	protected function parse_groupby() {
		if (!empty($this->tax_query->queries) || !empty($this->meta_query->queries)) {
			$this->groupby = "{$this->table}.$this->primary_id_column";
		}

		if (!empty($this->groupby)) {
			$this->groupby = 'GROUP BY ' . $this->groupby;
		}
	}

	/**
	 * instantiate the results item to WP object
	 */
	protected function instantiate_results(array $results): array{
		return array_map([$this, 'instantiate_item'], $results);
	}

	/**
	 * instantiate the results item to WP object
	 */
	protected static function instantiate_item(object $item): object {
		return $item;
	}

	protected function set_query_cache() {
		$cache_key = $this->build_cache_key();
		wp_cache_set($cache_key, $this->results, $this->table_name, DAY_IN_SECONDS);
	}

	protected function get_query_cache() {
		$cache_key = $this->build_cache_key();
		return wp_cache_get($cache_key, $this->table_name);
	}

	/**
	 * last_changed 策略尚未完成
	 */
	protected function build_cache_key(): string{
		// $args can be anything. Only use the args defined in defaults to compute the key.
		// $key = md5(serialize(wp_array_slice_assoc($args, array_keys($this->query_var_defaults))) . serialize($taxonomies) . $this->request);

		$key          = md5($this->request);
		$last_changed = wp_cache_get_last_changed($this->table_name);
		return "get_{$this->table_name}:{$key}:{$last_changed}";
	}

}
