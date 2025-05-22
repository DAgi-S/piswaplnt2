<?php
require_once '../includes/db_connect.php';

// Initialize response array
$response = array();

try {
    // Build the base query
    $query = "SELECT 
        sp.id as payment_id,
        sp.payment_date,
        sp.amount,
        sp.payment_method,
        sp.status as payment_status,
        sp.reference_number,
        sp.notes,
        sp.created_at,
        so.order_number,
        c.company_name as client_name,
        a.account_platform as account_name,
        u.full_name as created_by_name
    FROM sales_payments sp
    LEFT JOIN sales_orders so ON sp.sales_order_id = so.id
    LEFT JOIN clients c ON so.client_id = c.id
    LEFT JOIN accounts a ON sp.account_id = a.id
    LEFT JOIN users u ON sp.created_by = u.user_id
    WHERE 1=1";

    // Apply filters if provided
    $params = array();

    // Date range filter
    if (isset($_POST['dateRange']) && !empty($_POST['dateRange'])) {
        $today = date('Y-m-d');
        $dateRange = $_POST['dateRange'];
        
        switch($dateRange) {
            case 'today':
                $query .= " AND DATE(sp.payment_date) = ?";
                $params[] = $today;
                break;
            case 'yesterday':
                $query .= " AND DATE(sp.payment_date) = DATE_SUB(?, INTERVAL 1 DAY)";
                $params[] = $today;
                break;
            case 'last7days':
                $query .= " AND DATE(sp.payment_date) BETWEEN DATE_SUB(?, INTERVAL 6 DAY) AND ?";
                $params[] = $today;
                $params[] = $today;
                break;
            case 'last30days':
                $query .= " AND DATE(sp.payment_date) BETWEEN DATE_SUB(?, INTERVAL 29 DAY) AND ?";
                $params[] = $today;
                $params[] = $today;
                break;
            case 'thisMonth':
                $query .= " AND YEAR(sp.payment_date) = YEAR(?) AND MONTH(sp.payment_date) = MONTH(?)";
                $params[] = $today;
                $params[] = $today;
                break;
            case 'lastMonth':
                $query .= " AND DATE(sp.payment_date) BETWEEN DATE_SUB(DATE_SUB(?, INTERVAL DAY(?)-1 DAY), INTERVAL 1 MONTH) AND LAST_DAY(DATE_SUB(?, INTERVAL 1 MONTH))";
                $params[] = $today;
                $params[] = $today;
                $params[] = $today;
                break;
            case 'custom':
                if (!empty($_POST['startDate']) && !empty($_POST['endDate'])) {
                    $query .= " AND DATE(sp.payment_date) BETWEEN ? AND ?";
                    $params[] = $_POST['startDate'];
                    $params[] = $_POST['endDate'];
                }
                break;
        }
    }

    // Payment status filter
    if (isset($_POST['paymentStatus']) && !empty($_POST['paymentStatus'])) {
        $query .= " AND sp.status = ?";
        $params[] = $_POST['paymentStatus'];
    }

    // Payment method filter
    if (isset($_POST['paymentMethod']) && !empty($_POST['paymentMethod'])) {
        $query .= " AND sp.payment_method = ?";
        $params[] = $_POST['paymentMethod'];
    }

    // Account filter
    if (isset($_POST['account']) && !empty($_POST['account'])) {
        $query .= " AND sp.account_id = ?";
        $params[] = $_POST['account'];
    }

    // Client filter
    if (isset($_POST['client']) && !empty($_POST['client'])) {
        $query .= " AND so.client_id = ?";
        $params[] = $_POST['client'];
    }

    // Get total count before limit and offset
    $countQuery = "SELECT COUNT(*) as total FROM (" . $query . ") as counted";
    $stmt = $connect->prepare($countQuery);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Order handling
    $orderColumn = isset($_POST['order'][0]['column']) ? $_POST['order'][0]['column'] : 3;
    $orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';
    
    $columns = array(
        0 => 'payment_id',
        1 => 'order_number',
        2 => 'client_name',
        3 => 'payment_date',
        4 => 'amount',
        5 => 'payment_method',
        6 => 'account_name',
        7 => 'payment_status',
        8 => 'reference_number'
    );
    
    if (isset($columns[$orderColumn])) {
        $query .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;
    }

    // Add pagination
    if (isset($_POST['start']) && isset($_POST['length'])) {
        $query .= " LIMIT ?, ?";
        $params[] = (int)$_POST['start'];
        $params[] = (int)$_POST['length'];
    }

    // Execute final query
    $stmt = $connect->prepare($query);
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $data = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = array(
            "payment_id" => $row['payment_id'],
            "order_number" => $row['order_number'],
            "client_name" => $row['client_name'],
            "payment_date" => $row['payment_date'],
            "amount" => $row['amount'],
            "payment_method" => $row['payment_method'],
            "account_name" => $row['account_name'],
            "status" => $row['payment_status'],
            "reference_number" => $row['reference_number'],
            "created_by" => $row['created_by_name'],
            "created_at" => $row['created_at']
        );
    }

    // Prepare response
    $response = array(
        "draw" => isset($_POST['draw']) ? intval($_POST['draw']) : 0,
        "recordsTotal" => $totalRecords,
        "recordsFiltered" => $totalRecords,
        "data" => $data
    );

} catch (Exception $e) {
    $response = array(
        "error" => true,
        "message" => "Error: " . $e->getMessage()
    );
}

// Send response
header('Content-Type: application/json');
echo json_encode($response); 