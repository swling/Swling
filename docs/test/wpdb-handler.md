```php
add_filter('insert_post_data', function (array $data): array{
	$data['post_name'] = $data['post_name'] ?? uniqid();
	return $data;
});

add_action('before_delete_post', function ($where) {
	var_export($where);
	exit;
});

$wpdb_handler = new Model\WPDB_Handler_Post;
echo $wpdb_handler->insert(['post_title' => time()]);
echo $wpdb_handler->update(['post_title' => '修改0'], ['ID' => '1']);
print_r($wpdb_handler->get(['ID' => '1']));
print_r($wpdb_handler->delete(['ID' => '1']));

add_filter('insert_user_data', function (array $data): array{
	$data['user_pass'] = '过滤写入数据';
	return $data;
});

add_action('before_delete_user', function ($where) {
	var_export($where);
	exit;
});

$wpdb_handler = new Model\WPDB_Handler_User;
echo $wpdb_handler->insert(['user_login' => 'admin', 'display_name' => time()]);
echo $wpdb_handler->update(['display_name' => '修改'], ['ID' => '1']);
print_r($wpdb_handler->get(['ID' => 1]));
print_r($wpdb_handler->delete(['ID' => 1]));
```