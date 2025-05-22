<?php
require_once 'core.php';
require_once 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Start transaction
    $connect->begin_transaction();

    // Get all orders with invalid dates
    $query = "SELECT order_number, order_date, created_at 
              FROM sales_orders 
              WHERE order_date = '0000-00-00' 
                 OR order_date = '2030-01-10'
                 OR order_date > created_at";
    
    $result = $connect->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Extract the date from order number (format: SO-YYYYMMDD-XXXX)
            if (preg_match('/SO-(\d{4})(\d{2})(\d{2})-/', $row['order_number'], $matches)) {
                $year = $matches[1];
                $month = $matches[2];
                $day = $matches[3];
                $correctDate = "$year-$month-$day";
                
                // Update the order date
                $updateQuery = "UPDATE sales_orders 
                              SET order_date = ? 
                              WHERE order_number = ?";
                
                $stmt = $connect->prepare($updateQuery);
                $stmt->bind_param('ss', $correctDate, $row['order_number']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error updating order date for {$row['order_number']}: " . $stmt->error);
                }
                
                echo "Updated order {$row['order_number']} date to {$correctDate}\n";
            }
        }
    }

    // Commit transaction
    $connect->commit();
    echo "All orders updated successfully!\n";

} catch (Exception $e) {
    // Rollback on error
    if (isset($connect) && $connect->ping()) {
        $connect->rollback();
    }
    echo "Error: " . $e->getMessage() . "\n";
    error_log("Sales Order Date Fix Error: " . $e->getMessage());
}

// Now verify the updates
$verifyQuery = "SELECT order_number, order_date, created_at 
                FROM sales_orders 
                WHERE order_number IN ('SO-20250301-0001', 'SO-20250301-0003')
                ORDER BY order_number";

$result = $connect->query($verifyQuery);

if ($result) {
    echo "\nVerification Results:\n";
    echo "====================\n";
    while ($row = $result->fetch_assoc()) {
        echo "Order: {$row['order_number']}\n";
        echo "Order Date: {$row['order_date']}\n";
        echo "Created At: {$row['created_at']}\n\n";
    }
}

$connect->close();
?> 