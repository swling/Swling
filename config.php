<?php

ini_set('display_errors', 'On');

// 定义插件文件路径
define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);

// 安装目录
define('DIR_NAME', basename(__DIR__));

define('WPINC', 'src/wp-core');
define('WP_CONTENT_DIR', 'content');

// 自动加载器
require ABSPATH . 'autoloader.php';

// init
require ABSPATH . WPINC . '/load.php';
require ABSPATH . WPINC . '/repair.php';
require ABSPATH . WPINC . '/functions.php';
// require ABSPATH . WPINC . '/formatting.php';
require ABSPATH . WPINC . '/class-wpdb.php';
require ABSPATH . WPINC . '/plugin.php';
require ABSPATH . WPINC . '/taxonomy.php';
require ABSPATH . WPINC . '/post.php';

// 计时
timer_start();

// ** MySQL 设置 - 具体信息来自您正在使用的主机 ** //
// define('DB_NAME', 'sanks_wndwp');
define('DB_NAME', 'wordpress_dev');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
define('DB_CHARSET', 'utf8mb4');

/**
 * WordPress数据表前缀。
 *
 * 如果您有在同一数据库内安装多个WordPress的需求，请为每个WordPress设置
 * 不同的数据表前缀。前缀名只能为数字、字母加下划线。
 */
$table_prefix = 'wp_';

/**
 * 数据库整理类型。如不确定请勿更改
 */
define('DB_COLLATE', '');

// Debug
define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', null);
// define('WP_DEBUG_LOG', true);
define('SAVEQUERIES', true);

// 实例化数据库连接
$wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
$wpdb->set_prefix($table_prefix);

// 创建数据库依赖 wpdb
require ABSPATH . 'src/wp-core/admin/schema.php';
require ABSPATH . 'src/wp-core/admin/dbDelta.php';
if (isset($_GET['install'])) {
	dbDelta('all');
}

// Start the WordPress object cache, or an external object cache if the drop-in is present.
wp_start_object_cache();

// Taxonomy
create_initial_taxonomies();

// Post Type
create_initial_post_types();
