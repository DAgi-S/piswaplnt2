<?php
// Prevent direct access
if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once 'config.php';  // Changed from ../php_action/config.php to config.php

// Create connection
$connect = new mysqli($localhost, $username, $password, $dbname);

// Check connection
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Set charset to utf8
$connect->set_charset("utf8");

// Set charset to handle special characters correctly
$connect->set_charset("utf8mb4");
?> 