<?php
/**
 * Get Production Orders Controller
 * 
 * This file handles retrieving a list of production orders with optional filtering
 * using the ProductionManager class
 */

// Include database connection
require_once 'db_connect.php';

// Include the ProductionManager class
require_once 'classes/ProductionManager.php';

// Set header to JSON
header('Content-Type: application/json');

// Initialize response array
$response = array();
$response['success'] = false;
$response['messages'] = array();

try {
    // Initialize ProductionManager
    $productionManager = new ProductionManager($connect);
    
    // Initialize filters
    $filters = array();
    
    // Handle status filter
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    
    // Handle date range filters
    if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
        $filters['start_date'] = $_GET['start_date'];
    }
    
    if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
        $filters['end_date'] = $_GET['end_date'];
    }
    
    // Handle product filter
    if (isset($_GET['product_id']) && !empty($_GET['product_id'])) {
        $filters['product_id'] = $_GET['product_id'];
    }
    
    // Handle search term
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $filters['search'] = $_GET['search'];
    }
    
    // Pagination parameters
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    
    // Ensure page is at least 1
    if ($page < 1) {
        $page = 1;
    }
    
    // Calculate offset
    $offset = ($page - 1) * $limit;
    
    // Get total count for pagination
    $totalRecords = $productionManager->getProductionOrdersCount($filters);
    
    // Get production orders with optional filters and pagination
    $orders = $productionManager->getProductionOrders($filters, $limit, $offset);
    
    // Calculate pagination values
    $totalPages = ceil($totalRecords / $limit);
    
    // Set response data
    $response['success'] = true;
    $response['orders'] = $orders;
    $response['pagination'] = array(
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalRecords' => $totalRecords,
        'recordsPerPage' => $limit
    );
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'][] = "Error: " . $e->getMessage();
}

// Output JSON response
echo json_encode($response);
?> 