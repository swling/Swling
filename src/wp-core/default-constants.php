<?php

// content
define('WP_CONTENT_DIR', 'content');
define('WP_CONTENT_URL', WP_SITEURL . '/' . WP_CONTENT_DIR);

// plugin
define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/extends'); // Full path, no trailing slash.
define('WP_PLUGIN_URL', WP_CONTENT_URL . '/extends'); // Full URL, no trailing slash.

// theme
define('WP_THEME_DIR', WP_CONTENT_DIR . '/themes'); // Full URL, no trailing slash.
define('WP_THEME_URL', WP_CONTENT_URL . '/themes'); // Full URL, no trailing slash.

// current theme
define('TEMPLATEPATH', ABSPATH . WP_THEME_DIR . '/' . TEMPLATE_DIR);

/**
 * Constants for expressing human-readable data sizes in their respective number of bytes.
 *
 * @since 4.4.0
 */
define('KB_IN_BYTES', 1024);
define('MB_IN_BYTES', 1024 * KB_IN_BYTES);
define('GB_IN_BYTES', 1024 * MB_IN_BYTES);
define('TB_IN_BYTES', 1024 * GB_IN_BYTES);

/**
 * Constants for expressing human-readable intervals
 * in their respective number of seconds.
 *
 * Please note that these values are approximate and are provided for convenience.
 * For example, MONTH_IN_SECONDS wrongly assumes every month has 30 days and
 * YEAR_IN_SECONDS does not take leap years into account.
 *
 * If you need more accuracy please consider using the DateTime class (https://www.php.net/manual/en/class.datetime.php).
 *
 * @since 3.5.0
 * @since 4.4.0 Introduced `MONTH_IN_SECONDS`.
 */
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS);
define('DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS);
define('WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS);
define('MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS);
define('YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS);

// Defines cookie-related WordPress constants.
wp_cookie_constants();

/**
 * Defines cookie-related WordPress constants.
 *
 * Defines constants after multisite is loaded.
 *
 * @since 3.0.0
 */
function wp_cookie_constants() {
	/**
	 * Used to guarantee unique hash cookies.
	 *
	 * @since 1.5.0
	 */
	if (!defined('COOKIEHASH')) {
		$siteurl = WP_SITEURL ?? '';
		if ($siteurl) {
			define('COOKIEHASH', md5($siteurl));
		} else {
			define('COOKIEHASH', '');
		}
	}

	/**
	 * @since 2.0.0
	 */
	if (!defined('USER_COOKIE')) {
		define('USER_COOKIE', 'wordpressuser_' . COOKIEHASH);
	}

	/**
	 * @since 2.0.0
	 */
	if (!defined('PASS_COOKIE')) {
		define('PASS_COOKIE', 'wordpresspass_' . COOKIEHASH);
	}

	/**
	 * @since 2.5.0
	 */
	if (!defined('AUTH_COOKIE')) {
		define('AUTH_COOKIE', 'wordpress_' . COOKIEHASH);
	}

	/**
	 * @since 2.6.0
	 */
	if (!defined('SECURE_AUTH_COOKIE')) {
		define('SECURE_AUTH_COOKIE', 'wordpress_sec_' . COOKIEHASH);
	}

	/**
	 * @since 2.6.0
	 */
	if (!defined('LOGGED_IN_COOKIE')) {
		define('LOGGED_IN_COOKIE', 'wordpress_logged_in_' . COOKIEHASH);
	}

	/**
	 * @since 2.3.0
	 */
	if (!defined('TEST_COOKIE')) {
		define('TEST_COOKIE', 'wordpress_test_cookie');
	}

	/**
	 * @since 1.2.0
	 */
	if (!defined('COOKIEPATH')) {
		define('COOKIEPATH', preg_replace('|https?://[^/]+|i', '', WP_HOME . '/'));
	}

	/**
	 * @since 1.5.0
	 */
	if (!defined('SITECOOKIEPATH')) {
		define('SITECOOKIEPATH', preg_replace('|https?://[^/]+|i', '', WP_SITEURL . '/'));
	}

	/**
	 * @since 2.6.0
	 */
	if (!defined('ADMIN_COOKIE_PATH')) {
		define('ADMIN_COOKIE_PATH', SITECOOKIEPATH . 'wp-admin');
	}

	/**
	 * @since 2.6.0
	 */
	if (!defined('PLUGINS_COOKIE_PATH')) {
		define('PLUGINS_COOKIE_PATH', preg_replace('|https?://[^/]+|i', '', WP_PLUGIN_URL));
	}

	/**
	 * @since 2.0.0
	 */
	if (!defined('COOKIE_DOMAIN')) {
		define('COOKIE_DOMAIN', false);
	}

	if (!defined('RECOVERY_MODE_COOKIE')) {
		/**
		 * @since 5.2.0
		 */
		define('RECOVERY_MODE_COOKIE', 'wordpress_rec_' . COOKIEHASH);
	}
}
