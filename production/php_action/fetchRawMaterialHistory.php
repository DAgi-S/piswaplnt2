<?php
require_once 'core.php';

header('Content-Type: application/json');

// Debug incoming request
error_log("Fetching history for material ID: " . (isset($_POST['materialId']) ? $_POST['materialId'] : 'not set'));

// Validate input
if(!isset($_POST['materialId'])) {
    echo json_encode(array('data' => array()));
    exit();
}

try {
    $materialId = $_POST['materialId'];
    
    // Updated SQL query to include production order details
    $sql = "SELECT 
                m.*,
                u.username as created_by_name,
                r.name as material_name,
                r.unit as material_unit,
                CASE 
                    WHEN m.reference_type = 'production' THEN (
                        SELECT CONCAT('Production Order #', po.order_number, 
                                    ' - ', p.name,
                                    ' (', FORMAT(pom.required_quantity, 2), ' ', r.unit, ' required)')
                        FROM production_orders po
                        JOIN production_order_materials pom ON po.id = pom.production_order_id
                        JOIN production_products p ON po.product_id = p.id
                        WHERE po.id = m.reference_id 
                        AND pom.material_id = m.material_id
                        LIMIT 1
                    )
                    WHEN m.reference_type = 'purchase' THEN (
                        SELECT CONCAT('Purchase #', p.id, 
                                    ' (', s.company_name, ')')
                        FROM purchases p
                        JOIN suppliers s ON p.supplier_id = s.id
                        WHERE p.id = m.reference_id
                        LIMIT 1
                    )
                    ELSE m.reference_type
                END as reference_details
            FROM raw_material_movements m
            LEFT JOIN users u ON m.created_by = u.user_id
            LEFT JOIN raw_materials r ON m.material_id = r.id
            WHERE m.material_id = ?
            ORDER BY m.created_at DESC";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $materialId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Debug query results
    error_log("Query executed. Number of rows: " . $result->num_rows);
    
    $data = array();
    while($row = $result->fetch_assoc()) {
        // Format quantity with sign and unit
        $formattedQuantity = ($row['movement_type'] == 'in' ? '+' : '-') . 
                            number_format($row['quantity'], 2) . ' ' . 
                            strtoupper($row['material_unit']);
        
        // Format movement type label
        $movementTypeLabel = '<span class="label label-' . 
                           ($row['movement_type'] == 'in' ? 'success' : 'danger') . '">' . 
                           strtoupper($row['movement_type']) . '</span>';
        
        // Format the data for DataTables
        $data[] = array(
            'created_at' => date('Y-m-d H:i:s', strtotime($row['created_at'])),
            'movement_type' => strtoupper($row['movement_type']),
            'quantity' => ($row['movement_type'] == 'in' ? '+' : '-') . number_format($row['quantity'], 2) . ' ' . strtoupper($row['material_unit']),
            'reference_type' => ucfirst($row['reference_type']) . 
                              ($row['reference_id'] ? ' #' . $row['reference_id'] : ''),
            'notes' => $row['notes'] ?: '-',
            'created_by_name' => $row['created_by_name'] ?: 'System'
        );
    }
    
    // Debug final output
    error_log("Final data array: " . json_encode($data));
    
    echo json_encode(array('data' => $data));
    
} catch (Exception $e) {
    error_log("Error in fetchRawMaterialHistory: " . $e->getMessage());
    echo json_encode(array(
        'error' => true,
        'message' => $e->getMessage(),
        'data' => array()
    ));
} finally {
    if(isset($stmt)) {
        $stmt->close();
    }
    $connect->close();
} 