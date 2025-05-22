<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHP Test Page</h1>";

// Test database connection
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "pistocklnt";

try {
    $connect = new mysqli($localhost, $username, $password, $dbname);
    
    if($connect->connect_error) {
        throw new Exception("Connection Failed: " . $connect->connect_error);
    }
    
    echo "<p style='color: green;'>Database connection successful!</p>";
    
    // Test if tables exist
    $tables = ['user_roles', 'users'];
    foreach($tables as $table) {
        $result = $connect->query("SHOW TABLES LIKE '$table'");
        echo "<p>Table '$table' exists: " . ($result->num_rows > 0 ? "Yes" : "No") . "</p>";
    }
    
    // Test if admin user exists
    $result = $connect->query("SELECT user_id, username, role_id FROM users WHERE username = 'admin'");
    if($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p>Admin user exists with ID: {$user['user_id']} and Role ID: {$user['role_id']}</p>";
    } else {
        echo "<p style='color: red;'>Admin user does not exist!</p>";
    }
    
} catch(Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Test session
session_start();
echo "<h2>Session Information:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test file paths
echo "<h2>File Path Information:</h2>";
echo "Current file: " . __FILE__ . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "PHP SELF: " . $_SERVER['PHP_SELF'] . "<br>";

// Test required files exist
$files = [
    '../php_action/core.php',
    '../php_action/db_connect.php',
    'includes/header.php',
    'includes/auth_check.php'
];

echo "<h2>Required Files Check:</h2>";
foreach($files as $file) {
    echo "File '$file' exists: " . (file_exists($file) ? "Yes" : "No") . "<br>";
}
?> 