<?php
require_once '../../php_action/core.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize response array
$response = array(
    'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
    'recordsTotal' => 0,
    'recordsFiltered' => 0,
    'data' => array(),
    'success' => false,
    'messages' => array()
);

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        throw new Exception("User not logged in");
    }

    // Get total number of records
    $sqlTotal = "SELECT COUNT(*) as total FROM gps_sales";
    $totalResult = $connect->query($sqlTotal);
    $totalRow = $totalResult->fetch_assoc();
    $response['recordsTotal'] = $response['recordsFiltered'] = intval($totalRow['total']);

    // Fetch sales data
    $sql = "SELECT id, DATE_FORMAT(created_at, '%Y-%m-%d') as sale_date, 
            buyer_name, contact, sales_type, quantity, unit_price, total, created_at 
            FROM gps_sales 
            ORDER BY created_at DESC";

    $result = $connect->query($sql);

    if (!$result) {
        throw new Exception("Error executing query: " . $connect->error);
    }

    while ($row = $result->fetch_assoc()) {
        $actionButtons = '
            <div class="btn-group">
                <button type="button" class="btn btn-primary btn-sm editSale" data-id="'.$row['id'].'">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-danger btn-sm removeSale" data-id="'.$row['id'].'">
                    <i class="fas fa-trash"></i>
                </button>
            </div>';

        $response['data'][] = array(
            $row['sale_date'],
            $row['buyer_name'],
            $row['contact'],
            $row['sales_type'],
            number_format($row['unit_price'], 2),
            $row['quantity'],
            number_format($row['total'], 2),
            $actionButtons
        );
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'][] = $e->getMessage();
    error_log("Error in fetchGpsSalesNew.php: " . $e->getMessage());
}

// Set proper content type
header('Content-Type: application/json');

// Return the response
echo json_encode($response); 