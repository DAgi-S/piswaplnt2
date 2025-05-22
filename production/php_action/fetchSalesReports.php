<?php
require_once 'core.php';

header('Content-Type: application/json');

$output = array('data' => array());

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

    // Build the complete query with a subquery for payment totals
    $sql = "SELECT 
                s.id,
                s.order_number,
                s.order_date,
                s.subtotal,
                s.tax_amount,
                s.total_amount,
                s.payment_status,
                c.company_name as client_name,
                COALESCE(p.total_paid, 0) as received_amount,
                (s.total_amount - COALESCE(p.total_paid, 0)) as outstanding_amount
            FROM sales_orders s
            LEFT JOIN clients c ON s.client_id = c.id
            LEFT JOIN (
                SELECT 
                    sales_order_id,
                    reference_number,
                    notes,
                    SUM(amount) as total_paid
                FROM sales_payments
                GROUP BY sales_order_id
            ) p ON s.id = p.sales_order_id
            WHERE 1=1 ";

    // Add date condition with proper escaping
    if($dateCondition) {
        $sql .= "AND ($dateCondition) ";
    }
    
    // Add other conditions
    if($clientCondition) {
        $sql .= $clientCondition . " ";
    }
    
    if($statusCondition) {
        $sql .= $statusCondition . " ";
    }
    
    // Order by date
    $sql .= "ORDER BY s.order_date DESC";

    // Log the query for debugging
    error_log("Sales Report Query: " . $sql);

    $result = $connect->query($sql);

    if(!$result) {
        throw new Exception("Error executing query: " . $connect->error);
    }

    $output['data'] = array();
    while($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $status = ucfirst(str_replace('_', ' ', $row['payment_status']));
        
        $actionButton = '<div class="btn-group">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Action <span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right">
                <li><a href="javascript:void(0);" onclick="viewSaleDetails(\''.$row['order_number'].'\')"><i class="fa fa-eye"></i> View Details</a></li>
                <li><a href="javascript:void(0);" onclick="printInvoice(\''.$row['order_number'].'\')"><i class="fa fa-print"></i> Print Invoice</a></li>
            </ul>
        </div>';

        $output['data'][] = array(
            $row['order_number'],
            date('Y-m-d', strtotime($row['order_date'])),
            $row['client_name'],
            floatval($row['subtotal']),
            floatval($row['tax_amount']),
            floatval($row['total_amount']),
            floatval($row['received_amount']),
            floatval($row['outstanding_amount']),
            $status,
            $actionButton
        );
    }

} catch(Exception $e) {
    error_log("Error in fetchSalesReports.php: " . $e->getMessage());
    $output['error'] = true;
    $output['message'] = "An error occurred while fetching the report data.";
}

// Ensure clean output
ob_clean();
echo json_encode($output); 