<?php
namespace Controller;

use Exception;

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
 * - /{{api_prefix}}/extend/{plugin_slug}/action/{{action}}
 * ……
 */
class Dispatcher_API {

	private $handler;
	private $query;
	private $route;

	/**
	 * 集中定义 API
	 * - 为统一前端提交行为，本插件约定，所有 Route 仅支持单一 Method 请求方式
	 * - 'route_rule' 为本插件自定义参数，用于设定对应路由的匹配规则
	 */
	public static $routes = [
		'module'   => [
			'methods'             => 'GET',
			'callback'            => __CLASS__ . '::handle_module',
			'permission_callback' => '__return_true',
			'route_rule'          => '(?P<module>(.*))',
		],
		'action'   => [
			'methods'             => 'POST',
			'callback'            => __CLASS__ . '::handle_action',
			'permission_callback' => '__return_true',
			'route_rule'          => '(?P<action>(.*))',
		],
		'query'    => [
			'methods'             => 'GET',
			'callback'            => __CLASS__ . '::handle_query',
			'permission_callback' => '__return_true',
			'route_rule'          => '(?P<query>(.*))',
		],
		'endpoint' => [
			'methods'             => ['GET', 'POST'],
			'callback'            => __CLASS__ . '::handle_endpoint',
			'permission_callback' => '__return_true',
			'route_rule'          => '(?P<endpoint>(.*))',
		],
		'posts'    => [
			'methods'             => 'GET',
			'callback'            => __CLASS__ . '::filter_posts',
			'permission_callback' => '__return_true',
			'route_rule'          => false,
		],
		'users'    => [
			'methods'             => 'GET',
			'callback'            => __CLASS__ . '::filter_users',
			'permission_callback' => '__return_true',
			'route_rule'          => false,
		],
		'comment'  =>
		[
			'methods'             => ['POST', 'GET'],
			'callback'            => __CLASS__ . '::add_comment',
			'permission_callback' => '__return_true',
			'route_rule'          => false,
		],
	];

	public function __construct(array $url_info) {
		// define  REST_REQUEST
		define('REST_REQUEST', true);

		$path = $url_info['path'];

		$handler   = str_replace(static::get_api_prefix() . '/', '', $path);
		$path_info = explode('/', $handler);
		$_route    = strtolower($path_info[0]);

		/**
		 * - 主题节点：theme/action/{{action}}
		 * - 插件节点：extend/{plugin_slug}/action/{{action}}
		 * - 核心节点：action/{{action}}
		 */
		if ('theme' == $_route) {
			$this->route = $path_info[1] ?? '';
		} elseif ('extend' == $_route) {
			$this->route = $path_info[2] ?? '';
		} else {
			$this->route = $path_info[0];
		}

		$this->handler = $handler ? str_replace('/', '\\', $handler) : 'index';
		$this->query   = $url_info['query'] ?? '';

		$this->render();
	}

	private function render() {
		$route_method = 'handle_' . $this->route;

		if ($this->handler and method_exists(__CLASS__, $route_method)) {

			/**
			 * 临时性代码：兼容插件命名空间
			 * @since 2022.07.06
			 */
			if (!class_exists($this->handler)) {
				$this->handler = 'Wnd\\' . $this->handler;
			}

			$response = static::$route_method($this->handler);
			if ($response) {
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode($response);
			}

			exit;
		}

		header('Content-Type: application/json; charset=utf-8', false, 404);
		echo json_encode(
			[
				'status' => 0,
				'msg'    => 'No route was found matching the URL and request method.',
				'data'   => ['code' => 404],
			]
		);
	}

	public static function get_api_prefix(): string {
		return 'wp-json';
	}

	/**
	 * UI 响应
	 * @since 2019.04.07
	 *
	 * @param $request
	 */
	protected static function handle_module($class): array{
		if (!class_exists($class)) {
			return ['status' => 0, 'msg' => __('无效的UI', 'wnd') . ':' . $class];
		}

		try {
			$module = new $class();
			return ['status' => 1, 'data' => $module->get_structure(), 'time' => timer_stop()];
		} catch (Exception $e) {
			return ['status' => $e->getCode(), 'msg' => $e->getMessage()];
		}
	}

	/**
	 * 获取 json data
	 * @since 2020.04.24
	 *
	 * @param $request
	 */
	protected static function handle_query($class): array{
		if (!class_exists($class)) {
			return ['status' => 0, 'msg' => __('无效的Query', 'wnd') . ':' . $class];
		}

		try {
			return ['status' => 1, 'msg' => '', 'data' => $class::get(), 'time' => timer_stop()];
		} catch (Exception $e) {
			return ['status' => $e->getCode(), 'msg' => $e->getMessage()];
		}
	}

	/**
	 * 数据处理
	 *
	 * @param $request
	 */
	protected static function handle_action($class): array{
		/**
		 * 为实现惰性加载，使用控制类
		 * @since 2019.10.01
		 */
		if (!class_exists($class)) {
			return ['status' => 0, 'msg' => __('无效的Action', 'wnd') . ':' . $class];
		}

		try {
			$action = new $class();
			return $action->do_action();
		} catch (Exception $e) {
			return ['status' => $e->getCode(), 'msg' => $e->getMessage()];
		}
	}

	/**
	 * 根据查询参数判断是否为自定义伪静态接口，从而实现输出重写
	 * Endpoint 类相关响应数据应直接输出，而非返回值
	 * @since 0.9.17
	 */
	protected static function handle_endpoint($class) {
		// 执行 Endpoint 类
		try {
			new $class();
		} catch (Exception $e) {
			echo json_encode(['status' => $e->getCode(), 'msg' => $e->getMessage()]);
		}
	}
}
