<?php

use WP_Core\Utility\Singleton_Trait;

/**
 * Core class used to register styles.
 *
 * @see WP_Dependencies
 */
class WP_Styles extends WP_Dependencies {

	/**
	 * Default version string for scripts.
	 *
	 * @since 2.6.0
	 * @var string
	 */
	public $default_version;

	/**
	 * Holds a string which contains the type attribute for style tag.
	 *
	 * If the current theme does not declare HTML5 support for 'style',
	 * then it initializes as `type='text/css'`.
	 *
	 * @since 5.3.0
	 * @var string
	 */
	public $type_attr = " type='text/css'";

	/**
	 * Singleton model
	 * use ::get_instance() to instance
	 */
	use Singleton_Trait;

	/**
	 * Processes a dependency.
	 *
	 * @param string    $handle Name of the item. Should be unique.
	 * @param int       $group  Optional. Group level: level (int), no group (false).
	 *                          Default false.
	 * @return bool True on success, false if not set.
	 */
	public function do_item(string $handle, int $group = 0): bool {
		if (!parent::do_item($handle, $group)) {
			return false;
		}

		$obj = $this->registered[$handle];
		$ver = $obj->ver ? $obj->ver : $this->default_version;
		$src = $ver ? add_query_arg('ver', $ver, $obj->src) : $obj->src;

		$rel = isset($obj->extra['alt']) && $obj->extra['alt'] ? 'alternate stylesheet' : 'stylesheet';

		$tag = sprintf(
			"<link rel='%s' id='%s-css' href='%s'%s media='all' />\n",
			$rel,
			$handle,
			$src,
			$this->type_attr
		);
		echo $tag;

		$this->print_inline_style($handle);

		return true;
	}

	/**
	 * Adds extra CSS styles to a registered stylesheet.
	 *
	 * @since 3.3.0
	 *
	 * @param string $handle The style's registered handle.
	 * @param string $code   String containing the CSS styles to be added.
	 * @return bool True on success, false on failure.
	 */
	public function add_inline_style($handle, $code) {
		if (!$code) {
			return false;
		}

		$after = $this->get_data($handle, 'after');
		if (!$after) {
			$after = [];
		}

		$after[] = $code;

		return $this->add_data($handle, 'after', $after);
	}

	/**
	 * Prints extra CSS styles of a registered stylesheet.
	 *
	 * @since 3.3.0
	 *
	 * @param string $handle The style's registered handle.
	 * @param bool   $echo   Optional. Whether to echo the inline style
	 *                       instead of just returning it. Default true.
	 * @return string|bool False if no data exists, inline styles if `$echo` is true,
	 *                     true otherwise.
	 */
	public function print_inline_style($handle, $echo = true) {
		$output = $this->get_data($handle, 'after');

		if (empty($output)) {
			return false;
		}

		$output = implode("\n", $output);

		if (!$echo) {
			return $output;
		}

		printf(
			"<style id='%s-inline-css'%s>\n%s\n</style>\n",
			esc_attr($handle),
			$this->type_attr,
			$output
		);

		return true;
	}
}
