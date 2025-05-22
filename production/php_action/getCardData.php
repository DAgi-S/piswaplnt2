<?php
require_once 'core.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode(array('success' => false, 'message' => 'User not logged in'));
    exit();
}

// Get the card key from the request
$cardKey = isset($_GET['card']) ? $_GET['card'] : null;

if (!$cardKey) {
    echo json_encode(array('success' => false, 'message' => 'No card specified'));
    exit();
}

try {
    $data = array();
    
    // Fetch data based on card key
    switch ($cardKey) {
        case 'total_products':
            $query = "SELECT COUNT(*) as count FROM product WHERE status = 1";
            $result = $connect->query($query);
            $row = $result->fetch_assoc();
            $data['value'] = number_format($row['count']);
            
            // Get trend (products added in last 7 days)
            $trendQuery = "SELECT COUNT(*) as count FROM product WHERE status = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $trendResult = $connect->query($trendQuery);
            $trendRow = $trendResult->fetch_assoc();
            $data['trend'] = array(
                'value' => '+' . number_format($trendRow['count']),
                'label' => 'New this week',
                'direction' => 'up'
            );
            break;
            
        case 'low_stock':
            $query = "SELECT COUNT(*) as count FROM product WHERE quantity <= 10 AND status = 1";
            $result = $connect->query($query);
            $row = $result->fetch_assoc();
            $data['value'] = number_format($row['count']);
            
            // Get trend (change in low stock items in last 24 hours)
            $trendQuery = "SELECT COUNT(*) as count FROM product WHERE quantity <= 10 AND status = 1 AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $trendResult = $connect->query($trendQuery);
            $trendRow = $trendResult->fetch_assoc();
            $data['trend'] = array(
                'value' => number_format($trendRow['count']),
                'label' => 'Changed today',
                'direction' => $trendRow['count'] > 0 ? 'up' : 'down'
            );
            break;
            
        case 'total_orders':
            $query = "SELECT COUNT(*) as count FROM orders WHERE order_status = 1";
            $result = $connect->query($query);
            $row = $result->fetch_assoc();
            $data['value'] = number_format($row['count']);
            
            // Get trend (orders in last 24 hours)
            $trendQuery = "SELECT COUNT(*) as count FROM orders WHERE order_status = 1 AND order_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $trendResult = $connect->query($trendQuery);
            $trendRow = $trendResult->fetch_assoc();
            $data['trend'] = array(
                'value' => '+' . number_format($trendRow['count']),
                'label' => 'Today',
                'direction' => 'up'
            );
            break;
            
        case 'total_sales':
            $query = "SELECT SUM(grand_total) as total FROM orders WHERE order_status = 1";
            $result = $connect->query($query);
            $row = $result->fetch_assoc();
            $data['value'] = '₱' . number_format($row['total'] ?? 0, 2);
            
            // Get trend (sales in last 24 hours)
            $trendQuery = "SELECT SUM(grand_total) as total FROM orders WHERE order_status = 1 AND order_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $trendResult = $connect->query($trendQuery);
            $trendRow = $trendResult->fetch_assoc();
            $data['trend'] = array(
                'value' => '₱' . number_format($trendRow['total'] ?? 0, 2),
                'label' => 'Today',
                'direction' => 'up'
            );
            break;
            
        case 'recent_orders':
            $query = "SELECT COUNT(*) as count FROM orders WHERE order_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $result = $connect->query($query);
            $row = $result->fetch_assoc();
            $data['value'] = number_format($row['count']);
            
            // Get trend (comparison with previous 24 hours)
            $trendQuery = "SELECT COUNT(*) as count FROM orders WHERE order_date >= DATE_SUB(NOW(), INTERVAL 48 HOUR) AND order_date < DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            $trendResult = $connect->query($trendQuery);
            $trendRow = $trendResult->fetch_assoc();
            $diff = $row['count'] - $trendRow['count'];
            $data['trend'] = array(
                'value' => ($diff >= 0 ? '+' : '') . number_format($diff),
                'label' => 'vs yesterday',
                'direction' => $diff >= 0 ? 'up' : 'down'
            );
            break;
            
        case 'pending_orders':
            $query = "SELECT COUNT(*) as count FROM orders WHERE order_status = 0";
            $result = $connect->query($query);
            $row = $result->fetch_assoc();
            $data['value'] = number_format($row['count']);
            
            // Get trend (change in last hour)
            $trendQuery = "SELECT COUNT(*) as count FROM orders WHERE order_status = 0 AND order_date >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            $trendResult = $connect->query($trendQuery);
            $trendRow = $trendResult->fetch_assoc();
            $data['trend'] = array(
                'value' => '+' . number_format($trendRow['count']),
                'label' => 'Last hour',
                'direction' => 'up'
            );
            break;
            
        case 'total_categories':
            $query = "SELECT COUNT(*) as count FROM categories WHERE categories_status = 1";
            $result = $connect->query($query);
            $row = $result->fetch_assoc();
            $data['value'] = number_format($row['count']);
            
            // Get trend (categories added in last 30 days)
            $trendQuery = "SELECT COUNT(*) as count FROM categories WHERE categories_status = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $trendResult = $connect->query($trendQuery);
            $trendRow = $trendResult->fetch_assoc();
            $data['trend'] = array(
                'value' => '+' . number_format($trendRow['count']),
                'label' => 'This month',
                'direction' => 'up'
            );
            break;
            
        case 'total_brands':
            $query = "SELECT COUNT(*) as count FROM brands WHERE brand_status = 1";
            $result = $connect->query($query);
            $row = $result->fetch_assoc();
            $data['value'] = number_format($row['count']);
            
            // Get trend (brands added in last 30 days)
            $trendQuery = "SELECT COUNT(*) as count FROM brands WHERE brand_status = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $trendResult = $connect->query($trendQuery);
            $trendRow = $trendResult->fetch_assoc();
            $data['trend'] = array(
                'value' => '+' . number_format($trendRow['count']),
                'label' => 'This month',
                'direction' => 'up'
            );
            break;
            
        default:
            echo json_encode(array('success' => false, 'message' => 'Invalid card key'));
            exit();
    }
    
    echo json_encode(array('success' => true, 'data' => $data));
    
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
}

$connect->close();
?> 