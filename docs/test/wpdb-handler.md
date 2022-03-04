```php
add_filter('insert_post_data', function (array $data): array{
	$data['post_name'] = $data['post_name'] ?? uniqid();
	return $data;
});

add_action('before_delete_post', function ($where) {
	var_export($where);
	exit;
});

$wpdb_handler = Model\WPDB_Handler_Post::get_instance();
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

$wpdb_handler = Model\WPDB_Handler_User::get_instance();
echo $wpdb_handler->insert(['user_login' => 'admin', 'display_name' => time()]);
echo $wpdb_handler->update(['display_name' => '修改'], ['ID' => '1']);
print_r($wpdb_handler->get(['ID' => 1]));
print_r($wpdb_handler->delete(['ID' => 1]));
```

## WP_Query
```php
$args = [
	// 'fields'         => 'ids',
	'post_type'      => 'post',
	// 'post_author'    => 1,
	// 'post_parent'    => '0',
	'posts_per_page' => 3,
	'post_status'    => ['draft', 'pending'],
	'tax_query'      => [
		[
			'taxonomy' => 'category',
			'field'    => 'id',
			'terms'    => '355',
		],
	],
	// 'date_query'     => [
	// 	[
	// 		'year'  => 2021,
	// 		'month' => 2,
	// 		// 'day'   => 12,
	// 	],
	// ],
	// 'meta_key'       => 'price',
	// 'meta_value'     => '0.5',
	// 'meta_compare'   => '=',
	// 'orderby'        => 'comment_count',
	'order'          => 'DESC',
	// 'ID'    => ['10', '2266'],
];

$query = new WP_Query($args);
$posts = $query->get_posts();
echo $query->request . '<br/>';
echo '<br/>';

foreach ($posts as $post) {
	print_r($post->post_title ?? $post);
	echo '<br/>';
}

// global $wpdb;
// print_r($wpdb->queries);

```

## WP_Term_Query
```php
$args = [
	// 'slug'       => 'note',
	// 'taxonomy' => ['category'],
	'number'     => 3,
	'object_ids' => [1],
	// 'parent' => 88,

	// 'meta_key'     => 'price',
	// 'meta_value'   => '1',
	// 'meta_compare' => '=',
	// 'orderby'    => 'meta_value_num',
	'order'      => 'ASC',
];

$query = new WP_Term_Query($args);
$posts = $query->get_terms();
echo $query->request . '<br/>';
echo '<br/>';

foreach ($posts as $post) {
	print_r($post->name ?? $post);
	echo '<br/>';
}
```