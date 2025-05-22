<?php
// Enable error reporting for non-notice errors only
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 0); // Disable error display in output

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../php_action/core.php';
require_once '../php_action/db_connect.php';

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check database connection
    if ($connect->connect_error) {
        throw new Exception("Connection failed: " . $connect->connect_error);
    }

    // Fetch production orders with all required fields
    $sql = "SELECT 
                po.id,
                po.order_number,
                COALESCE(pp.name, 'N/A') as product_name,
                COALESCE(po.status, 'draft') as status,
                COALESCE(po.target_quantity, 0) as target_quantity,
                COALESCE(po.completed_quantity, 0) as completed_quantity,
                DATE_FORMAT(COALESCE(po.start_date, CURRENT_DATE), '%Y-%m-%d') as start_date,
                DATE_FORMAT(COALESCE(po.expected_completion_date, CURRENT_DATE), '%Y-%m-%d') as expected_completion_date,
                COALESCE(u.username, 'System') as created_by
            FROM production_orders po
            LEFT JOIN production_products pp ON po.product_id = pp.id
            LEFT JOIN users u ON po.created_by = u.user_id
            ORDER BY po.created_at DESC";

    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $connect->error);
    }

    $output = array();

    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Format numbers with null check
            $row['target_quantity'] = number_format((float)$row['target_quantity'], 2, '.', '');
            $row['completed_quantity'] = number_format((float)$row['completed_quantity'], 2, '.', '');
            
            // Ensure all values are properly encoded
            array_walk_recursive($row, function(&$item) {
                if (is_string($item)) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
                }
            });
            
            // Add row to output
            $output[] = $row;
        }
    }

    // Clean response for DataTables
    $response = array(
        "draw" => isset($_POST['draw']) ? (int)$_POST['draw'] : 1,
        "recordsTotal" => (int)$result->num_rows,
        "recordsFiltered" => (int)$result->num_rows,
        "data" => $output
    );

    // Ensure clean JSON output
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    error_log("Error in fetchProductionOrders.php: " . $e->getMessage());
    // Return a clean error response
    echo json_encode(array(
        'error' => true,
        'message' => "Error loading production orders"
    ));
} finally {
    if (isset($connect)) {
        $connect->close();
    }
} 