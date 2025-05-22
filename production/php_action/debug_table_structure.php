<?php
// Include database connection
require_once 'db_connect.php';

// Function to get table structure
function getTableColumns($conn, $tableName) {
    $columns = [];
    
    if ($conn instanceof PDO) {
        $stmt = $conn->prepare("DESCRIBE " . $tableName);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row;
        }
    } else {
        $stmt = $conn->prepare("DESCRIBE " . $tableName);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row;
        }
        $stmt->close();
    }
    
    return $columns;
}

// Get structure for production_order_materials table
$tableName = 'production_order_materials';
$columns = getTableColumns($connect, $tableName);

// Output the structure
echo "<h2>Structure of $tableName table</h2>";
echo "<pre>";
print_r($columns);
echo "</pre>";
?> 