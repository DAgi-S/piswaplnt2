<?php
require_once 'core.php';

if(isset($_POST['materialId'])) {
    $materialId = $_POST['materialId'];
    
    $sql = "SELECT 
                mc.quantity,
                mc.notes,
                mc.created_at,
                u.username as created_by
            FROM material_consumption mc
            LEFT JOIN users u ON mc.created_by = u.user_id
            WHERE mc.material_id = ?
            ORDER BY mc.created_at DESC
            LIMIT 100";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $materialId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $output = array();
    
    while($row = $result->fetch_assoc()) {
        $output[] = array(
            'quantity' => number_format($row['quantity'], 2),
            'notes' => $row['notes'],
            'created_at' => $row['created_at'],
            'created_by' => $row['created_by']
        );
    }
    
    $stmt->close();
    $connect->close();
    
    echo json_encode($output);
} 