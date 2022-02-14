<?php

ini_set('display_errors', 'On');

// 定义插件文件路径
define('SWL_PATH', __DIR__);

// 安装目录
define('SWL_DIR_NAME', basename(__DIR__));

// 自动加载器
require SWL_PATH . DIRECTORY_SEPARATOR . 'autoloader.php';
require SWL_PATH . DIRECTORY_SEPARATOR . '/src/wp-core/repair.php';
require SWL_PATH . DIRECTORY_SEPARATOR . '/src/wp-core/class-wpdb.php';
require SWL_PATH . DIRECTORY_SEPARATOR . '/src/wp-core/plugin.php';

// ** MySQL 设置 - 具体信息来自您正在使用的主机 ** //
define('DB_NAME', 'test');
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
// define('SAVEQUERIES', true);

// 实例化数据库连接
$wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
$wpdb->set_prefix($table_prefix);

// 创建数据库依赖 wpdb
require SWL_PATH . DIRECTORY_SEPARATOR . '/src/wp-core/admin/schema.php';
require SWL_PATH . DIRECTORY_SEPARATOR . '/src/wp-core/admin/dbDelta.php';
if (isset($_GET['install'])) {
	dbDelta('all');
}
