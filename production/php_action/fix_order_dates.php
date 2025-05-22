<?php
require_once 'core.php';
require_once 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Start transaction
    $connect->begin_transaction();

    // Prepare the update statement
    $stmt = $connect->prepare("UPDATE sales_orders SET order_date = ? WHERE order_number = ?");
    
    // Array of orders to update
    $orders = [
        ['SO-20250301-0001', '2025-03-01'],
        ['SO-20250301-0003', '2025-03-01']
    ];

    // Update each order
    foreach ($orders as $order) {
        $stmt->bind_param("ss", $order[1], $order[0]);
        if (!$stmt->execute()) {
            throw new Exception("Error updating order {$order[0]}: " . $stmt->error);
        }
        echo "Updated {$order[0]} to date {$order[1]}\n";
    }

    // Commit transaction
    $connect->commit();

    // Verify updates
    $result = $connect->query("SELECT order_number, order_date, created_at 
                             FROM sales_orders 
                             WHERE order_number IN ('SO-20250301-0001', 'SO-20250301-0003') 
                             ORDER BY order_number");

    echo "\nVerification Results:\n";
    echo "====================\n";
    while ($row = $result->fetch_assoc()) {
        echo "Order: " . $row['order_number'] . "\n";
        echo "Order Date: " . $row['order_date'] . "\n";
        echo "Created At: " . $row['created_at'] . "\n\n";
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $connect->rollback();
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    // Close statement and connection
    if (isset($stmt)) $stmt->close();
    $connect->close();
}
?> 