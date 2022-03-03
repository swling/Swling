<?php

add_action('before_delete_term', 'wp_delete_term_object_relationships', 10, 1);
add_action('before_delete_term', 'wp_modify_deleted_term_children', 10, 1);
