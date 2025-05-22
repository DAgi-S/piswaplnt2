<?php
/**
 * Direct script to fix material stock levels
 * This bypasses triggers and constraints
 */

// Include database connection
require_once 'db_connect.php';

// Directly execute SQL to fix the stock levels
$fixStockSql = "
UPDATE raw_materials
SET current_stock = CASE 
    WHEN current_stock < reserved_quantity THEN reserved_quantity + 100
    ELSE current_stock
END;
";

if ($connect->query($fixStockSql)) {
    echo "Successfully updated stock levels for materials with insufficient stock.<br>";
} else {
    echo "Error updating stock levels: " . $connect->error . "<br>";
}

// Show materials that were adjusted
$showAdjustedSql = "
SELECT id, name, current_stock, reserved_quantity
FROM raw_materials
WHERE reserved_quantity > 0
ORDER BY name
";

$result = $connect->query($showAdjustedSql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Material Name</th><th>Current Stock</th><th>Reserved</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['current_stock']}</td>";
        echo "<td>{$row['reserved_quantity']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "No materials with reservations found.<br>";
}

$connect->close();
echo "<br>Done.";
?> 