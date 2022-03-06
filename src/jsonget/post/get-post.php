<?php
namespace JsonGet\Post;

/**
 * 测试 WP 类自动加载
 */
class Get_Post {

	public function __construct() {
		$post_id    = $_GET['id'] ?? 0;
		$post       = get_post($post_id) ?: new \stdClass;
		$post->time = timer_stop();

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($post);
		exit;
	}
}
