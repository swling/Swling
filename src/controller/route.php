<?php
namespace Controller;

/**
 * ## API 路由
 * Api 部分参考 wnd-frontend 插件，仅支持 POST 及 GET 请求
 * - /{{api_prefix}}/
 * - /{{api_prefix}}/action/{{action}}
 * ……
 *
 * ### 主题拓展操作节点
 * - /{{api_prefix}}/theme/action/{{action}}
 * ……
 *
 * ### 插件拓展操作节点
 * - /{{api_prefix}}/extend/action/{{action}}
 * ……
 *
 * ## 渲染 URL 路由 仅支持 GET 请求
 * - /user/ 			                用户
 * - /console/			                控制台
 * - /{{post_type}}/{{id_or_slug}}	    正文
 * - /{{taxonomy}}/{{id_or_slug}}		分类
 */
class Route {

	private $controller     = '';
	private $query          = '';
	private $is_api_request = false;

	/**
	 * 移除安装目录并按预定 URL 规则解析控制器及参数
	 * - API 请求：移除前缀路径后作为控制类名称，请求参数即为 $_POST/$_GET
	 * - 常规请求：将路径切割为：控制类/请求参数
	 */
	public function __construct() {
		$request              = str_replace('/' . DIR_NAME . '/', '', $_SERVER['REQUEST_URI']);
		$request_arr          = parse_url($request);
		$path                 = $request_arr['path'] ?? '';
		$this->query          = $request_arr['query'] ?? '';
		$this->api_prefix     = static::get_api_prefix();
		$this->is_api_request = false;

		if (str_starts_with($path, $this->api_prefix)) {
			$this->is_api_request = true;
			$controller           = str_replace($this->api_prefix . '/', '', $path);
		} else {
			$url_info   = explode('/', $path, 2);
			$controller = $url_info[0];
		}

		// 将 URI 路径转为命名空间
		$this->controller = str_replace('/', '\\', $controller);
	}

	public function render() {
		if (!$this->controller) {
			return;
		}

		if (class_exists($this->controller)) {
			new $this->controller($this->query);
		} else {
			http_response_code(404);
		}
	}

	public static function get_api_prefix(): string {
		return 'wp-json';
	}
}
