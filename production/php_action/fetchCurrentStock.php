<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
    $item_type = isset($_POST['item_type']) ? mysqli_real_escape_string($connect, $_POST['item_type']) : '';
    $warehouse_id = isset($_POST['warehouse_id']) ? intval($_POST['warehouse_id']) : 0;

    // Validate required fields
    if (!$item_id || !$item_type || !$warehouse_id) {
        $response['success'] = false;
        $response['messages'] = 'Missing required fields';
        echo json_encode($response);
        exit();
    }

    try {
        // Get current stock from warehouse_stock table
        $sql = "SELECT COALESCE(quantity, 0) as stock 
                FROM warehouse_stock 
                WHERE warehouse_id = ? 
                AND item_type = ? 
                AND item_id = ?";

        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, 'isi', $warehouse_id, $item_type, $item_id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error fetching stock: " . mysqli_error($connect));
        }

        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        $response['success'] = true;
        $response['stock'] = $row ? floatval($row['stock']) : 0;

    } catch (Exception $e) {
        $response['success'] = false;
        $response['messages'] = $e->getMessage();
    }

} else {
    $response['success'] = false;
    $response['messages'] = 'Invalid request method';
}

echo json_encode($response);
mysqli_close($connect);
?> 