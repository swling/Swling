```php
print_r(wp_insert_term('标签', 'post_name'));

var_dump(term_exists(1, 'category'));

print_r(wp_insert_term('term名称', 'category', ['slug' => 'love50000000', 'description' => '描述', 'parent' => 1000]));
print_r(wp_insert_term('term名称0', 'post_tag', ['slug' => 'lmy0', 'description' => '描述', 'parent' => 0]));
get_term(41);
print_r(wp_update_term(4, 'category', ['name' => 'term名称0000', 'slug' => 'love', 'description' => '描述xiug00', 'parent' => 0]));
print_r(wp_update_term(42, 'post_tag', ['name' => 'term名称0', 'slug' => 'lmy01', 'description' => '描述xiug00', 'parent' => 0]));
get_term(41);

wp_delete_term(32);

var_dump(get_term(41));

var_dump(get_terms(['taxonomy' => 'category', 'slug' => 'image']));

var_dump(get_term_by('slug', 'love', ''));

wp_set_object_terms($object_id, $terms, $taxonomy, false);

(wp_get_object_terms(1, 'category', ['order' => 'DESC']));
wp_cache_get_last_changed('terms');
wp_cache_delete_last_changed('terms');
(wp_get_object_terms(1, 'category'));

print_r(wp_set_object_terms(1, ['9', '1'], 'category'));
print_r(wp_set_object_terms(1, ['标枪', '毒刺'], 'post_tag'));
print_r(wp_set_object_terms(11, [], 'post_tag'));

print_r(wp_get_object_terms(1, 'category', ['order' => 'DESC']));

wp_delete_object_term_relationships(1, 'post_tag');

wp_cache_delete_last_changed('terms');

wp_delete_object_term_relationships(1, 'category');

has_term( '', '', null );

is_object_in_term( , $taxonomy, null );

var_dump(is_object_in_term(1, 'category', 9));

## Term Relationships
wp_set_object_terms(1, ['标枪', '毒刺'], 'post_tag');
wp_delete_term(get_term_by('slug', '标枪', 'post_tag')->term_id);

## delete post
$post_id = wp_insert_post(['post_title' => uniqid(), 'post_type' => 'post', 'post_status' => 'publish', 'post_name' => uniqid()]);
wp_set_object_terms($post_id, ['标枪2', '毒刺2'], 'post_tag');
wp_delete_post(99);
```