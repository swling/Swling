<?php
namespace Controller;

use WP_Query;

/**
 * 根据 uri：
 * - 指派模板文件
 * - 定义主查询：global $wp_query
 * - 解析 WP_Query 参数
 */
class Template_Dispatcher {

	private $wp_query_args = [];
	private $template_file = '';
	private $handler;
	private $param;

	public function __construct(string $handler, string $param) {
		$this->handler = $handler;
		$this->param   = $param;
		$this->parse_query();

		global $wp_query;
		if ($this->wp_query_args) {
			$wp_query = new WP_Query($this->wp_query_args);
		}

		include TEMPLATEPATH . '/' . $this->template_file;
	}

	private function parse_query() {
		$post_types = get_post_types();
		$taxonomies = get_taxonomies();

		if (!$this->handler) {
			$this->template_file = 'index.php';
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
