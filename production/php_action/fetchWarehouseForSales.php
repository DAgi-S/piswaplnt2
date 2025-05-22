<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set Content Type
header('Content-Type: application/json');

// Default response
$response = array(
    'success' => false,
    'data' => array(),
    'messages' => array()
);

try {
    // Get active warehouses
    $sql = "SELECT 
                w.id,
                w.name,
                w.code,
                COALESCE(w.type, 'both') as type,
                w.location,
                w.status,
                (
                    SELECT COUNT(DISTINCT ws.item_id) 
                    FROM warehouse_stock ws 
                    WHERE ws.warehouse_id = w.id 
                    AND ws.quantity > 0
                ) as available_products
            FROM warehouses w
            WHERE w.status = 1 
            AND (w.type IN ('sales', 'both') OR w.type IS NULL)
            ORDER BY w.name ASC";
    
    $result = $connect->query($sql);
    
    if ($result) {
        $warehouses = array();
        while ($row = $result->fetch_assoc()) {
            // Format display name
            $displayName = sprintf(
                '%s (%s) - %s products in stock',
                $row['name'],
                $row['code'],
                $row['available_products']
            );
            
            $warehouses[] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'code' => $row['code'],
                'type' => $row['type'],
                'location' => $row['location'],
                'available_products' => $row['available_products'],
                'display_name' => $displayName
            );
        }
        
        $response['success'] = true;
        $response['data'] = $warehouses;
    } else {
        throw new Exception("Error fetching warehouses: " . $connect->error);
    }
    
} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
}

$connect->close();
echo json_encode($response); 