<?php
namespace Controller;

use Controller\Dispatcher_API;

/**
 * ## API 路由
 * - Api 部分参考 wnd-frontend 插件，仅支持 POST 及 GET 请求
 * - Template渲染 URL 路由 仅支持 GET 请求
 */
class Route {

	private $is_api_request = false;
	private $url_info       = [];

	/**
	 * 移除安装目录并按预定 URL 规则解析控制器及参数
	 * - API 请求：移除前缀路径后作为控制类名称，请求参数即为 $_POST/$_GET
	 * - 常规请求：将路径切割为：控制类/请求参数
	 */
	public function __construct() {
		$request  = str_ireplace('/' . DIR_NAME . '/', '', $_SERVER['REQUEST_URI']);
		$url_info = parse_url($request);
		$path     = $url_info['path'] ?? '';

		$api_prefix = Dispatcher_API::get_api_prefix();
		if (str_starts_with($path, $api_prefix)) {
			$this->is_api_request = true;
		}

		$this->url_info = $url_info;
	}

	public function dispatch() {
		if ($this->is_api_request) {
			new Dispatcher_API($this->url_info);
		} else {
			new Dispatcher_Template($this->url_info);
		}
	}
}
