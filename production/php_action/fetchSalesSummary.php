<?php
require_once 'core.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'total_sales' => 0,
    'total_amount' => 0,
    'received_amount' => 0,
    'outstanding_amount' => 0
);

try {
    // Build date range condition
    $dateCondition = "";
    if(isset($_POST['dateRange'])) {
        switch($_POST['dateRange']) {
            case 'today':
                $dateCondition = "DATE(s.order_date) = CURDATE()";
                break;
            case 'yesterday':
                $dateCondition = "DATE(s.order_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                break;
            case 'last7days':
                $dateCondition = "DATE(s.order_date) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'last30days':
                $dateCondition = "DATE(s.order_date) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            case 'thisMonth':
                $dateCondition = "MONTH(s.order_date) = MONTH(CURDATE()) AND YEAR(s.order_date) = YEAR(CURDATE())";
                break;
            case 'lastMonth':
                $dateCondition = "DATE(s.order_date) >= DATE_SUB(DATE_SUB(CURDATE(), INTERVAL DAYOFMONTH(CURDATE())-1 DAY), INTERVAL 1 MONTH) 
                                AND DATE(s.order_date) <= LAST_DAY(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
                break;
            case 'custom':
                if(isset($_POST['startDate']) && isset($_POST['endDate'])) {
                    $dateCondition = "DATE(s.order_date) BETWEEN '{$_POST['startDate']}' AND '{$_POST['endDate']}'";
                }
                break;
        }
    }

    // Build client condition
    $clientCondition = "";
    if(isset($_POST['client']) && !empty($_POST['client'])) {
        $clientId = $_POST['client'];
        $clientCondition = "AND s.client_id = $clientId";
    }

    // Build payment status condition
    $statusCondition = "";
    if(isset($_POST['paymentStatus']) && !empty($_POST['paymentStatus'])) {
        $status = $_POST['paymentStatus'];
        $statusCondition = "AND s.payment_status = '$status'";
    }

    // Build the complete query
    $sql = "SELECT 
            COUNT(DISTINCT s.id) as total_sales,
            SUM(s.total_amount) as total_amount,
            COALESCE(SUM(sp.amount), 0) as received_amount
            FROM sales_orders s
            LEFT JOIN sales_payments sp ON s.id = sp.sales_order_id
            WHERE s.order_status != 'cancelled' ";

    if($dateCondition) {
        $sql .= "AND $dateCondition ";
    }
    
    $sql .= $clientCondition . " " . $statusCondition;

    $result = $connect->query($sql);

    if(!$result) {
        throw new Exception("Error executing query: " . $connect->error);
    }

    $row = $result->fetch_array(MYSQLI_ASSOC);
    
    $response['success'] = true;
    $response['total_sales'] = $row['total_sales'];
    $response['total_amount'] = $row['total_amount'] ?: 0;
    $response['received_amount'] = $row['received_amount'] ?: 0;
    $response['outstanding_amount'] = $response['total_amount'] - $response['received_amount'];

} catch(Exception $e) {
    error_log("Error in fetchSalesSummary.php: " . $e->getMessage());
    $response['message'] = "An error occurred while fetching the summary data.";
}

// Ensure clean output
ob_clean();
echo json_encode($response); 