<?php
global $wpdb;
print_r($wpdb->queries);
?>

<p class="has-text-centered">
    <?php echo 'Files:' . count(get_included_files()); ?> -
    <?php echo 'Queries:' . get_num_queries(); ?> -
    <?php timer_stop(7); ?> -
    <?php echo memory_get_peak_usage() / 1024 / 1024; ?>
</p>

</body>

</html>