<?php
/**
 * Create Production Order Controller
 * 
 * This file handles the creation of new production orders
 * using the ProductionManager class
 */

// Start output buffering to prevent any unwanted output
ob_start();

// Set error handling to catch all types of errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Database connection
require_once 'db_connect.php';

// Include core functions and getCurrentUserId
require_once 'core.php';

// Include the ProductionManager class
require_once 'classes/ProductionManager.php';

// Include required files
require_once 'production_middleware.php';

// Initialize production middleware
$productionMiddleware = new ProductionMiddleware($connect);

// Validate create permission
if (!$productionMiddleware->validateAccess('production.order.create')) {
    echo json_encode(array(
        'success' => false, 
        'messages' => 'Permission denied: Cannot create production orders'
    ));
    exit();
}

// Set default response
$response = array(
    'success' => false,
    'messages' => 'Initialization'
);

try {
    // Check if the request is POST
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        throw new Exception('Invalid request method. Only POST is allowed.');
    }
    
    // Check for CSRF token if needed
    if (isset($_POST['csrf_token'])) {
        // Validate CSRF token - this is just a placeholder, implement actual validation
        error_log("CSRF token received: " . $_POST['csrf_token']);
    }
    
    // Log all input data for debugging
    error_log("Production Order Creation - Raw POST data: " . json_encode($_POST, JSON_UNESCAPED_UNICODE));
    
    // Get and validate form data with strict type checking - matching the exact field names from the form
    $product_id = isset($_POST['product']) ? intval($_POST['product']) : 0;
    $target_qty = isset($_POST['targetQuantity']) ? floatval($_POST['targetQuantity']) : 0;
    $order_date = isset($_POST['startDate']) ? $_POST['startDate'] : date('Y-m-d');
    $expected_completion = isset($_POST['completionDate']) ? $_POST['completionDate'] : null;
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'draft';
    $warehouse_id = isset($_POST['warehouse']) ? intval($_POST['warehouse']) : 0;
    
    // Get order number if provided
    $order_number = isset($_POST['orderNumber']) && !empty($_POST['orderNumber']) 
        ? trim($_POST['orderNumber']) 
        : 'PO-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Debug form inputs
    error_log("Validated form inputs: " . json_encode([
        'product_id' => $product_id,
        'target_qty' => $target_qty,
        'order_date' => $order_date,
        'expected_completion' => $expected_completion,
        'status' => $status,
        'warehouse_id' => $warehouse_id,
        'order_number' => $order_number
    ]));
    
    // Get user ID using our helper function
    $created_by = getCurrentUserId();
    error_log("Creating production order with user ID: " . $created_by);
    
    // Enhanced validation with more descriptive error messages
    if ($product_id <= 0) {
        throw new Exception("Valid product selection is required. Please select a product.");
    }
    
    // Validate target quantity more strictly
    if ($target_qty <= 0) {
        throw new Exception("Target quantity must be greater than zero. Please enter a valid production quantity.");
    }
    
    // Validate allowed status values
    $allowed_statuses = array('draft', 'pending', 'in_progress', 'completed', 'cancelled');
    if (!in_array($status, $allowed_statuses)) {
        throw new Exception("Invalid status value: '$status'. Allowed values are: " . implode(", ", $allowed_statuses));
    }
    
    // Validate dates if provided
    if (!empty($order_date) && !strtotime($order_date)) {
        throw new Exception("Invalid start date format. Please use YYYY-MM-DD format.");
    }
    
    if (!empty($expected_completion) && !strtotime($expected_completion)) {
        throw new Exception("Invalid completion date format. Please use YYYY-MM-DD format.");
    }
    
    // Initialize ProductionManager
    $productionManager = new ProductionManager($connect);
    
    // Create the production order
    $orderData = array(
        'order_number' => $order_number,
        'product_id' => $product_id,
        'target_quantity' => $target_qty,
        'start_date' => $order_date,
        'expected_completion_date' => $expected_completion,
        'notes' => $notes,
        'status' => $status,
        'warehouse_id' => $warehouse_id,
        'created_by' => $created_by
    );
    
    $result = $productionManager->createProductionOrder($orderData);
    
    // Handle the result
    if ($result['status']) {
        $response['success'] = true;
        $response['messages'] = 'Production order created successfully';
        $response['order_id'] = isset($result['order_id']) ? $result['order_id'] : null;
        
        // For backward compatibility
        if (isset($result['data']) && isset($result['data']['order_id'])) {
            $response['order_id'] = $result['data']['order_id'];
        }
        
        // Calculate requirements automatically if needed
        if (isset($_POST['calculate_requirements']) && $_POST['calculate_requirements'] == 'true') {
            $requirementsResult = $productionManager->calculateProductionRequirements(
                $order_number, 
                $product_id, 
                $target_qty
            );
            
            $response['requirements_calculated'] = $requirementsResult['status'];
            if (!$requirementsResult['status']) {
                $response['requirements_message'] = $requirementsResult['message'];
            }
        }
        
    } else {
        throw new Exception($result['message'] ?? 'Unknown error occurred while creating production order');
    }
    
} catch (Exception $e) {
    // Catch all exceptions and provide detailed error information
    $response['success'] = false;
    $response['messages'] = $e->getMessage();
    
    // Add debug information in development environment
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        $response['debug'] = [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
    
    // Log the error
    error_log("Production Order Creation Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
} finally {
    // Restore default error handler
    restore_error_handler();
    
    // Prepare for output
    header('Content-Type: application/json');
    
    // Completely clean any output before our JSON response
    // This ensures no PHP notices, warnings or other content affects our JSON
    ob_end_clean();
    
    // Start a new output buffer just for our JSON
    ob_start();
    
    // Ensure we're sending valid JSON
    try {
        // Encode with all the safety options
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    } catch (Exception $e) {
        // If JSON encoding fails, send a simple error message
        echo json_encode(['success' => false, 'messages' => 'Error encoding response: ' . $e->getMessage()]);
    }
    
    // Get the size of the JSON response
    $size = ob_get_length();
    
    // Set the Content-Length header for better HTTP compliance
    header("Content-Length: $size");
    
    // Flush the buffer and end the script
    ob_end_flush();
    exit();
}
?> 