<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Set session variables for testing
$_SESSION['test'] = 'This is a test session variable';

// Output basic information
echo "<h1>Simple PHP Test</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Data: " . print_r($_SESSION, true) . "</p>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    $localhost = "localhost";
    $username = "root";
    $password = "";
    $dbname = "pistocklntmarch";
    
    $connect = new mysqli($localhost, $username, $password, $dbname);
    
    if ($connect->connect_error) {
        throw new Exception("Connection failed: " . $connect->connect_error);
    }
    
    echo "<p>Database connection successful!</p>";
    
    // Test a simple query
    $result = $connect->query("SELECT COUNT(*) as count FROM products");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>Total products: " . $row['count'] . "</p>";
    } else {
        echo "<p>Error executing query: " . $connect->error . "</p>";
    }
    
    $connect->close();
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Check if common files exist
echo "<h2>File Existence Check</h2>";
$files = [
    'includes/header.php',
    'includes/footer.php',
    'php_action/core.php',
    'php_action/db_connect.php',
    'php_action/middleware.php',
    'php_action/fetchProducts.php'
];

echo "<ul>";
foreach ($files as $file) {
    echo "<li>" . $file . ": " . (file_exists($file) ? "Exists" : "Missing") . "</li>";
}
echo "</ul>";
?> 