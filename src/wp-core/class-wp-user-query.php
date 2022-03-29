<?php
/**
 * User API: WP_User_Query class
 *
 * @package WordPress
 * @subpackage Users
 * @since 4.4.0
 */

/**
 * Core class used for querying users.
 *
 * @since 3.1.0
 *
 * @see WP_User_Query::prepare_query() for information on accepted arguments.
 */
class WP_User_Query extends WP_Query_Abstract {

	protected $table_name        = 'users';
	protected $primary_id_column = 'ID';
	protected $meta_type         = 'user';
	protected $int_column        = ['ID', 'user_status'];
	protected $str_column        = ['user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url', 'user_activation_key', 'display_name'];
	protected $search_column     = ['user_login', 'user_email', 'display_name'];
	protected $default_order_by  = ['ID'];

	/**
	 * Total number of found users for the current query
	 *
	 * @since 3.1.0
	 * @var int
	 */
	private $total_users = 0;

	private $compat_fields = ['results', 'total_users'];

	/**
	 * Fills in missing query variables with default values.
	 *
	 * @since 4.4.0
	 *
	 * @param array $args Query vars, as passed to `WP_User_Query`.
	 * @return array Complete query variables with undefined ones filled in with defaults.
	 */
	public static function fill_query_vars($args) {
		$defaults = [
			'role'                => '',
			'role__in'            => [],
			'role__not_in'        => [],
			'capability'          => '',
			'capability__in'      => [],
			'capability__not_in'  => [],
			'meta_key'            => '',
			'meta_value'          => '',
			'meta_compare'        => '',
			'include'             => [],
			'exclude'             => [],
			'search'              => '',
			'search_columns'      => [],
			'orderby'             => 'login',
			'order'               => 'ASC',
			'offset'              => '',
			'number'              => '',
			'paged'               => 1,
			'count_total'         => true,
			'fields'              => 'all',
			'who'                 => '',
			'has_published_posts' => null,
			'nicename'            => '',
			'nicename__in'        => [],
			'nicename__not_in'    => [],
			'login'               => '',
			'login__in'           => [],
			'login__not_in'       => [],
			'no_found_rows'       => true,
		];

		return wp_parse_args($args, $defaults);
	}

	/**
	 * Prepare the query variables.
	 *
	 * @since 3.1.0
	 * @since 4.1.0 Added the ability to order by the `include` value.
	 * @since 4.2.0 Added 'meta_value_num' support for `$orderby` parameter. Added multi-dimensional array syntax
	 *              for `$orderby` parameter.
	 * @since 4.3.0 Added 'has_published_posts' parameter.
	 * @since 4.4.0 Added 'paged', 'role__in', and 'role__not_in' parameters. The 'role' parameter was updated to
	 *              permit an array or comma-separated list of values. The 'number' parameter was updated to support
	 *              querying for all users with using -1.
	 * @since 4.7.0 Added 'nicename', 'nicename__in', 'nicename__not_in', 'login', 'login__in',
	 *              and 'login__not_in' parameters.
	 * @since 5.1.0 Introduced the 'meta_compare_key' parameter.
	 * @since 5.3.0 Introduced the 'meta_type_key' parameter.
	 * @since 5.9.0 Added 'capability', 'capability__in', and 'capability__not_in' parameters.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @global int  $blog_id
	 *
	 * @param string|array $query {
	 *     Optional. Array or string of Query parameters.
	 *
	 *     @type int             $blog_id             The site ID. Default is the current site.
	 *     @type string|string[] $role                An array or a comma-separated list of role names that users must match
	 *                                                to be included in results. Note that this is an inclusive list: users
	 *                                                must match *each* role. Default empty.
	 *     @type string[]        $role__in            An array of role names. Matched users must have at least one of these
	 *                                                roles. Default empty array.
	 *     @type string[]        $role__not_in        An array of role names to exclude. Users matching one or more of these
	 *                                                roles will not be included in results. Default empty array.
	 *     @type string|string[] $meta_key            Meta key or keys to filter by.
	 *     @type string|string[] $meta_value          Meta value or values to filter by.
	 *     @type string          $meta_compare        MySQL operator used for comparing the meta value.
	 *                                                See WP_Meta_Query::__construct for accepted values and default value.
	 *     @type string          $meta_compare_key    MySQL operator used for comparing the meta key.
	 *                                                See WP_Meta_Query::__construct for accepted values and default value.
	 *     @type string          $meta_type           MySQL data type that the meta_value column will be CAST to for comparisons.
	 *                                                See WP_Meta_Query::__construct for accepted values and default value.
	 *     @type string          $meta_type_key       MySQL data type that the meta_key column will be CAST to for comparisons.
	 *                                                See WP_Meta_Query::__construct for accepted values and default value.
	 *     @type array           $meta_query          An associative array of WP_Meta_Query arguments.
	 *                                                See WP_Meta_Query::__construct for accepted values.
	 *     @type string          $capability          An array or a comma-separated list of capability names that users must match
	 *                                                to be included in results. Note that this is an inclusive list: users
	 *                                                must match *each* capability.
	 *                                                Does NOT work for capabilities not in the database or filtered via {@see 'map_meta_cap'}.
	 *                                                Default empty.
	 *     @type string[]        $capability__in      An array of capability names. Matched users must have at least one of these
	 *                                                capabilities.
	 *                                                Does NOT work for capabilities not in the database or filtered via {@see 'map_meta_cap'}.
	 *                                                Default empty array.
	 *     @type string[]        $capability__not_in  An array of capability names to exclude. Users matching one or more of these
	 *                                                capabilities will not be included in results.
	 *                                                Does NOT work for capabilities not in the database or filtered via {@see 'map_meta_cap'}.
	 *                                                Default empty array.
	 *     @type int[]           $include             An array of user IDs to include. Default empty array.
	 *     @type int[]           $exclude             An array of user IDs to exclude. Default empty array.
	 *     @type string          $search              Search keyword. Searches for possible string matches on columns.
	 *                                                When `$search_columns` is left empty, it tries to determine which
	 *                                                column to search in based on search string. Default empty.
	 *     @type string[]        $search_columns      Array of column names to be searched. Accepts 'ID', 'user_login',
	 *                                                'user_email', 'user_url', 'user_nicename', 'display_name'.
	 *                                                Default empty array.
	 *     @type string|array    $orderby             Field(s) to sort the retrieved users by. May be a single value,
	 *                                                an array of values, or a multi-dimensional array with fields as
	 *                                                keys and orders ('ASC' or 'DESC') as values. Accepted values are:
	 *                                                - 'ID'
	 *                                                - 'display_name' (or 'name')
	 *                                                - 'include'
	 *                                                - 'user_login' (or 'login')
	 *                                                - 'login__in'
	 *                                                - 'user_nicename' (or 'nicename'),
	 *                                                - 'nicename__in'
	 *                                                - 'user_email (or 'email')
	 *                                                - 'user_url' (or 'url'),
	 *                                                - 'user_registered' (or 'registered')
	 *                                                - 'post_count'
	 *                                                - 'meta_value',
	 *                                                - 'meta_value_num'
	 *                                                - The value of `$meta_key`
	 *                                                - An array key of `$meta_query`
	 *                                                To use 'meta_value' or 'meta_value_num', `$meta_key`
	 *                                                must be also be defined. Default 'user_login'.
	 *     @type string          $order               Designates ascending or descending order of users. Order values
	 *                                                passed as part of an `$orderby` array take precedence over this
	 *                                                parameter. Accepts 'ASC', 'DESC'. Default 'ASC'.
	 *     @type int             $offset              Number of users to offset in retrieved results. Can be used in
	 *                                                conjunction with pagination. Default 0.
	 *     @type int             $number              Number of users to limit the query for. Can be used in
	 *                                                conjunction with pagination. Value -1 (all) is supported, but
	 *                                                should be used with caution on larger sites.
	 *                                                Default -1 (all users).
	 *     @type int             $paged               When used with number, defines the page of results to return.
	 *                                                Default 1.
	 *     @type bool            $count_total         Whether to count the total number of users found. If pagination
	 *                                                is not needed, setting this to false can improve performance.
	 *                                                Default true.
	 *     @type string|string[] $fields              Which fields to return. Single or all fields (string), or array
	 *                                                of fields. Accepts:
	 *                                                - 'ID'
	 *                                                - 'display_name'
	 *                                                - 'user_login'
	 *                                                - 'user_nicename'
	 *                                                - 'user_email'
	 *                                                - 'user_url'
	 *                                                - 'user_registered'
	 *                                                - 'all' for all fields
	 *                                                - 'all_with_meta' to include meta fields.
	 *                                                Default 'all'.
	 *     @type string          $who                 Type of users to query. Accepts 'authors'.
	 *                                                Default empty (all users).
	 *     @type bool|string[]   $has_published_posts Pass an array of post types to filter results to users who have
	 *                                                published posts in those post types. `true` is an alias for all
	 *                                                public post types.
	 *     @type string          $nicename            The user nicename. Default empty.
	 *     @type string[]        $nicename__in        An array of nicenames to include. Users matching one of these
	 *                                                nicenames will be included in results. Default empty array.
	 *     @type string[]        $nicename__not_in    An array of nicenames to exclude. Users matching one of these
	 *                                                nicenames will not be included in results. Default empty array.
	 *     @type string          $login               The user login. Default empty.
	 *     @type string[]        $login__in           An array of logins to include. Users matching one of these
	 *                                                logins will be included in results. Default empty array.
	 *     @type string[]        $login__not_in       An array of logins to exclude. Users matching one of these
	 *                                                logins will not be included in results. Default empty array.
	 * }
	 */
	public function parse_query(array $query) {
		if (empty($this->query_vars) || !empty($query)) {
			$this->query_vars = $this->fill_query_vars($query);
		}
	}

	/**
	 * Return the total number of users for the current query.
	 *
	 * @since 3.1.0
	 *
	 * @return int Number of total users.
	 */
	public function get_total() {
		return $this->total_users;
	}

	/**
	 * Parse and sanitize 'orderby' keys passed to the user query.
	 *
	 * @since 4.2.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $orderby Alias for the field to order by.
	 * @return string Value to used in the ORDER clause, if `$orderby` is valid.
	 */
	protected function parse_orderby() {
		global $wpdb;

		$q       = &$this->query_vars;
		$orderby = $q['orderby'] ?: $this->default_order_by;
		if (!$orderby or 'none' == $orderby) {
			return;
		}

		$meta_query_clauses = $this->meta_query->get_clauses();

		$_orderby = '';
		if (in_array($orderby, ['login', 'nicename', 'email', 'url', 'registered'], true)) {
			$_orderby = 'user_' . $orderby;
		} elseif (in_array($orderby, ['user_login', 'user_nicename', 'user_email', 'user_url', 'user_registered'], true)) {
			$_orderby = $orderby;
		} elseif ('name' === $orderby || 'display_name' === $orderby) {
			$_orderby = 'display_name';
		} elseif ('post_count' === $orderby) {
			// @todo Avoid the JOIN.
			$where = get_posts_by_author_sql('post');
			$this->query_from .= " LEFT OUTER JOIN (
				SELECT post_author, COUNT(*) as post_count
				FROM $wpdb->posts
				$where
				GROUP BY post_author
			) p ON ({$wpdb->users}.ID = p.post_author)
			";
			$_orderby = 'post_count';
		} elseif ('ID' === $orderby || 'id' === $orderby) {
			$_orderby = 'ID';
		} elseif ('meta_value' === $orderby || $this->get('meta_key') == $orderby) {
			$_orderby = "$wpdb->usermeta.meta_value";
		} elseif ('meta_value_num' === $orderby) {
			$_orderby = "$wpdb->usermeta.meta_value+0";
		} elseif ('include' === $orderby && !empty($this->query_vars['include'])) {
			$include     = wp_parse_id_list($this->query_vars['include']);
			$include_sql = implode(',', $include);
			$_orderby    = "FIELD( $wpdb->users.ID, $include_sql )";
		} elseif ('nicename__in' === $orderby) {
			$sanitized_nicename__in = array_map('esc_sql', $this->query_vars['nicename__in']);
			$nicename__in           = implode("','", $sanitized_nicename__in);
			$_orderby               = "FIELD( user_nicename, '$nicename__in' )";
		} elseif ('login__in' === $orderby) {
			$sanitized_login__in = array_map('esc_sql', $this->query_vars['login__in']);
			$login__in           = implode("','", $sanitized_login__in);
			$_orderby            = "FIELD( user_login, '$login__in' )";
		} elseif (isset($meta_query_clauses[$orderby])) {
			$meta_clause = $meta_query_clauses[$orderby];
			$_orderby    = sprintf('CAST(%s.meta_value AS %s)', esc_sql($meta_clause['alias']), esc_sql($meta_clause['cast']));
		}

		if (!empty($_orderby)) {
			$order   = $this->parse_order($q['order']);
			$orderby = "{$_orderby} {$order}";

			$this->orderby = 'ORDER BY ' . $orderby;
		}
	}

	protected static function instantiate_item(object $item): object {
		return new WP_User($item);
	}
}
