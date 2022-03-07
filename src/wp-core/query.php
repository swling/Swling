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
