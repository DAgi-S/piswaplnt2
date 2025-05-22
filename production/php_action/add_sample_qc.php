<?php
require_once '../includes/db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Adding Sample Quality Control Entries</h1>";

try {
    // First, check if the quality_control table exists
    $table_check = "SHOW TABLES LIKE 'quality_control'";
    $result = $connect->query($table_check);
    
    if ($result->num_rows == 0) {
        echo "<p>Error: Quality control table does not exist.</p>";
        exit;
    }
    
    echo "<p>Quality control table exists.</p>";
    
    // Check for any existing entries
    $check_entries = "SELECT COUNT(*) as count FROM quality_control";
    $entries_result = $connect->query($check_entries);
    $row = $entries_result->fetch_assoc();
    $entry_count = $row['count'];
    
    echo "<p>Currently there are {$entry_count} quality control entries.</p>";
    
    // Get a production order to link to
    $order_query = "SELECT id, order_number FROM production_orders LIMIT 1";
    $order_result = $connect->query($order_query);
    
    if ($order_result->num_rows == 0) {
        echo "<p>Error: No production orders found. Cannot create sample QC entry.</p>";
        exit;
    }
    
    $order = $order_result->fetch_assoc();
    $production_order_id = $order['id'];
    $order_number = $order['order_number'];
    
    echo "<p>Found production order: {$order_number} (ID: {$production_order_id})</p>";
    
    // Create a sample quality control entry
    $inspection_date = date('Y-m-d');
    $quantity_checked = 100;
    $quantity_passed = 90;
    $quantity_failed = 10;
    $defect_type = "Surface finish";
    $notes = "Sample quality control entry created for testing";
    $status = "partially_passed";
    
    // Insert the sample entry
    $insert_query = "INSERT INTO quality_control 
        (production_order_id, inspection_date, quantity_checked, quantity_passed, quantity_failed, defect_type, notes, status, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
    $stmt = $connect->prepare($insert_query);
    
    if (!$stmt) {
        echo "<p>Error preparing statement: " . $connect->error . "</p>";
        exit;
    }
    
    $stmt->bind_param("isdddsss", 
        $production_order_id,
        $inspection_date,
        $quantity_checked,
        $quantity_passed,
        $quantity_failed,
        $defect_type,
        $notes,
        $status
    );
    
    if ($stmt->execute()) {
        $qc_id = $connect->insert_id;
        echo "<p>Sample quality control entry created successfully with ID: {$qc_id}</p>";
    } else {
        echo "<p>Error creating sample entry: " . $stmt->error . "</p>";
    }
    
    $stmt->close();
    
    echo "<p><a href='../quality_control.php'>Go to Quality Control</a></p>";
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

$connect->close();