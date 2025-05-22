<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set proper content type
header('Content-Type: application/json');

$output = array(
    'data' => array()
);

try {
    // Query to get raw material movements
    $sql = "SELECT 
                rm.created_at,
                r.name as item_name,
                rm.quantity,
                rm.movement_type,
                rm.reference_type,
                rm.reference_id,
                rm.notes,
                u.username as created_by,
                r.current_stock
            FROM raw_material_movements rm
            LEFT JOIN raw_materials r ON rm.material_id = r.id
            LEFT JOIN users u ON rm.created_by = u.user_id
            ORDER BY rm.created_at DESC";

    $result = $connect->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $output['data'][] = array(
                'created_at' => $row['created_at'],
                'item_name' => $row['item_name'],
                'quantity' => $row['quantity'],
                'movement_type' => $row['movement_type'],
                'reference_type' => ucfirst(str_replace('_', ' ', $row['reference_type'])),
                'reference_id' => $row['reference_id'],
                'notes' => $row['notes'],
                'created_by' => $row['created_by'],
                'current_stock' => $row['current_stock']
            );
        }
    }

    echo json_encode($output);
} catch (Exception $e) {
    $output['error'] = $e->getMessage();
    echo json_encode($output);
} 