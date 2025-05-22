<?php 
require_once dirname(dirname(__DIR__)) . '/php_action/db_connect.php';

// Check if user is logged in
if(!isset($_SESSION['userId'])) {
	header('location: ' . dirname(dirname($_SERVER['PHP_SELF'])) . '/index.php');
	exit();
} 