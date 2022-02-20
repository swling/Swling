```php
/**
	 * Parse a query string and set query type booleans.
	 *
	 * @since 1.5.0
	 * @since 4.2.0 Introduced the ability to order by specific clauses of a `$meta_query`, by passing the clause's
	 *              array key to `$orderby`.
	 * @since 4.4.0 Introduced `$post_name__in` and `$title` parameters. `$s` was updated to support excluded
	 *              search terms, by prepending a hyphen.
	 * @since 4.5.0 Removed the `$comments_popup` parameter.
	 *              Introduced the `$comment_status` and `$ping_status` parameters.
	 *              Introduced `RAND(x)` syntax for `$orderby`, which allows an integer seed value to random sorts.
	 * @since 4.6.0 Added 'post_name__in' support for `$orderby`. Introduced the `$lazy_load_term_meta` argument.
	 * @since 4.9.0 Introduced the `$comment_count` parameter.
	 * @since 5.1.0 Introduced the `$meta_compare_key` parameter.
	 * @since 5.3.0 Introduced the `$meta_type_key` parameter.
	 *
	 * @param string|array $query {
	 *     Optional. Array or string of Query parameters.
	 *
	 *     @type int             $attachment_id           Attachment post ID. Used for 'attachment' post_type.
	 *     @type int|string      $author                  Author ID, or comma-separated list of IDs.
	 *     @type string          $author_name             User 'user_nicename'.
	 *     @type int[]           $author__in              An array of author IDs to query from.
	 *     @type int[]           $author__not_in          An array of author IDs not to query from.
	 *     @type bool            $cache_results           Whether to cache post information. Default true.
	 *     @type int|string      $cat                     Category ID or comma-separated list of IDs (this or any children).
	 *     @type int[]           $category__and           An array of category IDs (AND in).
	 *     @type int[]           $category__in            An array of category IDs (OR in, no children).
	 *     @type int[]           $category__not_in        An array of category IDs (NOT in).
	 *     @type string          $category_name           Use category slug (not name, this or any children).
	 *     @type array|int       $comment_count           Filter results by comment count. Provide an integer to match
	 *                                                    comment count exactly. Provide an array with integer 'value'
	 *                                                    and 'compare' operator ('=', '!=', '>', '>=', '<', '<=' ) to
	 *                                                    compare against comment_count in a specific way.
	 *     @type string          $comment_status          Comment status.
	 *     @type int             $comments_per_page       The number of comments to return per page.
	 *                                                    Default 'comments_per_page' option.
	 *     @type array           $date_query              An associative array of WP_Date_Query arguments.
	 *                                                    See WP_Date_Query::__construct().
	 *     @type int             $day                     Day of the month. Default empty. Accepts numbers 1-31.
	 *     @type bool            $exact                   Whether to search by exact keyword. Default false.
	 *     @type string          $fields                  Post fields to query for. Accepts:
	 *                                                    - '' Returns an array of complete post objects (`WP_Post[]`).
	 *                                                    - 'ids' Returns an array of post IDs (`int[]`).
	 *                                                    - 'id=>parent' Returns an associative array of parent post IDs,
	 *                                                      keyed by post ID (`int[]`).
	 *                                                    Default ''.
	 *     @type int             $hour                    Hour of the day. Default empty. Accepts numbers 0-23.
	 *     @type int|bool        $ignore_sticky_posts     Whether to ignore sticky posts or not. Setting this to false
	 *                                                    excludes stickies from 'post__in'. Accepts 1|true, 0|false.
	 *                                                    Default false.
	 *     @type int             $m                       Combination YearMonth. Accepts any four-digit year and month
	 *                                                    numbers 1-12. Default empty.
	 *     @type string|string[] $meta_key                Meta key or keys to filter by.
	 *     @type string|string[] $meta_value              Meta value or values to filter by.
	 *     @type string          $meta_compare            MySQL operator used for comparing the meta value.
	 *                                                    See WP_Meta_Query::__construct for accepted values and default value.
	 *     @type string          $meta_compare_key        MySQL operator used for comparing the meta key.
	 *                                                    See WP_Meta_Query::__construct for accepted values and default value.
	 *     @type string          $meta_type               MySQL data type that the meta_value column will be CAST to for comparisons.
	 *                                                    See WP_Meta_Query::__construct for accepted values and default value.
	 *     @type string          $meta_type_key           MySQL data type that the meta_key column will be CAST to for comparisons.
	 *                                                    See WP_Meta_Query::__construct for accepted values and default value.
	 *     @type array           $meta_query              An associative array of WP_Meta_Query arguments.
	 *                                                    See WP_Meta_Query::__construct for accepted values.
	 *     @type int             $menu_order              The menu order of the posts.
	 *     @type int             $minute                  Minute of the hour. Default empty. Accepts numbers 0-59.
	 *     @type int             $monthnum                The two-digit month. Default empty. Accepts numbers 1-12.
	 *     @type string          $name                    Post slug.
	 *     @type bool            $nopaging                Show all posts (true) or paginate (false). Default false.
	 *     @type bool            $no_found_rows           Whether to skip counting the total rows found. Enabling can improve
	 *                                                    performance. Default false.
	 *     @type int             $offset                  The number of posts to offset before retrieval.
	 *     @type string          $order                   Designates ascending or descending order of posts. Default 'DESC'.
	 *                                                    Accepts 'ASC', 'DESC'.
	 *     @type string|array    $orderby                 Sort retrieved posts by parameter. One or more options may be passed.
	 *                                                    To use 'meta_value', or 'meta_value_num', 'meta_key=keyname' must be
	 *                                                    also be defined. To sort by a specific `$meta_query` clause, use that
	 *                                                    clause's array key. Accepts:
	 *                                                    - 'none'
	 *                                                    - 'name'
	 *                                                    - 'author'
	 *                                                    - 'date'
	 *                                                    - 'title'
	 *                                                    - 'modified'
	 *                                                    - 'menu_order'
	 *                                                    - 'parent'
	 *                                                    - 'ID'
	 *                                                    - 'rand'
	 *                                                    - 'relevance'
	 *                                                    - 'RAND(x)' (where 'x' is an integer seed value)
	 *                                                    - 'comment_count'
	 *                                                    - 'meta_value'
	 *                                                    - 'meta_value_num'
	 *                                                    - 'post__in'
	 *                                                    - 'post_name__in'
	 *                                                    - 'post_parent__in'
	 *                                                    - The array keys of `$meta_query`.
	 *                                                    Default is 'date', except when a search is being performed, when
	 *                                                    the default is 'relevance'.
	 *     @type int             $p                       Post ID.
	 *     @type int             $page                    Show the number of posts that would show up on page X of a
	 *                                                    static front page.
	 *     @type int             $paged                   The number of the current page.
	 *     @type int             $page_id                 Page ID.
	 *     @type string          $pagename                Page slug.
	 *     @type string          $perm                    Show posts if user has the appropriate capability.
	 *     @type string          $ping_status             Ping status.
	 *     @type int[]           $post__in                An array of post IDs to retrieve, sticky posts will be included.
	 *     @type int[]           $post__not_in            An array of post IDs not to retrieve. Note: a string of comma-
	 *                                                    separated IDs will NOT work.
	 *     @type string          $post_mime_type          The mime type of the post. Used for 'attachment' post_type.
	 *     @type string[]        $post_name__in           An array of post slugs that results must match.
	 *     @type int             $post_parent             Page ID to retrieve child pages for. Use 0 to only retrieve
	 *                                                    top-level pages.
	 *     @type int[]           $post_parent__in         An array containing parent page IDs to query child pages from.
	 *     @type int[]           $post_parent__not_in     An array containing parent page IDs not to query child pages from.
	 *     @type string|string[] $post_type               A post type slug (string) or array of post type slugs.
	 *                                                    Default 'any' if using 'tax_query'.
	 *     @type string|string[] $post_status             A post status (string) or array of post statuses.
	 *     @type int             $posts_per_page          The number of posts to query for. Use -1 to request all posts.
	 *     @type int             $posts_per_archive_page  The number of posts to query for by archive page. Overrides
	 *                                                    'posts_per_page' when is_archive(), or is_search() are true.
	 *     @type string          $s                       Search keyword(s). Prepending a term with a hyphen will
	 *                                                    exclude posts matching that term. Eg, 'pillow -sofa' will
	 *                                                    return posts containing 'pillow' but not 'sofa'. The
	 *                                                    character used for exclusion can be modified using the
	 *                                                    the 'wp_query_search_exclusion_prefix' filter.
	 *     @type int             $second                  Second of the minute. Default empty. Accepts numbers 0-59.
	 *     @type bool            $sentence                Whether to search by phrase. Default false.
	 *     @type bool            $suppress_filters        Whether to suppress filters. Default false.
	 *     @type string          $tag                     Tag slug. Comma-separated (either), Plus-separated (all).
	 *     @type int[]           $tag__and                An array of tag IDs (AND in).
	 *     @type int[]           $tag__in                 An array of tag IDs (OR in).
	 *     @type int[]           $tag__not_in             An array of tag IDs (NOT in).
	 *     @type int             $tag_id                  Tag id or comma-separated list of IDs.
	 *     @type string[]        $tag_slug__and           An array of tag slugs (AND in).
	 *     @type string[]        $tag_slug__in            An array of tag slugs (OR in). unless 'ignore_sticky_posts' is
	 *                                                    true. Note: a string of comma-separated IDs will NOT work.
	 *     @type array           $tax_query               An associative array of WP_Tax_Query arguments.
	 *                                                    See WP_Tax_Query->__construct().
	 *     @type string          $title                   Post title.
	 *     @type bool            $update_post_meta_cache  Whether to update the post meta cache. Default true.
	 *     @type bool            $update_post_term_cache  Whether to update the post term cache. Default true.
	 *     @type bool            $lazy_load_term_meta     Whether to lazy-load term meta. Setting to false will
	 *                                                    disable cache priming for term meta, so that each
	 *                                                    get_term_meta() call will hit the database.
	 *                                                    Defaults to the value of `$update_post_term_cache`.
	 *     @type int             $w                       The week number of the year. Default empty. Accepts numbers 0-53.
	 *     @type int             $year                    The four-digit year. Default empty. Accepts any four-digit year.
	 * }
	 */
```

```php
	// 将数组条件转为 or 查询 而非 in 查询的方法 
	private function get_type_status_sql() {
		$q    = &$this->query_vars;
		$wpdb = &$this->wpdb;

		$statuswheres = [];
		$q_status     = $q['post_status'];
		if (!is_array($q_status)) {
			$q_status = explode(',', $q_status);
		}

		foreach ($q_status as $status) {
			$statuswheres[] = "{$wpdb->posts}.post_status = '$status'";
		}

		$where_status = implode(' OR ', $statuswheres);
		if (!empty($where_status)) {
			$this->where .= " AND ($where_status)";
		}
	}
```	