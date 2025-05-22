<?php
// Prevent any unwanted output
ob_start();

require_once 'core.php';
require_once 'db_connect.php';

// Clear any previous output
ob_clean();

// Set proper content type
header('Content-Type: application/json');

// Initialize response array
$response = array(
    'success' => false,
    'data' => array(
        'total_payments' => 0,
        'total_amount' => 0,
        'pending_payments' => 0,
        'pending_amount' => 0
    ),
    'messages' => array()
);

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        throw new Exception("User session not found");
    }

    // Build the WHERE clause based on filters
    $whereClause = "WHERE 1=1";
    $params = array();
    $types = "";

    // Date Range Filter
    if (isset($_POST['dateRange'])) {
        switch ($_POST['dateRange']) {
            case 'today':
                $whereClause .= " AND DATE(payment_date) = CURDATE()";
                break;
            case 'yesterday':
                $whereClause .= " AND DATE(payment_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'last7days':
                $whereClause .= " AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'last30days':
                $whereClause .= " AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            case 'thisMonth':
                $whereClause .= " AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())";
                break;
            case 'lastMonth':
                $whereClause .= " AND payment_date >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH) 
                                 AND payment_date < DATE_FORMAT(CURDATE(), '%Y-%m-01')";
                break;
            case 'custom':
                if (!empty($_POST['startDate']) && !empty($_POST['endDate'])) {
                    $whereClause .= " AND DATE(payment_date) BETWEEN ? AND ?";
                    $params[] = $_POST['startDate'];
                    $params[] = $_POST['endDate'];
                    $types .= "ss";
                }
                break;
        }
    }

    // Payment Status Filter
    if (!empty($_POST['paymentStatus'])) {
        $whereClause .= " AND status = ?";
        $params[] = $_POST['paymentStatus'];
        $types .= "s";
    }

    // Payment Method Filter
    if (!empty($_POST['paymentMethod'])) {
        $whereClause .= " AND payment_method = ?";
        $params[] = $_POST['paymentMethod'];
        $types .= "s";
    }

    // Account Filter
    if (!empty($_POST['account'])) {
        $whereClause .= " AND account_id = ?";
        $params[] = $_POST['account'];
        $types .= "i";
    }

    // Client Filter
    if (!empty($_POST['client'])) {
        $whereClause .= " AND sales_order_id IN (SELECT id FROM sales_orders WHERE client_id = ?)";
        $params[] = $_POST['client'];
        $types .= "i";
    }

    // Get total payments and amount
    $totalQuery = "SELECT 
                    COUNT(*) as total_payments,
                    COALESCE(SUM(amount), 0) as total_amount
                   FROM sales_payments
                   $whereClause";
    
    $stmt = $connect->prepare($totalQuery);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $totalResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Get pending payments and amount
    $pendingWhereClause = $whereClause . " AND status = 'pending'";
    $pendingQuery = "SELECT 
                      COUNT(*) as pending_payments,
                      COALESCE(SUM(amount), 0) as pending_amount
                     FROM sales_payments
                     $pendingWhereClause";
    
    $stmt = $connect->prepare($pendingQuery);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $pendingResult = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Set response data
    $response['data'] = array(
        'total_payments' => intval($totalResult['total_payments']),
        'total_amount' => floatval($totalResult['total_amount']),
        'pending_payments' => intval($pendingResult['pending_payments']),
        'pending_amount' => floatval($pendingResult['pending_amount'])
    );
    
    $response['success'] = true;

} catch (Exception $e) {
    $response['messages'][] = $e->getMessage();
    error_log("Error in fetchSalesPaymentSummary.php: " . $e->getMessage());
}

// Make sure we have no other output
ob_end_clean();

// Send JSON response
echo json_encode($response);
exit();
?> 