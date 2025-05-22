<?php
require_once 'core.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userId'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit();
}

// Get limit parameter, default to 10
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

// Get is_read filter
$isRead = isset($_GET['is_read']) ? (bool)$_GET['is_read'] : false;

// Sample notifications for testing
$notifications = [
    [
        'notification_id' => 1,
        'type' => 'inventory',
        'title' => 'Low Stock Alert',
        'message' => 'Printer Paper (SKU: PP-001) is running low. Current stock: 45 units',
        'is_read' => false,
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
    ],
    [
        'notification_id' => 2,
        'type' => 'order',
        'title' => 'New Order Received',
        'message' => 'Order #ORD-2024-001 has been placed by Customer ABC',
        'is_read' => false,
        'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
    ],
    [
        'notification_id' => 3,
        'type' => 'system',
        'title' => 'System Update',
        'message' => 'System maintenance scheduled for tomorrow at 02:00 AM',
        'is_read' => true,
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
    ]
];

// Filter notifications based on is_read parameter
if (isset($_GET['is_read'])) {
    $notifications = array_filter($notifications, function($notification) use ($isRead) {
        return $notification['is_read'] === $isRead;
    });
}

// Limit the number of notifications
$notifications = array_slice($notifications, 0, $limit);

echo json_encode([
    'success' => true,
    'notifications' => array_values($notifications)
]); 