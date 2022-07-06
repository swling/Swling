<?php
get_header();
?>
<h1><?php echo basename(__FILE__); ?></h1>
<?php

while (have_posts()) : the_post();
	global $post;
	echo $post->post_title . '<br/>';
endwhile;

?>

<?php
get_footer();
