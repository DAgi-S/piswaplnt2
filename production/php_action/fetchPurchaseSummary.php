<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'total_purchases' => 0,
    'total_amount' => 0,
    'paid_amount' => 0,
    'due_amount' => 0
);

try {
    // Build date range condition
    $dateCondition = "";
    if(isset($_POST['dateRange'])) {
        switch($_POST['dateRange']) {
            case 'today':
                $dateCondition = "DATE(p.purchase_date) = CURDATE()";
                break;
            case 'yesterday':
                $dateCondition = "DATE(p.purchase_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'last7days':
                $dateCondition = "DATE(p.purchase_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'last30days':
                $dateCondition = "DATE(p.purchase_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            case 'thisMonth':
                $dateCondition = "MONTH(p.purchase_date) = MONTH(CURDATE()) AND YEAR(p.purchase_date) = YEAR(CURDATE())";
                break;
            case 'lastMonth':
                $dateCondition = "DATE(p.purchase_date) >= DATE_SUB(DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE())-1 DAY), INTERVAL 1 MONTH) 
                                AND DATE(p.purchase_date) <= LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
                break;
            case 'custom':
                if(isset($_POST['startDate']) && isset($_POST['endDate'])) {
                    $dateCondition = "DATE(p.purchase_date) BETWEEN '{$_POST['startDate']}' AND '{$_POST['endDate']}'";
                }
                break;
        }
    }

    // Build supplier condition
    $supplierCondition = "";
    if(isset($_POST['supplier']) && !empty($_POST['supplier'])) {
        $supplierId = $_POST['supplier'];
        $supplierCondition = "AND p.supplier_id = $supplierId";
    }

    // Build payment status condition
    $statusCondition = "";
    if(isset($_POST['paymentStatus']) && !empty($_POST['paymentStatus'])) {
        $status = $_POST['paymentStatus'];
        $statusCondition = "AND p.payment_status = '$status'";
    }

    // Build the complete query
    $sql = "SELECT 
            COUNT(DISTINCT p.id) as total_purchases,
            SUM(p.grand_total) as total_amount,
            COALESCE(SUM(pp.amount), 0) as paid_amount
            FROM purchases p
            LEFT JOIN purchase_payments pp ON p.id = pp.purchase_id
            WHERE p.active = 1 ";

    if($dateCondition) {
        $sql .= "AND $dateCondition ";
    }
    
    $sql .= $supplierCondition . " " . $statusCondition;

    $result = $connect->query($sql);

    if(!$result) {
        throw new Exception("Error executing query: " . $connect->error);
    }

    $row = $result->fetch_array(MYSQLI_ASSOC);
    
    $response['success'] = true;
    $response['total_purchases'] = $row['total_purchases'];
    $response['total_amount'] = $row['total_amount'] ?: 0;
    $response['paid_amount'] = $row['paid_amount'] ?: 0;
    $response['due_amount'] = $response['total_amount'] - $response['paid_amount'];

} catch(Exception $e) {
    error_log("Error in fetchPurchaseSummary.php: " . $e->getMessage());
    $response['message'] = "An error occurred while fetching the summary data.";
}

// Ensure clean output
ob_clean();
echo json_encode($response); 