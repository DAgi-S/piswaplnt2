<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

// Check connection
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Check products table structure
$sql = "DESCRIBE products";
$result = $connect->query($sql);
if ($result) {
    echo "Table structure:\n";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
        echo "\n";
    }
} else {
    echo "Error getting table structure: " . $connect->error . "\n";
}

// Count all products
$sql = "SELECT COUNT(*) as count FROM products";
$result = $connect->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    echo "\nTotal products: " . $row['count'] . "\n";
} else {
    echo "Error counting products: " . $connect->error . "\n";
}

// Get active products
$sql = "SELECT COUNT(*) as count FROM products WHERE status = 1 AND active = 1";
$result = $connect->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    echo "\nActive products: " . $row['count'] . "\n";
} else {
    echo "Error counting active products: " . $connect->error . "\n";
}

// List some products
$sql = "SELECT product_id, name, price, quantity, status, active FROM products LIMIT 5";
$result = $connect->query($sql);
if ($result) {
    echo "\nSample products:\n";
    while ($row = $result->fetch_assoc()) {
        print_r($row);
        echo "\n";
    }
} else {
    echo "Error getting sample products: " . $connect->error . "\n";
}

$connect->close();
?> 