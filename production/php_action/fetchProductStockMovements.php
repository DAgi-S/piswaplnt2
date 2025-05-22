<?php
require_once '../core.php';
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isset($_POST['product_id'])) {
    die(json_encode([
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => []
    ]));
}

$productId = intval($_POST['product_id']);

$sql = "SELECT 
            sm.created_at,
            sm.movement_type,
            sm.quantity,
            sm.reference_type,
            sm.reference_id,
            sm.notes,
            CONCAT(u.firstname, ' ', u.lastname) as created_by,
            p.current_stock,
            p.unit
        FROM stock_movements sm
        JOIN production_products p ON sm.product_id = p.id
        LEFT JOIN users u ON sm.created_by = u.id
        WHERE sm.product_id = ?
        ORDER BY sm.created_at DESC";

$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $productId);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        $row['created_at'],
        ucfirst($row['movement_type']),
        number_format($row['quantity'], 2) . ' ' . $row['unit'],
        $row['reference_type'] . ' #' . $row['reference_id'],
        $row['notes'],
        $row['created_by'],
        number_format($row['current_stock'], 2) . ' ' . $row['unit']
    ];
}

echo json_encode([
    "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
    "recordsTotal" => $result->num_rows,
    "recordsFiltered" => $result->num_rows,
    "data" => $data
]);

$stmt->close();
$connect->close();