<?php
/**
 * Query API: WP_Query class
 *
 */
abstract class WP_Query_Abstract {

	// Define DB table info in child class
	protected $wpdb              = '';
	protected $table             = '';
	protected $table_name        = '';
	protected $primary_id_column = '';
	protected $meta_type         = '';
	protected $int_column        = [];

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
	protected $where      = '';
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

		// excute sql query
		$this->results = $this->wpdb->get_results($this->request);
		if ($this->results) {
			$this->result_count = count($this->results);
			$this->set_found_results($this->query_vars, $this->limits);
		}

		// prevent duplicate queries
		$this->executed = true;

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
		switch ($q['fields']) {
			case 'ids':
				$this->fields = "{$this->table}.$this->primary_id_column";
				break;
			case 'id=>parent':
				$this->fields = "{$this->table}.$this->primary_id_column, {$this->table}.post_parent";
				break;
			default:
				$this->fields = "{$this->table}.*";
		}

		// Post Field Query
		$this->parse_field_query();

		// Tax Query
		$this->parse_tax_query();

		// Parse meta query.
		$this->parse_meta_query();

		// Handle complex date queries.
		$this->parse_date_query();

		// If 'offset' is provided, it takes precedence over 'paged'.
		$this->parse_limits();

		// Order
		$this->parse_orderby();

		// Groupby
		$this->parse_groupby();

		if (!$q['no_found_rows'] && !empty($this->limits)) {
			$this->found_rows = 'SQL_CALC_FOUND_ROWS';
		}

		$this->request = "SELECT {$this->found_rows} {$this->distinct} {$this->fields} FROM {$this->table} {$this->join} WHERE 1=1 {$this->where} {$this->groupby} {$this->orderby} {$this->limits}";
	}

	/**
	 * Set up the amount of found results and the number of pages (if limit clause was used)
	 * for the current query.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
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

	/**
	 * IN 比 OR 快？
	 * @link https://stackoverflow.com/questions/782915/mysql-or-vs-in-performance
	 *
	 **/
	protected function parse_field_query() {
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
		$q               = &$this->query_vars;
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
			$clauses = $this->meta_query->get_sql('post', $this->table, $this->primary_id_column, $this);
			$this->join .= $clauses['join'];
			$this->where .= $clauses['where'];
		}
	}

	protected function parse_date_query() {
		$q = &$this->query_vars;
		if (!empty($q['date_query'])) {
			$this->date_query = new WP_Date_Query($q['date_query']);
			$this->where .= $this->date_query->get_sql();
		}
	}

	protected function parse_orderby() {
		$wpdb = &$this->wpdb;
		$q    = &$this->query_vars;
		if (empty($q['orderby'])) {
			$this->orderby = "{$this->table}.post_date " . $q['order'];
		} elseif (!empty($q['order'])) {
			// $orderby = "{$q['orderby']} {$q['order']}";
		}

		if (!empty($this->orderby)) {
			$this->orderby = 'ORDER BY ' . $this->orderby;
		}
	}

	protected function parse_limits() {
		$q = &$this->query_vars;
		if (isset($q['offset']) && is_numeric($q['offset'])) {
			$q['offset'] = absint($q['offset']);
			$pgstrt      = $q['offset'] . ', ';
		} else {
			$pgstrt = absint(($this->page - 1) * $q['number']) . ', ';
		}
		$this->limits = 'LIMIT ' . $pgstrt . $q['number'];
	}

	protected function parse_groupby() {
		$wpdb = &$this->wpdb;
		if (!empty($this->tax_query->queries) || !empty($this->meta_query->queries)) {
			$this->groupby = "{$this->table}.$this->primary_id_column";
		}

		if (!empty($this->groupby)) {
			$this->groupby = 'GROUP BY ' . $this->groupby;
		}
	}

}
