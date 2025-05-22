<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pistocklnt');

// WebSocket configuration
define('WS_HOST', '0.0.0.0');
define('WS_PORT', 8080);

// Create database connection
$connect = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($connect->connect_error) {
    die("Connection Failed: " . $connect->connect_error);
}

// Set charset to handle special characters
$connect->set_charset("utf8");

return $connect; 