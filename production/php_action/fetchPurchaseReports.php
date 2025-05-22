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
    $sql = "SELECT p.*, s.company_name as supplier_name,
            COALESCE(SUM(pp.amount), 0) as paid_amount,
            (p.grand_total - COALESCE(SUM(pp.amount), 0)) as due_amount
            FROM purchases p
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            LEFT JOIN purchase_payments pp ON p.id = pp.purchase_id
            WHERE p.active = 1 ";

    if($dateCondition) {
        $sql .= "AND $dateCondition ";
    }
    
    $sql .= $supplierCondition . " " . $statusCondition;
    $sql .= " GROUP BY p.id ORDER BY p.purchase_date DESC";

    $result = $connect->query($sql);

    if(!$result) {
        throw new Exception("Error executing query: " . $connect->error);
    }

    while($row = $result->fetch_array(MYSQLI_ASSOC)) {
        $status = ucfirst(str_replace('_', ' ', $row['payment_status']));
        
        $actionButton = '<button class="btn btn-default btn-sm" onclick="viewPurchaseDetails(\''.$row['purchase_number'].'\')">
                            <i class="fa fa-eye"></i>
                        </button>';

        $output['data'][] = array(
            $row['purchase_number'],
            date('Y-m-d', strtotime($row['purchase_date'])),
            $row['supplier_name'],
            $row['sub_total'],
            $row['vat'],
            $row['grand_total'],
            $row['paid_amount'],
            $row['due_amount'],
            $status,
            $actionButton
        );
    }

} catch(Exception $e) {
    error_log("Error in fetchPurchaseReports.php: " . $e->getMessage());
    $output['error'] = true;
    $output['message'] = "An error occurred while fetching the report data.";
}

// Ensure clean output
ob_clean();
echo json_encode($output); 