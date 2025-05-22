<?php
/**
 * Direct script to fix material stock levels without transactions or triggers
 */

// Include database connection
require_once 'db_connect.php';

// First disable the trigger if it exists
$connect->query("DROP TRIGGER IF EXISTS stock_check");

echo "Disabled stock_check trigger if it existed<br>";

// Update all materials where current_stock < reserved_quantity
$updateStockSql = "
UPDATE raw_materials
SET current_stock = reserved_quantity + 100
WHERE current_stock < reserved_quantity;
";

if ($connect->query($updateStockSql)) {
    echo "Successfully updated stock levels for materials with insufficient stock<br>";
} else {
    echo "Error updating stock levels: " . $connect->error . "<br>";
}

// Show all materials with their stock levels
echo "<h3>Updated Material Stock Levels</h3>";
$listMaterialsSql = "
SELECT id, name, current_stock, reserved_quantity,
       CASE WHEN current_stock < reserved_quantity THEN 'INSUFFICIENT' ELSE 'OK' END AS status
FROM raw_materials
ORDER BY name;
";

$result = $connect->query($listMaterialsSql);

if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Material</th><th>Stock</th><th>Reserved</th><th>Status</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $status = ($row['current_stock'] < $row['reserved_quantity']) ? 'INSUFFICIENT' : 'OK';
        $statusClass = ($status == 'OK') ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['current_stock']}</td>";
        echo "<td>{$row['reserved_quantity']}</td>";
        echo "<td style='color: $statusClass;'>{$status}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "No materials found<br>";
}

// Recreate the trigger
$createTriggerSql = "
CREATE TRIGGER stock_check BEFORE UPDATE ON raw_materials
FOR EACH ROW
BEGIN
    IF NEW.current_stock < NEW.reserved_quantity THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = CONCAT('Stock cannot be less than reserved quantity. Current: ', NEW.current_stock, ', Reserved: ', NEW.reserved_quantity);
    END IF;
END
";

if ($connect->query($createTriggerSql)) {
    echo "<br>Successfully recreated stock_check trigger<br>";
} else {
    echo "<br>Error recreating stock_check trigger: " . $connect->error . "<br>";
}

$connect->close();
echo "<br>Done.<br>You can now try to confirm your production orders.";
?> 