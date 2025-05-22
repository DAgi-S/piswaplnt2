<?php
require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output that might corrupt JSON
while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => '',
    'data' => array()
);

$sql = "SELECT id, material_code, name, unit, cost_per_unit 
        FROM raw_materials 
        WHERE status = 'active' 
        ORDER BY name ASC";

try {
    $result = $connect->query($sql);
    
    if($result) {
        while($row = $result->fetch_assoc()) {
            $response['data'][] = array(
                'id' => $row['id'],
                'text' => $row['material_code'] . ' - ' . $row['name'] . ' (' . strtoupper($row['unit']) . ')',
                'unit' => strtoupper($row['unit']),
                'cost_per_unit' => $row['cost_per_unit']
            );
        }
        $response['success'] = true;
    } else {
        $response['messages'] = "Error fetching raw materials: " . $connect->error;
    }
} catch(Exception $e) {
    $response['messages'] = "Error: " . $e->getMessage();
}

$connect->close();

echo json_encode($response); 