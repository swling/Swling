### 合并 terms 和 term_taxonomy
```php
global $wpdb;
$wpdb->query("
	ALTER TABLE $wpdb->terms
	ADD COLUMN `taxonomy` varchar(32) NOT NULL default '' AFTER `slug`,
	ADD COLUMN `description` longtext NOT NULL AFTER `taxonomy`,
	ADD COLUMN `parent` bigint(20) unsigned NOT NULL default 0 AFTER `description`,
	ADD COLUMN `count` bigint(20) NOT NULL default 0 AFTER `parent`,

	ADD UNIQUE INDEX taxonomy_slug (taxonomy, slug)
	");

$tt = $wpdb->get_results("SELECT * FROM {$wpdb->term_taxonomy}");
foreach ($tt as $t) {
	$wpdb->update(
		$wpdb->terms,
		['taxonomy' => $t->taxonomy, 'description' => $t->description, 'parent' => $t->parent, 'count' => $t->count],
		['term_id' => $t->term_id]
	);
}

print_r($wpdb->last_error);
```

### user 表
```php
global $wpdb;
$wpdb->query("ALTER TABLE $wpdb->users DROP INDEX user_login_key, ADD UNIQUE INDEX user_login_key (user_login)");
print_r($wpdb->last_error);
```