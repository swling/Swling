```php
## meta
$handler = WP_Core\Model\WPDB_Handler_Meta::get_instance();
$handler->set_meta_type('post');
print_r($handler->get_object_meta_data(19));
print_r($handler->get_object_meta_data(1));
print_r($handler->get_meta(1, 'views'));
print_r($handler->get_meta(1, 'nickname'));
var_dump($handler->add_meta(1, 'nickname', time()));
var_dump($handler->update_meta(1, 'nickname', time()));
print_r($handler->get_meta(1, 'nickname'));
print_r($handler->delete_meta(1, 'nickname'));

update_metadata('post', 1, 'nickname', time());
print_r(get_metadata('post', 1, 'nickname'));
update_metadata('post', 1, 'wnd_meta', ['name' => '高华', 'term' => '中国现当代史', 'time' => time()]);

# post meta
update_post_meta(1, 'wnd_meta', ['name' => '高华', 'term' => '中国现当代史', 'time' => time()]);
print_r(get_post_meta(1, 'wnd_meta'));
print_r(get_meta_object('post', 1, 'wnd_meta'));
delete_post_meta(1, 'wnd_meta');

# user meta
$user_id = 70;
update_user_meta($user_id, 'wnd_meta', ['name' => '高华', 'term' => '中国现当代史', 'time' => time()]);
print_r(get_user_meta($user_id, 'wnd_meta'));
print_r(get_meta_object('user', $user_id, 'wnd_meta'));
delete_user_meta($user_id, 'wnd_meta');
```