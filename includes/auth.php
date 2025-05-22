<?php
// Initialize core functionality if not already done
if (!defined('CORE_INCLUDED')) {
    require_once __DIR__ . '/../php_action/core.php';
    define('CORE_INCLUDED', true);
}
require_once __DIR__ . '/../php_action/db_connect.php';
require_once __DIR__ . '/../php_action/middleware.php';

// Check if user is logged in
if (!isset($_SESSION['userId']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header('Location: ../login.php');
    exit();
}
?> 