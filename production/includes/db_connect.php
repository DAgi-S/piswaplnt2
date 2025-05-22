<?php
// Include configuration file
require_once '../php_action/config.php';

try {
    // Create PDO connection with error handling
    $connect = new PDO(
        "mysql:host=$localhost;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );
} catch (PDOException $e) {
    // Log error without outputting
    error_log("Database connection failed: " . $e->getMessage());
    // Return false instead of dying with output
    $connect = false;
} 