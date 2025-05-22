<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

echo "Connected successfully\n";

$sql = "SELECT COUNT(*) as count FROM products WHERE status = 1 AND active = 1";
$result = $connect->query($sql);

if ($result) {
    $row = $result->fetch_assoc();
    echo "Active products count: " . $row['count'] . "\n";
} else {
    echo "Error: " . $connect->error . "\n";
}

$connect->close();
?> 