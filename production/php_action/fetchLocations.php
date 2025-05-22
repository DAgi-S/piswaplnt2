<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => '',
    'data' => array()
);

if(isset($_POST['warehouseId'])) {
    try {
        $warehouseId = intval($_POST['warehouseId']);
        
        // Fetch locations with zone information
        $sql = "SELECT sl.id, sl.location_code, wz.name as zone_name, sl.status, sl.current_utilization, sl.capacity 
                FROM storage_locations sl 
                INNER JOIN warehouse_zones wz ON sl.zone_id = wz.id 
                WHERE wz.warehouse_id = ? AND sl.status != 'blocked' AND wz.status = 'active'
                ORDER BY wz.name, sl.location_code";
                
        $stmt = $connect->prepare($sql);
        if(!$stmt) {
            throw new Exception("Error preparing query: " . $connect->error);
        }
        
        $stmt->bind_param("i", $warehouseId);
        if(!$stmt->execute()) {
            throw new Exception("Error executing query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        while($row = $result->fetch_assoc()) {
            // Calculate available capacity
            $available = $row['capacity'] - $row['current_utilization'];
            $utilization = $row['capacity'] > 0 ? round(($row['current_utilization'] / $row['capacity']) * 100) : 0;
            
            // Add capacity info to location name
            $locationInfo = $row['location_code'] . ' (' . $row['zone_name'] . ') - ' . 
                          $available . ' units available (' . $utilization . '% full)';
            
            $response['data'][] = array(
                'id' => $row['id'],
                'location_code' => $row['location_code'],
                'zone_name' => $row['zone_name'],
                'status' => $row['status'],
                'available' => $available,
                'utilization' => $utilization,
                'text' => $locationInfo
            );
        }
        
        $response['success'] = true;
        
    } catch(Exception $e) {
        $response['messages'] = $e->getMessage();
        error_log("Error in fetchLocations.php: " . $e->getMessage());
    }
} else {
    $response['messages'] = "Warehouse ID is required";
}

echo json_encode($response); 