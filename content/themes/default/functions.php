<?php

/**
 * 加载静态资源
 * @since 初始化
 */
// 版本
define('WND_VER', '0.9.58.2');

// 定义插件网址路径
define('WND_URL', 'http://code.com/');

add_action('wp_enqueue_scripts', 'wnd_enqueue_scripts');
function wnd_enqueue_scripts($hook_suffix = '') {

	wp_enqueue_style('style', get_template_directory_uri() . '/style.css', [], 0.1);
	// wp_enqueue_script('vue', '//cdn.staticfile.org/vue/2.6.14/vue.min.js', [], 0.5);
	// wp_localize_script('vue', 'wp', ['v' => 0.01, 'time' => time()]);

	// 公共脚本及样式库可选本地或 jsdeliver
	$static_host = 'local';
	$static_path = WND_URL . 'static/';
	if (!$static_host or 'local' == $static_host) {
		wp_enqueue_style('bulma', $static_path . 'css/bulma.min.css', [], WND_VER);
		wp_enqueue_style('font-awesome', $static_path . 'css/font-awesome-all.min.css', [], WND_VER);
		wp_enqueue_script('axios', $static_path . 'js/lib/axios.min.js', [], WND_VER);
		wp_enqueue_script('vue', $static_path . 'js/lib/vue.global.prod.js', [], WND_VER);
	} elseif ('jsdeliver' == $static_host) {
		wp_enqueue_style('bulma', '//lf6-cdn-tos.bytecdntp.com/cdn/expire-1-M/bulma/0.9.3/css/bulma.min.css', [], '');
		wp_enqueue_style('font-awesome', '//lf9-cdn-tos.bytecdntp.com/cdn/expire-1-M/font-awesome/5.15.4/css/all.min.css', [], '');
		wp_enqueue_script('axios', '//lf6-cdn-tos.bytecdntp.com/cdn/expire-1-M/axios/0.26.0/axios.min.js', [], '');
		wp_enqueue_script('vue', '//lf9-cdn-tos.bytecdntp.com/cdn/expire-1-M/vue/3.2.31/vue.global.prod.js', [], '');
	}
	wp_enqueue_script('wnd-main', $static_path . 'js/main.min.js', ['vue', 'axios'], WND_VER);
	if (is_singular()) {
		wp_enqueue_script('wnd-comment', $static_path . 'js/comment.min.js', ['axios', 'comment-reply'], WND_VER);
	}

	// api 及语言本地化
	$wnd_data = [
		'rest_url'     => get_rest_url(),
		// 'rest_nonce'         => wp_create_nonce('wp_rest'),
		// 'disable_rest_nonce' => wnd_get_config('disable_rest_nonce'),
		'module_api'   => 'module',
		'action_api'   => 'action',
		'posts_api'    => 'posts',
		'users_api'    => 'users',
		'query_api'    => 'query',
		'endpoint_api' => 'endpoint',
		'comment'      => [
			'api'   => 'comment',
			'order' => get_option('comment_order'),
			// 'form_pos' => wnd_get_config('comment_form_pos') ?: 'top',
		],
		// 'fin_types'          => json_encode(Wnd_Init::get_fin_types()),
		// 'is_admin'           => is_admin(),
		// 'lang'               => $_GET[WND_LANG_KEY] ?? false,
		// 'ver'                => WND_VER,
		'msg'          => [
			'required'            => __('必填项为空', 'wnd'),
			'submit_successfully' => __('提交成功', 'wnd'),
			'submit_failed'       => __('提交失败', 'wnd'),
			'upload_successfully' => __('上传成功', 'wnd'),
			'upload_failed'       => __('上传失败', 'wnd'),
			'send_successfully'   => __('发送成功', 'wnd'),
			'send_failed'         => __('发送失败', 'wnd'),
			'confirm'             => __('确定'),
			'deleted'             => __('已删除', 'wnd'),
			'system_error'        => __('系统错误', 'wnd'),
			'waiting'             => __('请稍后', 'wnd'),
			'downloading'         => __('下载中', 'wnd'),
			'try_again'           => __('再试一次', 'wnd'),
			'view'                => __('查看', 'wnd'),
		],
	];
	wp_localize_script('wnd-main', 'wnd', $wnd_data);
}
