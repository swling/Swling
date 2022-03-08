<?php
namespace Controller;

/**
 * 根据 uri：
 * - 解析对应 API 类名称
 * - 如果对应类存在，执行实例化
 * - 如果对应类不存在，返回错误 json 信息
 *
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
 */
class Dispatcher_API {

	private $handler;
	private $query;

	public function __construct(array $url_info) {
		$path          = $url_info['path'];
		$handler       = str_replace(static::get_api_prefix() . '/', '', $path);
		$this->handler = $handler ? str_replace('/', '\\', $handler) : 'index';
		$this->query   = $url_info['query'] ?? '';
		$this->render();
	}

	private function render() {
		if ($this->handler and class_exists($this->handler)) {
			new $this->handler($this->query);
		} else {
			header('Content-Type: application/json; charset=utf-8', false, 404);
			echo json_encode(
				[
					'status' => 0,
					'msg'    => 'No route was found matching the URL and request method.',
					'data'   => ['code' => 404],
				]
			);
		}
	}

	public static function get_api_prefix(): string {
		return 'wp-json';
	}
}
