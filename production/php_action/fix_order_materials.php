<?php
// Include necessary files
require_once 'db_connect.php';

// Test database connection
echo "<h2>Testing Database Connection</h2>";
if ($connect) {
    echo "Database connection successful<br>";
} else {
    echo "Database connection failed<br>";
    exit;
}

// Test order ID
$orderId = 1;
$materialId = 1;
$requiredQuantity = 10;

// Test insertion with proper column names
try {
    echo "<h2>Testing Insertion</h2>";
    
    if ($connect instanceof PDO) {
        $stmt = $connect->prepare("
            INSERT INTO production_order_materials 
            (production_order_id, material_id, required_quantity, reserved_quantity, consumed_quantity, reservation_status) 
            VALUES (?, ?, ?, 0, 0, 'pending')
        ");
        $result = $stmt->execute([$orderId, $materialId, $requiredQuantity]);
        echo "PDO insertion: " . ($result ? "Success" : "Failed") . "<br>";
    } else {
        $stmt = $connect->prepare("
            INSERT INTO production_order_materials 
            (production_order_id, material_id, required_quantity, reserved_quantity, consumed_quantity, reservation_status) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $zero = 0.0;
        $status = 'pending';
        $stmt->bind_param("iiddss", $orderId, $materialId, $requiredQuantity, $zero, $zero, $status);
        $result = $stmt->execute();
        echo "MySQLi insertion: " . ($result ? "Success" : "Failed") . "<br>";
        $stmt->close();
    }
    
    echo "Insertion completed without errors<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

?> 