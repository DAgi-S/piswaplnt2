<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display in output

// Prevent any unwanted output before JSON
ob_start();

require_once 'api_headers.php';
require_once 'core.php';
require_once 'db_connect.php';

// Debug log function
function debug_log($message) {
    error_log(print_r($message, true));
}

try {
    // Ensure database connection
    if ($connect->connect_error) {
        throw new Exception("Connection failed: " . $connect->connect_error);
    }

    // Sanitize and prepare filter inputs
    $dateRange = isset($_POST['dateRange']) ? $_POST['dateRange'] : '';
    $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : '';
    $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : '';
    $product = isset($_POST['product']) ? $_POST['product'] : '';
    $orderStatus = isset($_POST['orderStatus']) ? $_POST['orderStatus'] : '';

    debug_log("Received filters: " . json_encode([
        'dateRange' => $dateRange,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'product' => $product,
        'orderStatus' => $orderStatus
    ]));

    // Build date filter based on selected range
    $dateFilter = "";
    switch($dateRange) {
        case 'today':
            $dateFilter = "DATE(po.start_date) = CAST(CURRENT_DATE() AS DATE)";
            break;
        case 'yesterday':
            $dateFilter = "DATE(po.start_date) = CAST(DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY) AS DATE)";
            break;
        case 'last7days':
            $dateFilter = "DATE(po.start_date) >= CAST(DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY) AS DATE)";
            break;
        case 'last30days':
            $dateFilter = "DATE(po.start_date) >= CAST(DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) AS DATE)";
            break;
        case 'thisMonth':
            $dateFilter = "EXTRACT(YEAR_MONTH FROM po.start_date) = EXTRACT(YEAR_MONTH FROM CURRENT_DATE())";
            break;
        case 'lastMonth':
            $dateFilter = "EXTRACT(YEAR_MONTH FROM po.start_date) = EXTRACT(YEAR_MONTH FROM DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
            break;
        case 'custom':
            if (!empty($startDate) && !empty($endDate)) {
                $startDate = $connect->real_escape_string($startDate);
                $endDate = $connect->real_escape_string($endDate);
                $dateFilter = "DATE(po.start_date) BETWEEN CAST('$startDate' AS DATE) AND CAST('$endDate' AS DATE)";
            }
            break;
        default:
            $dateFilter = "1=1"; // No date filter
    }

    debug_log("Date filter: " . $dateFilter);

    // Build product filter
    $productFilter = !empty($product) ? "AND po.product_id = " . $connect->real_escape_string($product) : "";

    // Build status filter
    $statusFilter = !empty($orderStatus) ? "AND po.status = '" . $connect->real_escape_string($orderStatus) . "'" : "";

    debug_log("Built filters: " . json_encode([
        'dateFilter' => $dateFilter,
        'productFilter' => $productFilter,
        'statusFilter' => $statusFilter
    ]));

    // Get total orders
    $sql = "SELECT COUNT(*) as total_count FROM production_orders po 
            WHERE $dateFilter $productFilter $statusFilter";
    debug_log("Total orders query: " . $sql);
    $result = $connect->query($sql);
    if (!$result) {
        throw new Exception("Error in total orders query: " . $connect->error . "\nQuery: " . $sql);
    }
    $total = $result->fetch_assoc()['total_count'];

    // Get completed orders
    $sql = "SELECT COUNT(*) as completed_count FROM production_orders po 
            WHERE $dateFilter $productFilter AND po.status = 'completed'";
    debug_log("Completed orders query: " . $sql);
    $result = $connect->query($sql);
    if (!$result) {
        throw new Exception("Error in completed orders query: " . $connect->error . "\nQuery: " . $sql);
    }
    $completed = $result->fetch_assoc()['completed_count'];

    // Get in progress orders
    $sql = "SELECT COUNT(*) as progress_count FROM production_orders po 
            WHERE $dateFilter $productFilter AND po.status = 'in_progress'";
    debug_log("In progress orders query: " . $sql);
    $result = $connect->query($sql);
    if (!$result) {
        throw new Exception("Error in in-progress orders query: " . $connect->error . "\nQuery: " . $sql);
    }
    $in_progress = $result->fetch_assoc()['progress_count'];

    // Get delayed orders - avoiding reserved word 'delayed'
    $sql = "SELECT COUNT(*) as delay_count FROM production_orders po 
            WHERE $dateFilter $productFilter 
            AND (
                (po.status = 'completed' AND po.actual_completion_date > po.expected_completion_date)
                OR (po.status NOT IN ('completed', 'cancelled') 
                    AND po.expected_completion_date < CURRENT_DATE())
            )";
    debug_log("Delayed orders query: " . $sql);
    $result = $connect->query($sql);
    if (!$result) {
        throw new Exception("Error in delayed orders query: " . $connect->error . "\nQuery: " . $sql);
    }
    $delayed = $result->fetch_assoc()['delay_count'];

    $stats = array(
        'total' => intval($total),
        'completed' => intval($completed),
        'in_progress' => intval($in_progress),
        'delayed' => intval($delayed)
    );

    debug_log("Final stats: " . json_encode($stats));

    // Clean output buffer and send JSON response
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json');
    echo json_encode($stats);

} catch (Exception $e) {
    debug_log("Error occurred: " . $e->getMessage());
    
    // Clean output buffer and send error response
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
}

// Close database connection
if (isset($connect)) {
    $connect->close();
}
exit; 