<?php
get_header();
?>
<h1><?php echo basename(__FILE__); ?></h1>
<?php

// print_r(get_queried_object());

while (have_posts()) : the_post();
	global $post;
	echo $post->post_content . '<br/>';

	$categories = wp_get_object_terms($post->ID, 'category');
	foreach ($categories as $category) {
		echo '<a href="' . get_term_link($category) . '">' . $category->name . '</a>';
	}
endwhile;

?>

<?php
get_footer();
