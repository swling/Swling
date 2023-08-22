<?php
get_header();
?>
<h1><?php echo basename(__FILE__); ?></h1>
<?php

// print_r(get_queried_object());

while (have_posts()) : the_post();
	global $post;
	echo '<h1>' . $post->post_title . '</h1>';
	echo $post->post_content . '<br/>';

	$categories = wp_get_object_terms($post->ID, 'category');
	foreach ($categories as $category) {
		echo '<a href="' . get_term_link($category) . '">' . $category->name . '</a>';
	}

// print_r($post);
endwhile;

?>

<?php
get_footer();
