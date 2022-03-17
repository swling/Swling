<?php
/**
 * Retrieves template directory URI for current theme.
 *
 * @since 1.5.0
 *
 * @return string URI to current theme's template directory.
 */
function get_template_directory_uri() {
	return WP_THEME_URL . '/' . TEMPLATE_DIR;
}
