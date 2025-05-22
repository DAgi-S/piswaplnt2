<?php
require_once 'core.php';
require_once 'classes/NotificationArchiveManager.php';

// Check if user has admin privileges
if (!isset($_SESSION['userRole']) || $_SESSION['userRole'] !== 'admin') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

try {
    $archiveManager = new NotificationArchiveManager();
    $dateRange = $_GET['dateRange'] ?? '30d';

    // Get notification types distribution
    $sql = "SELECT 
                type,
                COUNT(*) as count
            FROM notification_archives
            WHERE " . $archiveManager->getDateCondition($dateRange) . "
            GROUP BY type
            ORDER BY count DESC";
    
    $result = $connect->query($sql);
    $typeData = [
        'labels' => [],
        'values' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        $typeData['labels'][] = ucfirst($row['type']);
        $typeData['values'][] = (int)$row['count'];
    }

    // Get delivery success rate by channel
    $sql = "SELECT 
                channel,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered
            FROM notification_archives
            WHERE " . $archiveManager->getDateCondition($dateRange) . "
            GROUP BY channel";
    
    $result = $connect->query($sql);
    $deliveryData = [
        'labels' => [],
        'values' => []
    ];
    
    while ($row = $result->fetch_assoc()) {
        $deliveryData['labels'][] = ucfirst($row['channel']);
        $successRate = ($row['total'] > 0) 
            ? round(($row['delivered'] / $row['total']) * 100, 2)
            : 0;
        $deliveryData['values'][] = $successRate;
    }

    // Get additional analytics
    $sql = "SELECT 
                COUNT(DISTINCT DATE(created_at)) as total_days,
                COUNT(*) / COUNT(DISTINCT DATE(created_at)) as avg_daily,
                AVG(TIMESTAMPDIFF(SECOND, created_at, delivered_at)) as avg_delivery_time,
                COUNT(DISTINCT type) as unique_types,
                COUNT(DISTINCT channel) as unique_channels,
                SUM(CASE WHEN JSON_EXTRACT(metadata, '$.attempts') > 1 THEN 1 ELSE 0 END) as retried_count
            FROM notification_archives
            WHERE " . $archiveManager->getDateCondition($dateRange) . "
            AND status = 'delivered'";
    
    $result = $connect->query($sql);
    $additionalStats = $result->fetch_assoc();

    // Format average delivery time
    $avgDeliverySeconds = $additionalStats['avg_delivery_time'] ?? 0;
    $additionalStats['avg_delivery_time_formatted'] = $avgDeliverySeconds > 0 
        ? gmdate("H:i:s", round($avgDeliverySeconds))
        : '00:00:00';

    echo json_encode([
        'success' => true,
        'typeData' => $typeData,
        'deliveryData' => $deliveryData,
        'additionalStats' => $additionalStats
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}