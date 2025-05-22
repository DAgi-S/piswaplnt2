<?php
// Load configuration if not already loaded
if (!defined('DB_HOST')) {
    require_once __DIR__ . '/config.php';
}

// Create connection
$connect = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($connect->connect_error) {
    error_log("Database connection failed: " . $connect->connect_error);
    die("Connection failed: " . $connect->connect_error);
}

// Set charset to utf8
$connect->set_charset("utf8");
?> 