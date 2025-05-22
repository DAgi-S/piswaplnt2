<?php 

session_start();

require_once 'php_action/db_connect.php';

// Only check permissions if the constant is defined and true
if (defined('CHECK_PERMISSIONS') && CHECK_PERMISSIONS === true) {
    require_once 'php_action/middleware.php';
    
    if (!hasPermission('create_letter')) {
        echo json_encode([
            'success' => false,
            'messages' => 'Permission denied'
        ]);
        exit();
    }
}

if(!isset($_SESSION['userId'])) {
    header('location: login.php');
    exit();
} 



?>