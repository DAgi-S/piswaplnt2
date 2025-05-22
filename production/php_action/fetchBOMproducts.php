<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

try {
    // Check if product_id is provided
    if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
        throw new Exception('Product ID is required');
    }

    $productId = intval($_POST['product_id']);

    // Fetch BOM items
    $sql = "SELECT 
                rm.name as raw_material_name,
                pb.quantity as quantity,
                rm.unit,
                pb.wastage as wastage,
                COALESCE(rm.current_stock, 0) as raw_material_stock,
                rm.status
            FROM product_bom pb
            JOIN raw_materials rm ON rm.id = pb.raw_material_id
            WHERE pb.product_id = :product_id 
            ORDER BY rm.name ASC";

    $stmt = $connect->prepare($sql);
    $stmt->execute([':product_id' => $productId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'messages' => $e->getMessage()
    ]);
} 