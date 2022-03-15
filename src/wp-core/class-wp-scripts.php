<?php

use WP_Core\Utility\Singleton_Trait;

/**
 * Core class used to register scripts.
 *
 * @see WP_Dependencies
 */
class WP_Scripts extends WP_Dependencies {

	/**
	 * Default version string for scripts.
	 *
	 * @since 2.6.0
	 * @var string
	 */
	public $default_version;

	/**
	 * Holds a string which contains the type attribute for script tag.
	 *
	 * If the current theme does not declare HTML5 support for 'script',
	 * then it initializes as `type='text/javascript'`.
	 *
	 * @since 5.3.0
	 * @var string
	 */
	public $type_attr = " type='text/javascript'";

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

		$before_handle = $this->print_inline_script($handle, 'before', false);
		$after_handle  = $this->print_inline_script($handle, 'after', false);

		if ($before_handle) {
			$before_handle = sprintf("<script%s id='%s-js-before'>\n%s\n</script>\n", $this->type_attr, esc_attr($handle), $before_handle);
		}

		if ($after_handle) {
			$after_handle = sprintf("<script%s id='%s-js-after'>\n%s\n</script>\n", $this->type_attr, esc_attr($handle), $after_handle);
		}

		$obj = $this->registered[$handle];
		$ver = $obj->ver ? $obj->ver : $this->default_version;
		if (!empty($ver)) {
			$src = add_query_arg('ver', $ver, $obj->src);
		}

		$tag = $before_handle;
		$tag .= sprintf("<script%s src='%s' id='%s-js'></script>\n", $this->type_attr, $src, esc_attr($handle));
		$tag .= $after_handle;
		echo $tag;

		return true;
	}

	/**
	 * Adds extra code to a registered script.
	 *
	 * @param string $handle   Name of the script to add the inline script to.
	 *                         Must be lowercase.
	 * @param string $data     String containing the JavaScript to be added.
	 * @param string $position Optional. Whether to add the inline script
	 *                         before the handle or after. Default 'after'.
	 * @return bool True on success, false on failure.
	 */
	public function add_inline_script(string $handle, string $data, string $position = 'after') {
		if (!$data) {
			return false;
		}

		if ('after' !== $position) {
			$position = 'before';
		}

		$script   = (array) $this->get_data($handle, $position);
		$script[] = $data;

		return $this->add_data($handle, $position, $script);
	}

	/**
	 * Prints inline scripts registered for a specific handle.
	 *
	 * @param string $handle   Name of the script to add the inline script to.
	 *                         Must be lowercase.
	 * @param string $position Optional. Whether to add the inline script
	 *                         before the handle or after. Default 'after'.
	 * @param bool   $echo     Optional. Whether to echo the script
	 *                         instead of just returning it. Default true.
	 * @return string|false Script on success, false otherwise.
	 */
	public function print_inline_script(string $handle, string $position = 'after', bool $echo = true) {
		$output = $this->get_data($handle, $position);

		if (empty($output)) {
			return false;
		}

		$output = trim(implode("\n", $output), "\n");

		if ($echo) {
			printf("<script%s id='%s-js-%s'>\n%s\n</script>\n", $this->type_attr, esc_attr($handle), esc_attr($position), $output);
		}

		return $output;
	}

	/**
	 * Prints extra scripts of a registered script.
	 *
	 * @param string $handle The script's registered handle.
	 * @param bool   $echo   Optional. Whether to echo the extra script
	 *                       instead of just returning it. Default true.
	 * @return bool|string|void Void if no data exists, extra scripts if `$echo` is true,
	 *                          true otherwise.
	 */
	public function print_extra_script(string $handle, bool $echo = true) {
		$output = $this->get_data($handle, 'data');
		if (!$output) {
			return;
		}

		if (!$echo) {
			return $output;
		}

		printf("<script%s id='%s-js-extra'>\n", $this->type_attr, esc_attr($handle));

		// CDATA is not needed for HTML 5.
		if ($this->type_attr) {
			echo "/* <![CDATA[ */\n";
		}

		echo "$output\n";

		if ($this->type_attr) {
			echo "/* ]]> */\n";
		}

		echo "</script>\n";

		return true;
	}

	/**
	 * Processes items and dependencies for the head group.
	 *
	 * @return string[] Handles of items that have been processed.
	 */
	public function do_head_items() {
		$this->do_items(false, 0);
		return $this->done;
	}

	/**
	 * Processes items and dependencies for the footer group.
	 *
	 * @return string[] Handles of items that have been processed.
	 */
	public function do_footer_items() {
		$this->do_items(false, 1);
		return $this->done;
	}
}
