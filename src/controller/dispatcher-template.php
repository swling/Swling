<?php
namespace Controller;

use WP_Query;

/**
 * 根据 uri：
 * - 指派模板文件
 * - 定义主查询：global $wp_query、global $wp_the_query;
 * - 解析 WP_Query 参数
 *
 * ## 渲染 URL 路由 仅支持 GET 请求
 * - /user 				                用户
 * - /console			                控制台
 * - /{{post_type}}/{{id_or_slug}}	    正文
 * - /{{post_type}}					    post type archive
 * - /{{taxonomy}}						taxonomy term archive
 */
class Dispatcher_Template {

	private $wp_query_args = [];
	private $template_file = '';
	private $is_home       = true;
	private $handler;
	private $param;

	public function __construct(array $url_info) {
		$path          = $url_info['path'];
		$path_info     = explode('/', $path);
		$this->handler = $path_info[0];
		$this->param   = $path_info[1] ?? '';

		$this->load();
	}

	private function load() {
		$this->parse_query();

		global $wp_query;
		if ($this->wp_query_args or $this->is_home) {
			$wp_query = new WP_Query($this->wp_query_args);
			$wp_query->query();
		}

		// 复制备份（在修改主查询后，可通过此备份必要时还原）
		global $wp_the_query;
		$wp_the_query = $wp_query;

		// load template file
		include TEMPLATEPATH . '/' . $this->template_file;
	}

	private function parse_query() {
		$post_types = get_post_types();
		$taxonomies = get_taxonomies();

		if (!$this->handler) {
			$this->template_file = 'index.php';
			$this->is_home       = true;
			return;
		}

		if (in_array($this->handler, $post_types)) {
			$this->dispatch_single();
			return;
		}

		if (in_array($this->handler, $taxonomies)) {
			$this->dispatch_tax();
			return;
		}

		$this->template_file = '404.php';
	}

	private function dispatch_single() {
		$this->template_file              = 'single.php';
		$this->wp_query_args['post_type'] = $this->handler;

		if (is_numeric($this->param)) {
			$this->wp_query_args['ID'] = $this->param;
		} else {
			$this->wp_query_args['post_name'] = $this->param;
		}
	}

	private function dispatch_tax() {
		$tax_query['taxonomy'] = $this->handler;
		$tax_query['terms']    = urldecode($this->param);
		$tax_query['field']    = 'slug';

		$this->wp_query_args['tax_query'][] = $tax_query;
		$this->template_file                = 'archive.php';
	}
}
