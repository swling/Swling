<?php
get_header();
?>

<?php
print_r(get_queried_object());

// global $wp_query;
// print_r($wp_query);

while (have_posts()) : the_post();
	global $post;
	echo '<a href="' . get_permalink() . '">' . $post->post_title . '</a><br/>';
endwhile;

?>
<h1><?php echo basename(__FILE__); ?></h1>

<script>
	wnd_ajax_modal("user/wnd_profile_form", {
		"haha": "1016",
		"caobi": "好"
	});
</script>

<?php
get_footer();
