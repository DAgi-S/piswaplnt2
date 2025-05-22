<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "pistocklnt";

// db connection
$connect = new mysqli($localhost, $username, $password, $dbname);
// check connection
if($connect->connect_error) {
    die("Connection Failed : " . $connect->connect_error);
}

try {
    // Check if image column exists
    $checkSql = "SHOW COLUMNS FROM products LIKE 'image'";
    $result = $connect->query($checkSql);
    
    if ($result === false) {
        throw new Exception("Error checking column: " . $connect->error);
    }
    
    if ($result->num_rows == 0) {
        // Add image column if it doesn't exist
        $sql = "ALTER TABLE products ADD COLUMN image VARCHAR(255) DEFAULT NULL";
        if ($connect->query($sql)) {
            echo "Image column added successfully\n";
        } else {
            throw new Exception("Error adding image column: " . $connect->error);
        }
    } else {
        echo "Image column already exists\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$connect->close(); 