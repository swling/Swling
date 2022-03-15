<?php
require 'config.php';

$route = new Controller\Route();
$route->dispatch();
return;

## Test Code Area

## Query
global $wpdb;
print_r($wpdb->queries);
