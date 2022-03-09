<?php
/**
 * Query API: WP_Query class
 *
 */
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

	/**
	 * The number of posts for the current query.
	 *
	 * @since 1.5.0
	 * @var int
	 */
	public $post_count = 0;

	/**
	 * Index of the current item in the loop.
	 *
	 * @since 1.5.0
	 * @var int
	 */
	public $current_post = -1;

	/**
	 * Whether the loop has started and the caller is in the loop.
	 *
	 * @since 2.0.0
	 * @var bool
	 */
	public $in_the_loop = false;

	/**
	 * The current post.
	 *
	 * This property does not get populated when the `fields` argument is set to
	 * `ids` or `id=>parent`.
	 *
	 * @since 1.5.0
	 * @var WP_Post|null
	 */
	public $post;

	/**
	 * The number of found posts for the current query.
	 *
	 * If limit clause was not used, equals $post_count.
	 *
	 * @since 2.1.0
	 * @var int
	 */
	public $found_posts = 0;

	/**
	 * The number of pages.
	 *
	 * @since 2.1.0
	 * @var int
	 */
	public $max_num_pages = 0;

	/**
	 * Holds the data for a single object that is queried.
	 *
	 * Holds the contents of a post, page, category, attachment.
	 *
	 * @since 1.5.0
	 * @var WP_Term|WP_Post_Type|WP_Post|WP_User|null
	 */
	public $queried_object;

	/**
	 * The ID of the queried object.
	 *
	 * @since 1.5.0
	 * @var int
	 */
	public $queried_object_id;

	/**
	 * SQL for the database query.
	 *
	 * @since 2.0.1
	 * @var string
	 */
	public $request;

	private static $default_query = [
		'ID'             => '',
		'post_name'      => '',
		'post_parent'    => '',
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'tax_query'      => [],
		'meta_query'     => [],
		'date_query'     => [],
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

		$this->query($query);

	}

	/**
	 * Sets up the WordPress query by parsing query string.
	 *
	 * @since 1.5.0
	 *
	 * @see WP_Query::parse_query() for all available arguments.
	 *
	 * @param array $query array of query arguments.
	 * @return WP_Post[]|int[] Array of post objects or post IDs.
	 */
	public function query(array $query) {
		$this->init_query_flags();
		$this->parse_query($query);
		return $this->get_posts();
	}

	/**
	 * Is the query the main query?
	 *
	 * @since 3.3.0
	 *
	 * @global WP_Query $wp_query WordPress Query object.
	 *
	 * @return bool Whether the query is the main query.
	 */
	public function is_main_query() {
		global $wp_query;
		return $wp_query === $this;
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

		if ($qv['tax_query']) {
			$this->is_archive = true;
			$this->is_tax     = true;
		} elseif ('' !== $qv['post_name'] or $qv['ID']) {
			if ('page' == $qv['post_type']) {
				$this->is_page = true;
			} else {
				$this->is_single = true;
			}
		} elseif (!empty($qv['post_type']) && !is_array($qv['post_type'])) {
			$post_type_obj = get_post_type_object($qv['post_type']);
			if (!empty($post_type_obj->has_archive)) {
				$this->is_post_type_archive = true;
				$this->is_archive           = true;
			}
		}

		$this->is_singular = $this->is_single || $this->is_page;

		if (!$this->is_singular and !$this->is_archive and !$this->is_search and !$this->is_404) {
			$this->is_home = true;
		}
	}

	/**
	 * Retrieves the value of a query variable.
	 *
	 * @since 1.5.0
	 * @since 3.9.0 The `$default` argument was introduced.
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
	 * @since 1.5.0
	 *
	 * @param string $query_var Query variable key.
	 * @param mixed  $value     Query variable value.
	 */
	public function set($query_var, $value) {
		$this->query_vars[$query_var] = $value;
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

		if ($this->posts) {
			$this->post_count = count($this->posts);
			$this->set_found_posts($q, $this->limits);
			$this->post = reset($this->posts);
		}

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
			if ('' === $value or 'any' == $value) {
				continue;
			}

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

	/**
	 * Retrieves the currently queried object.
	 *
	 * If queried object is not set, then the queried object will be set from
	 * the category, tag, taxonomy, posts page, single post, page, or author
	 * query variable. After it is set up, it will be returned.
	 *
	 * @since 1.5.0
	 *
	 * @return WP_Term|WP_Post_Type|WP_Post|WP_User|null The queried object.
	 */
	public function get_queried_object() {
		if ($this->queried_object) {
			return $this->queried_object;
		}

		$this->queried_object    = null;
		$this->queried_object_id = null;

		if ($this->is_tax) {
			// For other tax queries, grab the first term from the first clause.
			if (!empty($this->tax_query->queried_terms)) {
				$queried_taxonomies = array_keys($this->tax_query->queried_terms);
				$matched_taxonomy   = reset($queried_taxonomies);
				$query              = $this->tax_query->queried_terms[$matched_taxonomy];

				if (!empty($query['terms'])) {
					if ('term_id' === $query['field']) {
						$term = get_term(reset($query['terms']));
					} else {
						$term = get_term_by($query['field'], reset($query['terms']), $matched_taxonomy);
					}
				}
			}

			if (!empty($term) && !is_wp_error($term)) {
				$this->queried_object    = $term;
				$this->queried_object_id = (int) $term->term_id;
			}
		} elseif ($this->is_post_type_archive) {
			$post_type = $this->get('post_type');
			if (is_array($post_type)) {
				$post_type = reset($post_type);
			}
			$this->queried_object = get_post_type_object($post_type);
		} elseif ($this->is_singular && !empty($this->post)) {
			$this->queried_object    = $this->post;
			$this->queried_object_id = (int) $this->post->ID;
		} elseif ($this->is_author) {
			$this->queried_object_id = (int) $this->get('author');
			$this->queried_object    = get_userdata($this->queried_object_id);
		}

		return $this->queried_object;
	}

	/**
	 * Retrieves the ID of the currently queried object.
	 *
	 * @since 1.5.0
	 *
	 * @return int
	 */
	public function get_queried_object_id() {
		$this->get_queried_object();

		if (isset($this->queried_object_id)) {
			return $this->queried_object_id;
		}

		return 0;
	}

	/**
	 * Set up the next post and iterate current post index.
	 *
	 * @since 1.5.0
	 *
	 * @return WP_Post Next post.
	 */
	public function next_post(): object{
		$this->current_post++;

		/** @var WP_Post */
		$this->post = $this->posts[$this->current_post];
		return $this->post;
	}

	/**
	 * Sets up the current post.
	 *
	 * Retrieves the next post, sets up the post, sets the 'in the loop'
	 * property to true.
	 *
	 * @since 1.5.0
	 *
	 * @global WP_Post $post Global post object.
	 */
	public function the_post() {
		global $post;
		$this->in_the_loop = true;

		if (-1 == $this->current_post) {
			// Loop has just started.
			/**
			 * Fires once the loop is started.
			 *
			 * @since 2.0.0
			 *
			 * @param WP_Query $query The WP_Query instance (passed by reference).
			 */
			do_action_ref_array('loop_start', [ & $this]);
		}

		$post = $this->next_post();
		$this->setup_postdata($post);
	}

	/**
	 * Determines whether there are more posts available in the loop.
	 *
	 * Calls the {@see 'loop_end'} action when the loop is complete.
	 *
	 * @since 1.5.0
	 *
	 * @return bool True if posts are available, false if end of the loop.
	 */
	public function have_posts() {
		if ($this->current_post + 1 < $this->post_count) {
			return true;
		} elseif ($this->current_post + 1 == $this->post_count && $this->post_count > 0) {
			/**
			 * Fires once the loop has ended.
			 *
			 * @since 2.0.0
			 *
			 * @param WP_Query $query The WP_Query instance (passed by reference).
			 */
			do_action_ref_array('loop_end', [ & $this]);
			// Do some cleaning up after the loop.
			$this->rewind_posts();
		} elseif (0 === $this->post_count) {
			/**
			 * Fires if no results are found in a post query.
			 *
			 * @since 4.9.0
			 *
			 * @param WP_Query $query The WP_Query instance.
			 */
			do_action('loop_no_results', $this);
		}

		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Rewind the posts and reset post index.
	 *
	 * @since 1.5.0
	 */
	public function rewind_posts() {
		$this->current_post = -1;
		if ($this->post_count > 0) {
			$this->post = $this->posts[0];
		}
	}

	/**
	 * Set up global post data.
	 *
	 * @since 4.1.0
	 * @since 4.4.0 Added the ability to pass a post ID to `$post`.
	 *
	 * @global int     $id
	 * @global int     $page
	 * @global array   $pages
	 * @global int     $multipage
	 * @global int     $more
	 * @global int     $numpages
	 *
	 * @param WP_Post|object|int $post WP_Post instance or Post ID/object.
	 * @return true True when finished.
	 */
	public function setup_postdata(object $post) {
		global $id, $page, $pages, $multipage, $more, $numpages;

		if (!get_object_vars($post)) {
			return false;
		}

		if (!($post instanceof WP_Post)) {
			$post = new WP_Post($post);
		}

		$elements = $this->generate_postdata($post);
		if (false === $elements) {
			return;
		}

		$id        = $elements['id'];
		$page      = $elements['page'];
		$pages     = $elements['pages'];
		$multipage = $elements['multipage'];
		$more      = $elements['more'];
		$numpages  = $elements['numpages'];

		/**
		 * Fires once the post data has been set up.
		 *
		 * @since 2.8.0
		 * @since 4.1.0 Introduced `$query` parameter.
		 *
		 * @param WP_Post  $post  The Post object (passed by reference).
		 * @param WP_Query $query The current Query object (passed by reference).
		 */
		do_action_ref_array('the_post', [ & $post, &$this]);

		return true;
	}

	/**
	 * Generate post data.
	 *
	 * @since 5.2.0
	 *
	 * @param WP_Post|object $post WP_Post instance or Post ID/object.
	 * @return array|false Elements of post or false on failure.
	 */
	public function generate_postdata(object $post) {
		if (!get_object_vars($post)) {
			return false;
		}

		if (!($post instanceof WP_Post)) {
			$post = new WP_Post($post);
		}

		$id        = (int) $post->ID;
		$numpages  = 1;
		$multipage = 0;
		$page      = $this->get('page');
		if (!$page) {
			$page = 1;
		}

		$content = $post->post_content;
		if (false !== strpos($content, '<!--nextpage-->')) {
			$content = str_replace("\n<!--nextpage-->\n", '<!--nextpage-->', $content);
			$content = str_replace("\n<!--nextpage-->", '<!--nextpage-->', $content);
			$content = str_replace("<!--nextpage-->\n", '<!--nextpage-->', $content);

			// Remove the nextpage block delimiters, to avoid invalid block structures in the split content.
			$content = str_replace('<!-- wp:nextpage -->', '', $content);
			$content = str_replace('<!-- /wp:nextpage -->', '', $content);

			// Ignore nextpage at the beginning of the content.
			if (0 === strpos($content, '<!--nextpage-->')) {
				$content = substr($content, 15);
			}

			$pages = explode('<!--nextpage-->', $content);
		} else {
			$pages = [$post->post_content];
		}

		/**
		 * Filters the "pages" derived from splitting the post content.
		 *
		 * "Pages" are determined by splitting the post content based on the presence
		 * of `<!-- nextpage -->` tags.
		 *
		 * @since 4.4.0
		 *
		 * @param string[] $pages Array of "pages" from the post content split by `<!-- nextpage -->` tags.
		 * @param WP_Post  $post  Current post object.
		 */
		$pages = apply_filters('content_pagination', $pages, $post);

		$numpages = count($pages);

		if ($numpages > 1) {
			if ($page > 1) {
				$more = 1;
			}
			$multipage = 1;
		} else {
			$multipage = 0;
			$more      = 0;
		}

		$elements = compact('id', 'page', 'pages', 'multipage', 'more', 'numpages');

		return $elements;
	}
	/**
	 * After looping through a nested query, this function
	 * restores the $post global to the current post in this query.
	 *
	 * @since 3.7.0
	 *
	 * @global WP_Post $post Global post object.
	 */
	public function reset_postdata() {
		if (!empty($this->post)) {
			$GLOBALS['post'] = $this->post;
			$this->setup_postdata($this->post);
		}
	}

	/**
	 * Set up the amount of found posts and the number of pages (if limit clause was used)
	 * for the current query.
	 *
	 * @since 3.5.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param array  $q      Query variables.
	 * @param string $limits LIMIT clauses of the query.
	 */
	private function set_found_posts($q, $limits) {
		global $wpdb;

		// Bail if posts is an empty array. Continue if posts is an empty string,
		// null, or false to accommodate caching plugins that fill posts later.
		if ($q['no_found_rows'] || (is_array($this->posts) && !$this->posts)) {
			return;
		}

		if (!empty($limits)) {
			/**
			 * Filters the query to run for retrieving the found posts.
			 *
			 * @since 2.1.0
			 *
			 * @param string   $found_posts_query The query to run to find the found posts.
			 * @param WP_Query $query             The WP_Query instance (passed by reference).
			 */
			$found_posts_query = apply_filters_ref_array('found_posts_query', ['SELECT FOUND_ROWS()', &$this]);

			$this->found_posts = (int) $wpdb->get_var($found_posts_query);
		} else {
			if (is_array($this->posts)) {
				$this->found_posts = count($this->posts);
			} else {
				if (null === $this->posts) {
					$this->found_posts = 0;
				} else {
					$this->found_posts = 1;
				}
			}
		}

		/**
		 * Filters the number of found posts for the query.
		 *
		 * @since 2.1.0
		 *
		 * @param int      $found_posts The number of posts found.
		 * @param WP_Query $query       The WP_Query instance (passed by reference).
		 */
		$this->found_posts = (int) apply_filters_ref_array('found_posts', [$this->found_posts, &$this]);

		if (!empty($limits)) {
			$this->max_num_pages = ceil($this->found_posts / $q['posts_per_page']);
		}
	}
}
