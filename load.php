<?php
// 自动加载器
require ABSPATH . 'autoloader.php';

// init
require ABSPATH . WPINC . '/default-constants.php';
require ABSPATH . WPINC . '/load.php';
require ABSPATH . WPINC . '/repair.php';
require ABSPATH . WPINC . '/functions.php';
require ABSPATH . WPINC . '/class-wpdb.php';
require ABSPATH . WPINC . '/plugin.php';
require ABSPATH . WPINC . '/theme.php';
require ABSPATH . WPINC . '/default-hook.php';
require ABSPATH . WPINC . '/query.php';

require ABSPATH . WPINC . '/formatting.php';
require ABSPATH . WPINC . '/option.php';
require ABSPATH . WPINC . '/meta.php';
require ABSPATH . WPINC . '/taxonomy.php';
require ABSPATH . WPINC . '/user.php';
require ABSPATH . WPINC . '/post.php';
require ABSPATH . WPINC . '/term.php';
require ABSPATH . WPINC . '/comment.php';

require ABSPATH . WPINC . '/template.php';
require ABSPATH . WPINC . '/script.php';
require ABSPATH . WPINC . '/styles.php';
require ABSPATH . WPINC . '/link-template.php';

// pluggable
require ABSPATH . WPINC . '/pluggable.php';

// 计时
timer_start();

// shutdown
register_shutdown_function('shutdown_action_hook');

// 实例化数据库连接
$wpdb = new wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
$wpdb->set_prefix($table_prefix);

// 创建数据库依赖 wpdb
if (isset($_GET['install'])) {
	require ABSPATH . 'src/wp-core/admin/schema.php';
	require ABSPATH . 'src/wp-core/admin/dbDelta.php';

	dbDelta('all');
}

// Start the WordPress object cache, or an external object cache if the drop-in is present.
wp_start_object_cache();

// Taxonomy
create_initial_taxonomies();

// Post Type and Status
create_initial_post_types();

// Core Frontend（Wnd Frontend）
require WND_PATH . '/function/inc-general.php'; //通用函数定义
require WND_PATH . '/function/inc-meta.php'; //数组形式储存 meta、option
require WND_PATH . '/function/inc-post.php'; //post相关自定义函数
require WND_PATH . '/function/inc-user.php'; //user相关自定义函数
require WND_PATH . '/function/inc-media.php'; //媒体文件处理函数
require WND_PATH . '/function/inc-finance.php'; //财务
require WND_PATH . '/function/tpl-general.php'; //通用模板

// current theme functions.php
if (file_exists(TEMPLATEPATH . '/functions.php')) {
	require TEMPLATEPATH . '/functions.php';
}
