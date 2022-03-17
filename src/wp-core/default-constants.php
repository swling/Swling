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
