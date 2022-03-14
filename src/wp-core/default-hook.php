<?php
// WP Head
add_action('wp_head', 'print_head_scripts', 8);

// WP Footer
add_action('wp_footer', 'print_footer_scripts', 9);

// Term relationships
add_action('before_delete_term', 'wp_delete_term_object_relationships', 10, 1);
add_action('before_delete_term', 'wp_modify_deleted_term_children', 10, 1);

add_action('before_delete_post', 'wp_delete_object_term_relationships', 10, 1);
