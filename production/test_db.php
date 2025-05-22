<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Try to include the database connection
try {
    require_once 'includes/db_connect.php';
    echo "<p style='color:green'>Database connection file included successfully</p>";
} catch(Exception $e) {
    echo "<p style='color:red'>Error including database connection file: " . $e->getMessage() . "</p>";
    exit;
}

// Check if the database connection variable exists
if (!isset($connect)) {
    echo "<p style='color:red'>Database connection variable not set</p>";
    exit;
} 

// Test the database connection
if ($connect->connect_error) {
    echo "<p style='color:red'>Database connection failed: " . $connect->connect_error . "</p>";
    exit;
}

echo "<p style='color:green'>Database connection successful</p>";

// Check if quality_control table exists
$result = $connect->query("SHOW TABLES LIKE 'quality_control'");
if ($result->num_rows == 0) {
    echo "<p style='color:red'>quality_control table does not exist</p>";
} else {
    echo "<p style='color:green'>quality_control table exists</p>";
    
    // Get table structure
    $result = $connect->query("DESCRIBE quality_control");
    echo "<h3>quality_control Table Structure</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . ($value ?? "NULL") . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    // Count records
    $result = $connect->query("SELECT COUNT(*) as count FROM quality_control");
    $row = $result->fetch_assoc();
    echo "<p>Total records in quality_control table: " . $row['count'] . "</p>";
    
    // Get a sample record if any exist
    if ($row['count'] > 0) {
        $result = $connect->query("SELECT * FROM quality_control LIMIT 1");
        if ($result && $result->num_rows > 0) {
            echo "<h3>Sample Record</h3>";
            echo "<pre>";
            print_r($result->fetch_assoc());
            echo "</pre>";
        }
    }
}

// Check if we can connect to related tables
$related_tables = ['production_orders', 'production_products'];
foreach ($related_tables as $table) {
    $result = $connect->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        echo "<p style='color:red'>$table table does not exist</p>";
    } else {
        echo "<p style='color:green'>$table table exists</p>";
        
        // Count records
        $result = $connect->query("SELECT COUNT(*) as count FROM $table");
        $row = $result->fetch_assoc();
        echo "<p>Total records in $table table: " . $row['count'] . "</p>";
    }
}

// Close the connection
$connect->close();
echo "<p style='color:green'>Database connection closed</p>";
?> 