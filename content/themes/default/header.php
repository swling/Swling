<!DOCTYPE html>
<html>

<head>
	<title>维护中</title>
	<?php wp_head(); ?>
</head>

<body>
	<?php
	var_export('is_main_query:'.is_main_query().PHP_EOL);
	var_export('home:'.is_home().PHP_EOL);
	var_export('page:'.is_page().PHP_EOL);
	var_export('single:'.is_single().PHP_EOL);
	var_export('singular:'.is_singular().PHP_EOL);
	var_export('tax:'.is_tax().PHP_EOL);
	var_export('archive:'.is_archive().PHP_EOL);
	var_export('author:'.is_author().PHP_EOL);

	var_dump(is_tax('category','dev'));