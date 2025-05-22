<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'core.php';

// Clear any previous output
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'messages' => '',
    'html' => '',
    'data' => null
);

try {
    if (!isset($_POST['movement_id']) || empty($_POST['movement_id'])) {
        throw new Exception('Movement ID is required');
    }

    $movementId = intval($_POST['movement_id']);

    // Get movement details with related information
    $sql = "SELECT 
                sm.*,
                p.name as product_name,
                u.username as created_by_name,
                COALESCE(ws.quantity, 0) as current_stock
            FROM stock_movements sm 
            LEFT JOIN products p ON sm.product_id = p.product_id
            LEFT JOIN users u ON sm.created_by = u.user_id
            LEFT JOIN warehouse_stock ws ON ws.item_id = sm.product_id AND ws.item_type = 'product'
            WHERE sm.movement_id = ?";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $movementId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        // Format the HTML for the modal
        $html = '
        <div class="table-responsive">
            <table class="table table-bordered">
                <tr>
                    <th style="width: 30%;">Date</th>
                    <td>'.date('Y-m-d H:i:s', strtotime($row['created_at'])).'</td>
                </tr>
                <tr>
                    <th>Product</th>
                    <td>'.$row['product_name'].'</td>
                </tr>
                <tr>
                    <th>Quantity</th>
                    <td>'.number_format($row['quantity'], 2).' '.($row['movement_type'] == 'in' ? '<span class="label label-success">IN</span>' : '<span class="label label-danger">OUT</span>').'</td>
                </tr>
                <tr>
                    <th>Reference Type</th>
                    <td>'.ucfirst($row['reference_type']).'</td>
                </tr>
                <tr>
                    <th>Reference ID</th>
                    <td>'.$row['reference_id'].'</td>
                </tr>
                <tr>
                    <th>Notes</th>
                    <td>'.(empty($row['notes']) ? '<em>No notes</em>' : $row['notes']).'</td>
                </tr>
                <tr>
                    <th>Created By</th>
                    <td>'.$row['created_by_name'].'</td>
                </tr>
                <tr>
                    <th>Current Stock</th>
                    <td>'.number_format($row['current_stock'], 2).'</td>
                </tr>
            </table>
        </div>';

        $response['success'] = true;
        $response['html'] = $html;
        $response['data'] = $row;
    } else {
        throw new Exception('Stock movement not found');
    }

} catch (Exception $e) {
    $response['messages'] = $e->getMessage();
}

echo json_encode($response); 