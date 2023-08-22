<?php
require 'config.php';

// $handler = WP_Core\Model\WPDB_Handler_Meta::get_instance();
// $handler->set_meta_type('post');
// print_r($handler->get_rows(1));
// print_r($handler->get_meta(1, 'views'));
// print_r($handler->get_meta(1, 'nickname'));
// var_dump($handler->add_meta(1, 'nickname', time()));
// var_dump($handler->update_meta(1, 'nickname', time()));
// print_r($handler->get_meta(1, 'nickname'));
// print_r($handler->delete_meta(1, 'nickname'));
// exit;

$route = new Controller\Route();
$route->dispatch();
return;

// var_dump(wp_update_post(['ID' => 1, 'post_title' => time()]));
// print_r(get_post(1));
// delete_post_meta(1, 'test');
// get_post_meta(1, 'test');
// get_post_meta(1, 'test');
// echo get_post_meta(1, 'test');

## Test Code Area
// $wpdb_handler = WP_Core\Model\WPDB_Handler_Post::get_instance();
// echo $wpdb_handler->insert(['post_title' => time(), 'post_type' => 'post', 'post_name' => uniqid()]);
// echo $wpdb_handler->update(['post_title' => time(), 'ID' => '2525']);
// print_r($wpdb_handler->get(2525));
// print_r($wpdb_handler->delete(['ID' => '2525']));

## Query
global $wpdb;
print_r($wpdb->queries);
