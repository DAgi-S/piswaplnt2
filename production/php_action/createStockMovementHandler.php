<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'stock_movement_updates.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false, 
        'messages' => 'Invalid request method'
    ]);
    exit();
}

try {
    // Initialize stock movement manager
    $stockManager = new StockMovementManager($connect, $_SESSION['userId']);

    // Prepare movement data
    $movementData = [
        'item_id' => $_POST['item_id'],
        'quantity' => $_POST['quantity'],
        'movement_type' => $_POST['movement_type'],
        'reference_type' => $_POST['reference_type'],
        'reference_id' => $_POST['reference_id'],
        'source_type' => $_POST['source_type'] ?? ($_POST['movement_type'] === 'in' ? 'supplier' : 'warehouse'),
        'source_id' => $_POST['source_id'] ?? null,
        'destination_type' => $_POST['destination_type'] ?? ($_POST['movement_type'] === 'in' ? 'warehouse' : 'customer'),
        'destination_id' => $_POST['destination_id'] ?? null,
        'notes' => $_POST['notes'] ?? null,
        'warehouse_id' => $_POST['movement_type'] === 'in' ? 
            $_POST['destination_id'] : 
            $_POST['source_id']
    ];

    // Create stock movement
    $result = $stockManager->createStockMovement($movementData);

    // Return response
    echo json_encode($result);

} catch (Exception $e) {
    error_log("Error in createStockMovementHandler.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'messages' => 'Error: ' . $e->getMessage()
    ]);
} 