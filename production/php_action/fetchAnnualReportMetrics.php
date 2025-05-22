<?php
require_once 'core.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

function getYearRange() {
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    return [
        'start' => "$year-01-01",
        'end' => "$year-12-31"
    ];
}

try {
    $response = array();
    $date_range = getYearRange();

    // Production Products Count - Added status condition
    $sql = "SELECT 
                COUNT(*) as total_products,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_products,
                COALESCE(SUM(CASE WHEN status = 'active' THEN current_stock ELSE 0 END), 0) as total_stock,
                COALESCE(SUM(CASE WHEN status = 'active' THEN current_stock * production_cost ELSE 0 END), 0) as total_stock_value
            FROM production_products";
    $result = $connect->query($sql);
    $products = $result->fetch_assoc();
    $response['production_products'] = $products;

    // Raw Materials Count and Value - Added status condition
    $sql = "SELECT 
                COUNT(*) as total_materials,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_materials,
                COALESCE(SUM(CASE WHEN status = 'active' THEN current_stock ELSE 0 END), 0) as total_stock,
                COALESCE(SUM(CASE WHEN status = 'active' THEN current_stock * cost_per_unit ELSE 0 END), 0) as total_stock_value
            FROM raw_materials";
    $result = $connect->query($sql);
    $materials = $result->fetch_assoc();
    $response['raw_materials'] = $materials;

    // Quality Rate for the Year - Added JOIN with production_orders
    $sql = "SELECT 
                COUNT(DISTINCT qc.id) as total_checks,
                COUNT(DISTINCT CASE WHEN qc.status = 'passed' THEN qc.id END) as passed_checks,
                COALESCE(SUM(qc.quantity_checked), 0) as total_quantity_checked,
                COALESCE(SUM(CASE WHEN qc.status = 'passed' THEN qc.quantity_passed ELSE 0 END), 0) as total_quantity_passed
            FROM quality_control qc
            INNER JOIN production_orders po ON qc.production_order_id = po.id
            WHERE qc.inspection_date BETWEEN ? AND ?
            AND po.status != 'cancelled'";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('ss', $date_range['start'], $date_range['end']);
    $stmt->execute();
    $quality = $stmt->get_result()->fetch_assoc();
    
    // Calculate quality rate based on quantity
    $response['quality_rate'] = $quality['total_quantity_checked'] > 0 
        ? round(($quality['total_quantity_passed'] / $quality['total_quantity_checked']) * 100, 2)
        : 0;
    $response['quality'] = $quality;

    // Sales Orders with Status Counts - Added client JOIN and active status check
    $sql = "SELECT 
                COUNT(DISTINCT so.id) as total_orders,
                COUNT(DISTINCT CASE WHEN so.order_status = 'completed' THEN so.id END) as completed_orders,
                COUNT(DISTINCT CASE WHEN so.order_status = 'pending' THEN so.id END) as pending_orders,
                COALESCE(SUM(so.total_amount), 0) as total_amount,
                COALESCE(SUM(so.tax_amount), 0) as total_vat,
                COALESCE(SUM(so.paid_amount), 0) as total_paid,
                COALESCE(SUM(so.balance), 0) as total_balance
            FROM sales_orders so
            INNER JOIN clients c ON so.client_id = c.id
            WHERE so.order_date BETWEEN ? AND ?
            AND c.status = 1
            AND so.order_status != 'cancelled'";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('ss', $date_range['start'], $date_range['end']);
    $stmt->execute();
    $sales = $stmt->get_result()->fetch_assoc();
    $response['sales'] = $sales;

    // Purchases with Payment Status - Added supplier JOIN and active status check
    $sql = "SELECT 
                COUNT(DISTINCT p.id) as total_orders,
                COALESCE(SUM(p.grand_total), 0) as total_amount,
                COALESCE(SUM(p.vat_amount), 0) as total_vat,
                COALESCE(SUM(p.paid_amount), 0) as total_paid,
                COUNT(DISTINCT CASE WHEN p.payment_status = 'Paid' THEN p.id END) as paid_orders,
                COUNT(DISTINCT CASE WHEN p.payment_status = 'Unpaid' THEN p.id END) as unpaid_orders
            FROM purchases p
            INNER JOIN suppliers s ON p.supplier_id = s.id
            WHERE p.purchase_date BETWEEN ? AND ?
            AND s.active = 1
            AND p.status = 1";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('ss', $date_range['start'], $date_range['end']);
    $stmt->execute();
    $purchases = $stmt->get_result()->fetch_assoc();
    $response['purchases'] = $purchases;

    // Production Orders Status - Added product JOIN and non-cancelled status check
    $sql = "SELECT 
                COUNT(DISTINCT po.id) as total_orders,
                COUNT(DISTINCT CASE WHEN po.status = 'completed' THEN po.id END) as completed_orders,
                COUNT(DISTINCT CASE WHEN po.status = 'inprogress' THEN po.id END) as in_progress_orders,
                COALESCE(SUM(po.target_quantity), 0) as total_target,
                COALESCE(SUM(po.completed_quantity), 0) as total_completed
            FROM production_orders po
            INNER JOIN production_products pp ON po.product_id = pp.id
            WHERE po.start_date BETWEEN ? AND ?
            AND pp.status = 'active'
            AND po.status != 'cancelled'";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('ss', $date_range['start'], $date_range['end']);
    $stmt->execute();
    $production = $stmt->get_result()->fetch_assoc();
    $response['production'] = $production;

    // Calculate Net Values - Using COALESCE to handle NULL values
    $response['net_revenue'] = ($sales['total_amount'] ?? 0) - ($purchases['total_amount'] ?? 0);
    $response['net_vat'] = ($sales['total_vat'] ?? 0) - ($purchases['total_vat'] ?? 0);

    // Production Efficiency - Added NULL handling
    $response['production_efficiency'] = $production['total_target'] > 0 
        ? round(($production['total_completed'] / $production['total_target']) * 100, 2)
        : 0;

    echo json_encode([
        'success' => true,
        'data' => $response
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 