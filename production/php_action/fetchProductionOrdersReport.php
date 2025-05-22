<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display in output

// Prevent any unwanted output before JSON
ob_start();

require_once 'api_headers.php';
require_once 'core.php';
require_once 'db_connect.php';

try {
    // Ensure database connection
    if ($connect->connect_error) {
        throw new Exception("Connection failed: " . $connect->connect_error);
    }

    // Get and sanitize inputs
    $dateRange = isset($_POST['dateRange']) ? $_POST['dateRange'] : 'today';
    $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : '';
    $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : '';
    $product = isset($_POST['product']) ? $_POST['product'] : '';
    $orderStatus = isset($_POST['orderStatus']) ? $_POST['orderStatus'] : '';

    // Build date filter
    $dateField = 'po.start_date';
    $dateFilter = "1=1";
    switch($dateRange) {
        case 'today':
            $dateFilter = "DATE($dateField) = CURRENT_DATE()";
            break;
        case 'yesterday':
            $dateFilter = "DATE($dateField) = DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)";
            break;
        case 'last7days':
            $dateFilter = "DATE($dateField) >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)";
            break;
        case 'last30days':
            $dateFilter = "DATE($dateField) >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
            break;
        case 'thisMonth':
            $dateFilter = "EXTRACT(YEAR_MONTH FROM $dateField) = EXTRACT(YEAR_MONTH FROM CURRENT_DATE())";
            break;
        case 'lastMonth':
            $dateFilter = "EXTRACT(YEAR_MONTH FROM $dateField) = EXTRACT(YEAR_MONTH FROM DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
            break;
        case 'custom':
            if (!empty($startDate) && !empty($endDate)) {
                $startDate = $connect->real_escape_string($startDate);
                $endDate = $connect->real_escape_string($endDate);
                $dateFilter = "DATE($dateField) BETWEEN '$startDate' AND '$endDate'";
            }
            break;
    }

    // Build product filter
    $productFilter = !empty($product) ? "AND po.product_id = " . $connect->real_escape_string($product) : "";

    // Build status filter
    $statusFilter = !empty($orderStatus) ? "AND po.status = '" . $connect->real_escape_string($orderStatus) . "'" : "";

    // Main query
    $sql = "SELECT 
                po.order_number,
                p.product_code,
                p.name as product_name,
                po.target_quantity,
                po.completed_quantity,
                po.start_date,
                po.expected_completion_date,
                po.actual_completion_date,
                po.status,
                CASE 
                    WHEN po.status = 'completed' AND po.actual_completion_date > po.expected_completion_date THEN 'delayed'
                    WHEN po.status = 'in_progress' AND CURRENT_DATE() > po.expected_completion_date THEN 'delayed'
                    ELSE po.status
                END as effective_status,
                CASE
                    WHEN po.status = 'completed' THEN 
                        ROUND((po.completed_quantity / po.target_quantity) * 100 -
                        (GREATEST(DATEDIFF(po.actual_completion_date, po.expected_completion_date), 0) * 5), 2)
                    ELSE 
                        ROUND((COALESCE(po.completed_quantity, 0) / po.target_quantity) * 100, 2)
                END as efficiency
            FROM production_orders po
            LEFT JOIN production_products p ON po.product_id = p.id
            WHERE $dateFilter
            $productFilter
            $statusFilter
            ORDER BY po.order_number DESC";

    $result = $connect->query($sql);
    if (!$result) {
        throw new Exception("Error in query: " . $connect->error);
    }

    $data = array();
    while ($row = $result->fetch_assoc()) {
        // Format dates
        $row['start_date'] = date('Y-m-d', strtotime($row['start_date']));
        $row['expected_completion'] = date('Y-m-d', strtotime($row['expected_completion_date']));
        $row['actual_completion'] = $row['actual_completion_date'] ? 
            date('Y-m-d', strtotime($row['actual_completion_date'])) : 'Not Completed';

        // Format numbers
        $row['target_quantity'] = number_format($row['target_quantity']);
        $row['completed_quantity'] = number_format($row['completed_quantity'] ?? 0);
        
        // Add efficiency class
        $row['efficiency_class'] = $row['efficiency'] >= 90 ? 'text-success' : 
                                ($row['efficiency'] >= 70 ? 'text-warning' : 'text-danger');

        $data[] = $row;
    }

    // Clean output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    // Clean output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Send error response
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Close database connection
if (isset($connect)) {
    $connect->close();
}
exit; 