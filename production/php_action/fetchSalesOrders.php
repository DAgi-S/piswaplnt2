<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set Content Type
header('Content-Type: application/json');

// Default response
$response = array(
    'draw' => isset($_POST['draw']) ? intval($_POST['draw']) : 1,
    'recordsTotal' => 0,
    'recordsFiltered' => 0,
    'data' => array(),
    'error' => null
);

try {
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Get total records count
    $totalRecordsStmt = $connect->query("SELECT COUNT(*) as total FROM sales_orders");
    if (!$totalRecordsStmt) {
        throw new Exception("Error getting total records: " . $connect->error);
    }
    $totalRecords = $totalRecordsStmt->fetch_assoc()['total'];
    $response['recordsTotal'] = intval($totalRecords);

    // Build the SQL query
    $sql = "SELECT DISTINCT
                so.id,
                so.order_number,
                DATE_FORMAT(so.order_date, '%Y-%m-%d') as order_date,
                so.total_amount,
                so.paid_amount,
                so.balance,
                so.payment_status,
                so.order_status,
                c.company_name as client_name,
                COALESCE(u.username, 'System') as created_by_name
            FROM sales_orders so
            LEFT JOIN clients c ON so.client_id = c.id
            LEFT JOIN users u ON so.created_by = u.user_id
            LEFT JOIN sales_order_items soi ON so.id = soi.sales_order_id
            LEFT JOIN production_products p ON soi.product_id = p.id";

    // Apply search filtering if search value is provided
    $searchValue = isset($_POST['search']['value']) ? $connect->real_escape_string($_POST['search']['value']) : '';
    if (!empty($searchValue)) {
        $sql .= " WHERE (
            so.order_number LIKE '%$searchValue%' OR
            c.company_name LIKE '%$searchValue%' OR
            so.order_status LIKE '%$searchValue%' OR
            so.payment_status LIKE '%$searchValue%' OR
            LOWER(p.name) LIKE LOWER('%$searchValue%') OR
            LOWER(p.product_code) LIKE LOWER('%$searchValue%')
        )";
    }

    // Get filtered records count for pagination
    $countSql = "SELECT COUNT(DISTINCT so.id) as total FROM sales_orders so
                 LEFT JOIN clients c ON so.client_id = c.id
                 LEFT JOIN users u ON so.created_by = u.user_id
                 LEFT JOIN sales_order_items soi ON so.id = soi.sales_order_id
                 LEFT JOIN production_products p ON soi.product_id = p.id";
    
    if (!empty($searchValue)) {
        $countSql .= " WHERE (
            so.order_number LIKE '%$searchValue%' OR
            c.company_name LIKE '%$searchValue%' OR
            so.order_status LIKE '%$searchValue%' OR
            so.payment_status LIKE '%$searchValue%' OR
            LOWER(p.name) LIKE LOWER('%$searchValue%') OR
            LOWER(p.product_code) LIKE LOWER('%$searchValue%')
        )";
    }

    $filteredRecordsStmt = $connect->query($countSql);
    if (!$filteredRecordsStmt) {
        throw new Exception("Error getting filtered records: " . $connect->error);
    }
    $filteredCount = $filteredRecordsStmt->fetch_assoc()['total'];
    $response['recordsFiltered'] = intval($filteredCount);

    // Apply ordering
    $orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 2;
    $orderDir = isset($_POST['order'][0]['dir']) ? $connect->real_escape_string($_POST['order'][0]['dir']) : 'desc';
    $columns = array(
        'so.order_number',
        'c.company_name',
        'so.order_date',
        'so.total_amount',
        'so.paid_amount',
        'so.balance',
        'so.payment_status',
        'so.order_status'
    );

    if (isset($columns[$orderColumn])) {
        $sql .= " ORDER BY {$columns[$orderColumn]} $orderDir";
    } else {
        $sql .= " ORDER BY so.created_at DESC";
    }

    // Only apply pagination if length is not -1 (which means show all)
    if (isset($_POST['length']) && intval($_POST['length']) != -1) {
        $start = isset($_POST['start']) ? intval($_POST['start']) : 0;
        $length = isset($_POST['length']) ? intval($_POST['length']) : 10;
        $sql .= " LIMIT $start, $length";
    }

    // Log the SQL query for debugging
    error_log("SQL Query: " . $sql);

    // Execute the final query
    $stmt = $connect->query($sql);
    if (!$stmt) {
        throw new Exception("Error executing final query: " . $connect->error);
    }
    
    // Log the number of rows returned
    error_log("Number of rows returned: " . $stmt->num_rows);
    
    // Fetch and format the results
    $data = array();
    while ($row = $stmt->fetch_assoc()) {
        $data[] = array(
            'id' => $row['id'],
            'order_number' => $row['order_number'],
            'client_name' => $row['client_name'],
            'order_date' => $row['order_date'],
            'total_amount' => floatval($row['total_amount']),
            'paid_amount' => floatval($row['paid_amount']),
            'balance' => floatval($row['balance']),
            'payment_status' => $row['payment_status'],
            'order_status' => $row['order_status'],
            'actions' => generateActionButtons($row)
        );
    }
    
    $response['data'] = $data;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    error_log("Error in fetchSalesOrders.php: " . $e->getMessage());
}

// Helper functions
function getPaymentStatusClass($status) {
    $classes = array(
        'unpaid' => 'danger',
        'partial' => 'warning',
        'paid' => 'success'
    );
    return isset($classes[strtolower($status)]) ? $classes[strtolower($status)] : 'default';
}

function getOrderStatusClass($status) {
    $classes = array(
        'pending' => 'warning',
        'processing' => 'info',
        'completed' => 'success',
        'cancelled' => 'danger'
    );
    return isset($classes[strtolower($status)]) ? $classes[strtolower($status)] : 'default';
}

function generateActionButtons($row) {
    $buttons = '<div class="btn-group btn-group-sm">';
    $buttons .= '<button type="button" class="btn btn-default view-btn" data-id="' . $row['id'] . '"><i class="fa fa-eye"></i></button>';
    
    if (strtolower($row['order_status']) !== 'cancelled' && strtolower($row['payment_status']) !== 'paid') {
        $buttons .= '<button type="button" class="btn btn-success payment-btn" data-id="' . $row['id'] . '"><i class="fa fa-money"></i></button>';
    }
    
    if (strtolower($row['order_status']) !== 'completed' && strtolower($row['order_status']) !== 'cancelled') {
        $buttons .= '<button type="button" class="btn btn-info status-btn" data-id="' . $row['id'] . '"><i class="fa fa-refresh"></i></button>';
    }
    
    $buttons .= '</div>';
    return $buttons;
}

echo json_encode($response); 