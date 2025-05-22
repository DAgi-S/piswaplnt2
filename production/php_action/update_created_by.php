<?php
/**
 * Update Production Orders Created By Field
 * 
 * This script updates all production orders with empty created_by fields
 * to assign them to a default user (usually admin)
 */

// Include database connection
require_once 'db_connect.php';

// Output header
header('Content-Type: text/html; charset=utf-8');
echo "<h2>Production Orders - Update Created By</h2>";

try {
    // Count orders with empty created_by fields
    $countQuery = "SELECT COUNT(*) as total FROM production_orders WHERE created_by IS NULL OR created_by = 0";
    $result = $connect->query($countQuery);
    
    if (!$result) {
        throw new Exception("Error counting records: " . $connect->error);
    }
    
    $row = $result->fetch_assoc();
    $totalToUpdate = $row['total'];
    
    echo "<p>Found {$totalToUpdate} production orders with missing created_by values.</p>";
    
    if ($totalToUpdate > 0) {
        // Get the admin user ID (usually 1 or the lowest ID in users table)
        $adminQuery = "SELECT user_id FROM users ORDER BY user_id ASC LIMIT 1";
        $adminResult = $connect->query($adminQuery);
        
        if (!$adminResult || $adminResult->num_rows === 0) {
            throw new Exception("Error: No users found in the system.");
        }
        
        $adminRow = $adminResult->fetch_assoc();
        $adminId = $adminRow['user_id'];
        
        echo "<p>Using user ID {$adminId} as default creator.</p>";
        
        // Update all empty created_by fields
        $updateQuery = "UPDATE production_orders SET created_by = ? WHERE created_by IS NULL OR created_by = 0";
        $stmt = $connect->prepare($updateQuery);
        
        if (!$stmt) {
            throw new Exception("Error preparing update statement: " . $connect->error);
        }
        
        $stmt->bind_param("i", $adminId);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("Error updating records: " . $stmt->error);
        }
        
        $updatedCount = $stmt->affected_rows;
        echo "<p>Successfully updated {$updatedCount} production orders.</p>";
        
        $stmt->close();
    } else {
        echo "<p>No orders need updating.</p>";
    }
    
    echo "<p>Process completed successfully.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
} finally {
    echo "<p><a href='../production_orders.php'>Return to Production Orders</a></p>";
}

$connect->close(); 