<?php
get_header();
?>

<?php
print_r(get_queried_object());

// global $wp_query;
// print_r($wp_query);

while (have_posts()): the_post();
	global $post;
	echo $post->post_title . '<br/>';
endwhile;

?>
<h1><?php echo basename(__FILE__); ?></h1>

<script>
	wnd_ajax_modal("user/wnd_login_form", {
		"haha": "1016",
		"caobi": "好"
	});
</script>

<?php
get_footer();
