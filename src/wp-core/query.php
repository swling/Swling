<?php
/**
 * WordPress Query API
 *
 * The query API attempts to get which part of WordPress the user is on. It
 * also provides functionality for getting URL query information.
 *
 * @link https://developer.wordpress.org/themes/basics/the-loop/ More information on The Loop.
 *
 * @package WordPress
 * @subpackage Query
 */

/**
 * Retrieves the value of a query variable in the WP_Query class.
 *
 * @since 1.5.0
 * @since 3.9.0 The `$default` argument was introduced.
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param string $var       The variable key to retrieve.
 * @param mixed  $default   Optional. Value to return if the query variable is not set. Default empty.
 * @return mixed Contents of the query variable.
 */
function get_query_var($var, $default = '') {
	global $wp_query;
	return $wp_query->get($var, $default);
}

/**
 * Retrieves the currently queried object.
 *
 * Wrapper for WP_Query::get_queried_object().
 *
 * @since 3.1.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @return WP_Term|WP_Post_Type|WP_Post|WP_User|null The queried object.
 */
function get_queried_object() {
	global $wp_query;
	return $wp_query->get_queried_object();
}

/**
 * Retrieves the ID of the currently queried object.
 *
 * Wrapper for WP_Query::get_queried_object_id().
 *
 * @since 3.1.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @return int ID of the queried object.
 */
function get_queried_object_id() {
	global $wp_query;
	return $wp_query->get_queried_object_id();
}

/**
 * Sets the value of a query variable in the WP_Query class.
 *
 * @since 2.2.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param string $var   Query variable key.
 * @param mixed  $value Query variable value.
 */
function set_query_var($var, $value) {
	global $wp_query;
	$wp_query->set($var, $value);
}

/*
 * The Loop. Post loop control.
 */

/**
 * Determines whether current WordPress query has posts to loop over.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @return bool True if posts are available, false if end of the loop.
 */
function have_posts() {
	global $wp_query;
	return $wp_query->have_posts();
}

/**
 * Determines whether the caller is in the Loop.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 2.0.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @return bool True if caller is within loop, false if loop hasn't started or ended.
 */
function in_the_loop() {
	global $wp_query;
	return $wp_query->in_the_loop;
}

/**
 * Rewind the loop posts.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 */
function rewind_posts() {
	global $wp_query;
	$wp_query->rewind_posts();
}

/**
 * Iterate the post index in the loop.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 */
function the_post() {
	global $wp_query;
	$wp_query->the_post();
}

/**
 * Destroys the previous query and sets up a new query.
 *
 * This should be used after query_posts() and before another query_posts().
 * This will remove obscure bugs that occur when the previous WP_Query object
 * is not destroyed properly before another is set up.
 *
 * @since 2.3.0
 *
 * @global WP_Query $wp_query     WordPress Query object.
 * @global WP_Query $wp_the_query Copy of the global WP_Query instance created during wp_reset_query().
 */
function wp_reset_query() {
	$GLOBALS['wp_query'] = $GLOBALS['wp_the_query'];
	wp_reset_postdata();
}

/**
 * After looping through a separate query, this function restores
 * the $post global to the current post in the main query.
 *
 * @since 3.0.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 */
function wp_reset_postdata() {
	global $wp_query;

	if (isset($wp_query)) {
		$wp_query->reset_postdata();
	}
}

/**
 * Set up global post data.
 *
 * @since 1.5.0
 * @since 4.4.0 Added the ability to pass a post ID to `$post`.
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param WP_Post|object|int $post WP_Post instance or Post ID/object.
 * @return bool True when finished.
 */
function setup_postdata($post) {
	global $wp_query;

	if (!empty($wp_query) && $wp_query instanceof WP_Query) {
		return $wp_query->setup_postdata($post);
	}

	return false;
}

/**
 * Generates post data.
 *
 * @since 5.2.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param WP_Post|object|int $post WP_Post instance or Post ID/object.
 * @return array|false Elements of post, or false on failure.
 */
function generate_postdata($post) {
	global $wp_query;

	if (!empty($wp_query) && $wp_query instanceof WP_Query) {
		return $wp_query->generate_postdata($post);
	}

	return false;
}

/*
 * Query type checks.
 */

/**
 * Determines whether the query is the main query.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 3.3.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @return bool Whether the query is the main query.
 */
function is_main_query() {
	global $wp_query;

	if ('pre_get_posts' === current_filter()) {
		_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: 1: pre_get_posts, 2: WP_Query->is_main_query(), 3: is_main_query(), 4: Documentation URL. */
				__('In %1$s, use the %2$s method, not the %3$s function. See %4$s.'),
				'<code>pre_get_posts</code>',
				'<code>WP_Query->is_main_query()</code>',
				'<code>is_main_query()</code>',
				__('https://developer.wordpress.org/reference/functions/is_main_query/')
			),
			'3.7.0'
		);
	}

	return $wp_query->is_main_query();
}

/**
 * Determines whether the query is for the blog homepage.
 *
 * The blog homepage is the page that shows the time-based blog content of the site.
 *
 * is_home() is dependent on the site's "Front page displays" Reading Settings 'show_on_front'
 * and 'page_for_posts'.
 *
 * If a static page is set for the front page of the site, this function will return true only
 * on the page you set as the "Posts page".
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @see is_front_page()
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @return bool Whether the query is for the blog homepage.
 */
function is_home() {
	global $wp_query;

	if (!isset($wp_query)) {
		_doing_it_wrong(__FUNCTION__, __('Conditional query tags do not work before the query is run. Before then, they always return false.'), '3.1.0');
		return false;
	}

	return $wp_query->is_home();
}

/**
 * Determines whether the query is for an existing single page.
 *
 * If the $page parameter is specified, this function will additionally
 * check if the query is for one of the pages specified.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @see is_single()
 * @see is_singular()
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param int|string|int[]|string[] $page Optional. Page ID, title, slug, or array of such
 *                                        to check against. Default empty.
 * @return bool Whether the query is for an existing single page.
 */
function is_page($page = '') {
	global $wp_query;

	if (!isset($wp_query)) {
		_doing_it_wrong(__FUNCTION__, __('Conditional query tags do not work before the query is run. Before then, they always return false.'), '3.1.0');
		return false;
	}

	return $wp_query->is_page($page);
}

/**
 * Determines whether the query is for a paged result and not for the first page.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @return bool Whether the query is for a paged result.
 */
function is_paged() {
	global $wp_query;

	if (!isset($wp_query)) {
		_doing_it_wrong(__FUNCTION__, __('Conditional query tags do not work before the query is run. Before then, they always return false.'), '3.1.0');
		return false;
	}

	return $wp_query->is_paged();
}

/**
 * Determines whether the query is for a post or page preview.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 2.0.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @return bool Whether the query is for a post or page preview.
 */
function is_preview() {
	global $wp_query;

	if (!isset($wp_query)) {
		_doing_it_wrong(__FUNCTION__, __('Conditional query tags do not work before the query is run. Before then, they always return false.'), '3.1.0');
		return false;
	}

	return $wp_query->is_preview();
}

/**
 * Determines whether the query is for a search.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @return bool Whether the query is for a search.
 */
function is_search() {
	global $wp_query;

	if (!isset($wp_query)) {
		_doing_it_wrong(__FUNCTION__, __('Conditional query tags do not work before the query is run. Before then, they always return false.'), '3.1.0');
		return false;
	}

	return $wp_query->is_search();
}

/**
 * Determines whether the query is for an existing custom taxonomy archive page.
 *
 * If the $taxonomy parameter is specified, this function will additionally
 * check if the query is for that specific $taxonomy.
 *
 * If the $term parameter is specified in addition to the $taxonomy parameter,
 * this function will additionally check if the query is for one of the terms
 * specified.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 2.5.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param string|string[]           $taxonomy Optional. Taxonomy slug or slugs to check against.
 *                                            Default empty.
 * @param int|string|int[]|string[] $term     Optional. Term ID, name, slug, or array of such
 *                                            to check against. Default empty.
 * @return bool Whether the query is for an existing custom taxonomy archive page.
 *              True for custom taxonomy archive pages, false for built-in taxonomies
 *              (category and tag archives).
 */
function is_tax($taxonomy = '', $term = '') {
	global $wp_query;

	if (!isset($wp_query)) {
		_doing_it_wrong(__FUNCTION__, __('Conditional query tags do not work before the query is run. Before then, they always return false.'), '3.1.0');
		return false;
	}

	return $wp_query->is_tax($taxonomy, $term);
}

/**
 * Determines whether the query is for an existing author archive page.
 *
 * If the $author parameter is specified, this function will additionally
 * check if the query is for one of the authors specified.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param int|string|int[]|string[] $author Optional. User ID, nickname, nicename, or array of such
 *                                          to check against. Default empty.
 * @return bool Whether the query is for an existing author archive page.
 */
function is_author($author = '') {
	global $wp_query;

	if (!isset($wp_query)) {
		_doing_it_wrong(__FUNCTION__, __('Conditional query tags do not work before the query is run. Before then, they always return false.'), '3.1.0');
		return false;
	}

	return $wp_query->is_author($author);
}

/**
 * Determines whether the query is for an existing archive page.
 *
 * Archive pages include category, tag, author, date, custom post type,
 * and custom taxonomy based archives.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @see is_category()
 * @see is_tag()
 * @see is_author()
 * @see is_date()
 * @see is_post_type_archive()
 * @see is_tax()
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @return bool Whether the query is for an existing archive page.
 */
function is_archive() {
	global $wp_query;

	if (!isset($wp_query)) {
		_doing_it_wrong(__FUNCTION__, __('Conditional query tags do not work before the query is run. Before then, they always return false.'), '3.1.0');
		return false;
	}

	return $wp_query->is_archive();
}

/**
 * Determines whether the query is for an existing post type archive page.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 3.1.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param string|string[] $post_types Optional. Post type or array of posts types
 *                                    to check against. Default empty.
 * @return bool Whether the query is for an existing post type archive page.
 */
function is_post_type_archive($post_types = '') {
	global $wp_query;

	if (!isset($wp_query)) {
		_doing_it_wrong(__FUNCTION__, __('Conditional query tags do not work before the query is run. Before then, they always return false.'), '3.1.0');
		return false;
	}

	return $wp_query->is_post_type_archive($post_types);
}

/**
 * Determines whether the query is for an existing single post.
 *
 * Works for any post type, except attachments and pages
 *
 * If the $post parameter is specified, this function will additionally
 * check if the query is for one of the Posts specified.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @see is_page()
 * @see is_singular()
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param int|string|int[]|string[] $post Optional. Post ID, title, slug, or array of such
 *                                        to check against. Default empty.
 * @return bool Whether the query is for an existing single post.
 */
function is_single($post = '') {
	global $wp_query;

	if (!isset($wp_query)) {
		_doing_it_wrong(__FUNCTION__, __('Conditional query tags do not work before the query is run. Before then, they always return false.'), '3.1.0');
		return false;
	}

	return $wp_query->is_single($post);
}

/**
 * Determines whether the query is for an existing single post of any post type
 * (post, attachment, page, custom post types).
 *
 * If the $post_types parameter is specified, this function will additionally
 * check if the query is for one of the Posts Types specified.
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @see is_page()
 * @see is_single()
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @param string|string[] $post_types Optional. Post type or array of post types
 *                                    to check against. Default empty.
 * @return bool Whether the query is for an existing single post
 *              or any of the given post types.
 */
function is_singular($post_types = '') {
	global $wp_query;

	if (!isset($wp_query)) {
		_doing_it_wrong(__FUNCTION__, __('Conditional query tags do not work before the query is run. Before then, they always return false.'), '3.1.0');
		return false;
	}

	return $wp_query->is_singular($post_types);
}

/**
 * Determines whether the query has resulted in a 404 (returns no results).
 *
 * For more information on this and similar theme functions, check out
 * the {@link https://developer.wordpress.org/themes/basics/conditional-tags/
 * Conditional Tags} article in the Theme Developer Handbook.
 *
 * @since 1.5.0
 *
 * @global WP_Query $wp_query WordPress Query object.
 *
 * @return bool Whether the query is a 404 error.
 */
function is_404() {
	global $wp_query;

	if (!isset($wp_query)) {
		_doing_it_wrong(__FUNCTION__, __('Conditional query tags do not work before the query is run. Before then, they always return false.'), '3.1.0');
		return false;
	}

	return $wp_query->is_404();
}
