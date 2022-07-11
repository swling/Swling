<?php
get_header();
?>
<h1><?php echo basename(__FILE__); ?></h1>
<?php

// var_dump(have_posts());
// exit('cccc');

while (have_posts()) : the_post();
	global $post;
	echo '<a href="' . get_permalink() . '">' . $post->post_title . '</a><br/>';
// print_r(wp_get_object_terms($post->ID, 'post_tag'));
endwhile;
?>
<?php
// global $wp_query;

// print_r($wp_query);

get_footer();
