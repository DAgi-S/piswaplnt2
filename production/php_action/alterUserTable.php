<?php
require_once 'core.php';

// Add dashboard_preferences column to users table
$alterQuery = "ALTER TABLE users ADD COLUMN dashboard_preferences TEXT DEFAULT NULL";

try {
    if($connect->query($alterQuery)) {
        echo "Successfully added dashboard_preferences column to users table";
    } else {
        echo "Error adding column: " . $connect->error;
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}

$connect->close();
?> 