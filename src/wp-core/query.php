<?php

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
