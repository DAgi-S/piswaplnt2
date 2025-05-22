<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Ensure no output before headers
ob_start();

try {
    // Prepare the SQL query
    $sql = "SELECT 
                w.*,
                (SELECT COUNT(*) FROM warehouse_stock ws WHERE ws.warehouse_id = w.id) as stock_count
            FROM warehouses w
            ORDER BY w.name ASC";
    
    $result = $connect->query($sql);
    
    if (!$result) {
        throw new Exception("Error fetching warehouses: " . $connect->error);
    }
    
    $output = array('data' => array());
    
    while ($row = $result->fetch_assoc()) {
        // Sanitize the data
        $warehouseId = intval($row['id']);
        $code = htmlspecialchars($row['code']);
        $name = htmlspecialchars($row['name']);
        $type = htmlspecialchars($row['type']);
        $location = htmlspecialchars($row['location'] ?? '');
        $status = htmlspecialchars($row['status']);
        $stockCount = intval($row['stock_count']);
        
        // Create the data array
        $data = array(
            'id' => $warehouseId,
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'location' => $location,
            'status' => $status,
            'stock_count' => $stockCount
        );
        
        $output['data'][] = $data;
    }
    
    // Clear any previous output
    ob_clean();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    // Log error
    error_log("Warehouse Fetch Error: " . $e->getMessage());
    
    // Clear any previous output
    ob_clean();
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode(array(
        'error' => true,
        'message' => 'Error fetching warehouse data. Please try again.'
    ));
}

// Close the database connection
$connect->close(); 