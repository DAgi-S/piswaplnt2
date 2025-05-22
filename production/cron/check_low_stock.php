<?php
require_once '../php_action/core.php';
require_once '../php_action/classes/LowStockManager.php';

// Initialize the low stock manager
$lowStockManager = new LowStockManager($connect);

// Update thresholds based on historical data
$lowStockManager->updateThresholds();

// Check for low stock items and send alerts
$lowStockManager->sendLowStockAlerts();

// Log the cron execution
$logMessage = date('Y-m-d H:i:s') . " - Low stock check completed\n";
file_put_contents(__DIR__ . '/cron.log', $logMessage, FILE_APPEND); 