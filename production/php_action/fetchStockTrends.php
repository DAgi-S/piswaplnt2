<?php
require_once 'core.php';
require_once 'classes/LowStockManager.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'message' => 'Invalid request method'));
    exit();
}

// Validate input parameters
if (!isset($_POST['item_id']) || !isset($_POST['item_type'])) {
    echo json_encode(array('success' => false, 'message' => 'Missing required parameters'));
    exit();
}

$itemId = intval($_POST['item_id']);
$itemType = $_POST['item_type'];

// Initialize low stock manager
$lowStockManager = new LowStockManager($connect);

// Get stock trends
$trends = $lowStockManager->getStockTrends($itemType, $itemId);

if ($trends === false) {
    echo json_encode(array('success' => false, 'message' => 'Failed to fetch stock trends'));
    exit();
}

// Calculate statistics
$reorderPoint = $lowStockManager->calculateReorderPoint($itemType, $itemId);

// Get current stock level
$query = "SELECT current_stock, min_stock_level FROM " . 
         ($itemType == 'raw_material' ? 'raw_materials' : 'products') . 
         " WHERE " . ($itemType == 'raw_material' ? 'id' : 'product_id') . " = ?";

$stmt = $connect->prepare($query);
$stmt->bind_param("i", $itemId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

// Calculate days until stockout based on average daily usage
$avgDailyUsage = 0;
$daysUntilStockout = 'N/A';

if (count($trends) > 0) {
    $totalUsage = 0;
    $days = 0;
    foreach ($trends as $trend) {
        if ($trend['net_change'] < 0) {
            $totalUsage += abs($trend['net_change']);
            $days++;
        }
    }
    
    if ($days > 0) {
        $avgDailyUsage = $totalUsage / $days;
        if ($avgDailyUsage > 0) {
            $daysUntilStockout = floor($result['current_stock'] / $avgDailyUsage);
        }
    }
}

// Prepare response
$response = array(
    'success' => true,
    'data' => $trends,
    'statistics' => array(
        'average_daily_usage' => $avgDailyUsage,
        'recommended_reorder_point' => $reorderPoint,
        'days_until_stockout' => $daysUntilStockout,
        'current_stock' => $result['current_stock'],
        'minimum_stock' => $result['min_stock_level']
    )
);

echo json_encode($response); 