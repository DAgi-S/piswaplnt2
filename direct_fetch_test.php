<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set session variables for testing (simulate admin login)
$_SESSION['userId'] = 1;
$_SESSION['userName'] = 'admin';
$_SESSION['roleId'] = 2; // Admin role

// Buffer the output
ob_start();

// Include fetchProducts.php directly
include 'php_action/fetchProducts.php';

// Get the output
$output = ob_get_clean();

// Display the raw output
echo "<h2>Raw Output:</h2>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Try to decode the JSON
$data = json_decode($output, true);

// Check if it's valid JSON
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<h2>Decoded JSON:</h2>";
    echo "<pre>" . print_r($data, true) . "</pre>";
} else {
    echo "<h2>JSON Error:</h2>";
    echo json_last_error_msg();
}
?> 