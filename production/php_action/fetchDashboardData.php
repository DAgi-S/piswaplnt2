<?php
header('Content-Type: application/json');
require_once 'db_connect.php';

$response = array('success' => false, 'data' => array());

try {
    // Get total orders (combining production and sales orders)
    $orders_sql = "SELECT 
        (SELECT COUNT(*) FROM production_orders WHERE status != 'cancelled') + 
        (SELECT COUNT(*) FROM sales_orders WHERE order_status != 'cancelled') as total_orders";
    $orders_result = $connect->query($orders_sql);
    $orders_data = $orders_result->fetch_assoc();
    
    // Get total revenue from sales orders
    $revenue_sql = "SELECT 
        COALESCE(SUM(total_amount), 0) as total_revenue 
        FROM sales_orders 
        WHERE order_status != 'cancelled'";
    $revenue_result = $connect->query($revenue_sql);
    $revenue_data = $revenue_result->fetch_assoc();
    
    // Get total active products
    $products_sql = "SELECT COUNT(*) as total_products 
        FROM production_products 
        WHERE status = 'active'";
    $products_result = $connect->query($products_sql);
    $products_data = $products_result->fetch_assoc();
    
    // Get total active clients
    $clients_sql = "SELECT COUNT(*) as total_clients 
        FROM clients 
        WHERE status = 'active'";
    $clients_result = $connect->query($clients_sql);
    $clients_data = $clients_result->fetch_assoc();
    
    // Get recent reports count
    $reports_sql = "SELECT COUNT(*) as total_reports 
        FROM reports 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $reports_result = $connect->query($reports_sql);
    $reports_data = $reports_result->fetch_assoc();
    
    $response['data'] = array(
        'total_orders' => (int)$orders_data['total_orders'],
        'total_revenue' => number_format((float)$revenue_data['total_revenue'], 2),
        'total_products' => (int)$products_data['total_products'],
        'total_clients' => (int)$clients_data['total_clients'],
        'recent_reports' => (int)$reports_data['total_reports']
    );
    
    $response['success'] = true;
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?> 