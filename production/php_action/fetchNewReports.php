<?php
require_once 'core.php';

// Set header type to JSON
header('Content-Type: application/json');

// Function to get date range query part
function getDateRangeQuery($dateRange, $startDate = null, $endDate = null, $dateField = 'created_at') {
    $today = date('Y-m-d');
    switch($dateRange) {
        case 'today':
            return " WHERE DATE($dateField) = '$today'";
        case 'yesterday':
            return " WHERE DATE($dateField) = DATE_SUB('$today', INTERVAL 1 DAY)";
        case 'last7days':
            return " WHERE DATE($dateField) BETWEEN DATE_SUB('$today', INTERVAL 7 DAY) AND '$today'";
        case 'last30days':
            return " WHERE DATE($dateField) BETWEEN DATE_SUB('$today', INTERVAL 30 DAY) AND '$today'";
        case 'thisMonth':
            return " WHERE MONTH($dateField) = MONTH(CURRENT_DATE()) AND YEAR($dateField) = YEAR(CURRENT_DATE())";
        case 'lastMonth':
            return " WHERE DATE($dateField) BETWEEN DATE_SUB(DATE_SUB(CURRENT_DATE(), INTERVAL DAY(CURRENT_DATE())-1 DAY), INTERVAL 1 MONTH) AND LAST_DAY(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
        case 'custom':
            if($startDate && $endDate) {
                return " WHERE DATE($dateField) BETWEEN '$startDate' AND '$endDate'";
            }
        default:
            return "";
    }
}

try {
    $response = array();
    $reportType = isset($_POST['type']) ? $_POST['type'] : '';
    $dateRange = isset($_POST['dateRange']) ? $_POST['dateRange'] : '';
    $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : null;
    $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : null;

    switch($reportType) {
        case 'production_summary':
            // Production Summary Report
            $dateRangeQuery = getDateRangeQuery($dateRange, $startDate, $endDate, 'po.created_at');
            $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN po.status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN po.status = 'inprogress' THEN 1 ELSE 0 END) as ongoing_orders,
                    SUM(CASE WHEN po.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                    ROUND(AVG(CASE WHEN po.status = 'completed' THEN (po.completed_quantity/po.target_quantity * 100) ELSE 0 END), 2) as avg_efficiency
                    FROM production_orders po" . $dateRangeQuery;
            
            $result = $connect->query($sql);
            $summary = $result->fetch_assoc();
            
            // Get detailed orders
            $sql = "SELECT 
                    po.order_number as order_id,
                    pp.name as product_name,
                    po.target_quantity,
                    po.completed_quantity,
                    ROUND((po.completed_quantity/po.target_quantity * 100), 2) as efficiency,
                    po.start_date,
                    po.actual_completion_date as end_date,
                    po.status
                    FROM production_orders po
                    LEFT JOIN production_products pp ON po.product_id = pp.id" . 
                    $dateRangeQuery . 
                    " ORDER BY po.created_at DESC";
            
            $result = $connect->query($sql);
            $orders = array();
            while($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
            
            $response = array(
                'success' => true,
                'summary' => $summary,
                'orders' => $orders
            );
            break;

        case 'inventory_status':
            $sql = "SELECT 
                    rm.id as material_id,
                    rm.name as material_name,
                    rm.current_stock,
                    rm.min_stock_level as minimum_stock,
                    rm.unit,
                    rmc.name as category,
                    CASE 
                        WHEN rm.current_stock <= rm.min_stock_level THEN 'Low Stock'
                        WHEN rm.current_stock = 0 THEN 'Out of Stock'
                        ELSE 'In Stock'
                    END as stock_status
                    FROM raw_materials rm
                    LEFT JOIN raw_material_categories rmc ON rm.category_id = rmc.id
                    ORDER BY 
                        CASE 
                            WHEN rm.current_stock <= rm.min_stock_level THEN 1
                            WHEN rm.current_stock = 0 THEN 2
                            ELSE 3
                        END,
                        rm.name";
            
            $result = $connect->query($sql);
            $materials = array();
            while($row = $result->fetch_assoc()) {
                $materials[] = $row;
            }
            
            // Get summary counts
            $sql = "SELECT 
                    COUNT(*) as total_items,
                    SUM(CASE WHEN current_stock <= min_stock_level THEN 1 ELSE 0 END) as low_stock_items,
                    SUM(CASE WHEN current_stock = 0 THEN 1 ELSE 0 END) as out_of_stock_items
                    FROM raw_materials";
            
            $result = $connect->query($sql);
            $summary = $result->fetch_assoc();
            
            $response = array(
                'success' => true,
                'summary' => $summary,
                'materials' => $materials
            );
            break;

        case 'quality_metrics':
            $dateRangeQuery = getDateRangeQuery($dateRange, $startDate, $endDate, 'qc.inspection_date');
            $sql = "SELECT 
                    qc.id as inspection_id,
                    qc.production_order_id as product_id,
                    po.order_number as batch_number,
                    qc.inspection_date,
                    u.username as inspector,
                    qc.status,
                    qc.notes as remarks,
                    qc.quantity_failed as defect_count
                    FROM quality_control qc
                    LEFT JOIN production_orders po ON qc.production_order_id = po.id
                    LEFT JOIN users u ON qc.created_by = u.user_id" .
                    $dateRangeQuery . 
                    " ORDER BY qc.inspection_date DESC";
            
            $result = $connect->query($sql);
            $inspections = array();
            while($row = $result->fetch_assoc()) {
                $inspections[] = $row;
            }
            
            // Get summary statistics
            $sql = "SELECT 
                    COUNT(*) as total_inspections,
                    SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed_inspections,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_inspections,
                    ROUND(AVG(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) * 100, 2) as pass_rate
                    FROM quality_control" . $dateRangeQuery;
            
            $result = $connect->query($sql);
            $summary = $result->fetch_assoc();
            
            $response = array(
                'success' => true,
                'summary' => $summary,
                'inspections' => $inspections
            );
            break;

        case 'cost_analysis':
            $dateRangeQuery = getDateRangeQuery($dateRange, $startDate, $endDate, 'po.created_at');
            
            // Get overall cost summary
            $sql = "SELECT 
                    COUNT(DISTINCT po.id) as total_orders,
                    SUM(pb.quantity_required * rm.cost_per_unit * (1 + pb.wastage_percent/100)) as total_material_cost,
                    COUNT(DISTINCT po.product_id) as total_products,
                    SUM(po.completed_quantity) as total_units_produced,
                    ROUND(AVG(pb.wastage_percent), 2) as avg_wastage_percent,
                    SUM((pb.quantity_required * rm.cost_per_unit * pb.wastage_percent/100)) as total_wastage_cost
                    FROM production_orders po
                    JOIN product_bom pb ON po.product_id = pb.product_id
                    JOIN raw_materials rm ON pb.material_id = rm.id" . 
                    $dateRangeQuery;
            
            $result = $connect->query($sql);
            $summary = $result->fetch_assoc();
            
            // Calculate cost per unit
            $summary['cost_per_unit'] = $summary['total_units_produced'] > 0 ? 
                round($summary['total_material_cost'] / $summary['total_units_produced'], 2) : 0;
            
            // Get cost breakdown by product category
            $sql = "SELECT 
                    pc.name as category_name,
                    COUNT(DISTINCT po.id) as order_count,
                    SUM(pb.quantity_required * rm.cost_per_unit * (1 + pb.wastage_percent/100)) as material_cost,
                    SUM(po.completed_quantity) as units_produced,
                    ROUND(AVG(pb.wastage_percent), 2) as avg_wastage,
                    SUM((pb.quantity_required * rm.cost_per_unit * pb.wastage_percent/100)) as wastage_cost
                    FROM production_orders po
                    JOIN production_products pp ON po.product_id = pp.id
                    JOIN production_categories pc ON pp.category_id = pc.id
                    JOIN product_bom pb ON po.product_id = pb.product_id
                    JOIN raw_materials rm ON pb.material_id = rm.id" . 
                    $dateRangeQuery . 
                    " GROUP BY pc.id, pc.name
                    ORDER BY material_cost DESC";
            
            $result = $connect->query($sql);
            $categoryAnalysis = array();
            while($row = $result->fetch_assoc()) {
                $row['cost_per_unit'] = $row['units_produced'] > 0 ? 
                    round($row['material_cost'] / $row['units_produced'], 2) : 0;
                $categoryAnalysis[] = $row;
            }
            
            // Get monthly cost trends
            $sql = "SELECT 
                    DATE_FORMAT(po.created_at, '%Y-%m') as month,
                    COUNT(DISTINCT po.id) as order_count,
                    SUM(pb.quantity_required * rm.cost_per_unit * (1 + pb.wastage_percent/100)) as total_cost,
                    SUM((pb.quantity_required * rm.cost_per_unit * pb.wastage_percent/100)) as wastage_cost,
                    SUM(po.completed_quantity) as units_produced
                    FROM production_orders po
                    JOIN product_bom pb ON po.product_id = pb.product_id
                    JOIN raw_materials rm ON pb.material_id = rm.id" . 
                    $dateRangeQuery . 
                    " GROUP BY DATE_FORMAT(po.created_at, '%Y-%m')
                    ORDER BY month ASC";
            
            $result = $connect->query($sql);
            $monthlyTrends = [
                'months' => [],
                'total_cost' => [],
                'wastage_cost' => [],
                'units_produced' => [],
                'cost_per_unit' => []
            ];
            
            while($row = $result->fetch_assoc()) {
                $monthlyTrends['months'][] = $row['month'];
                $monthlyTrends['total_cost'][] = round($row['total_cost'], 2);
                $monthlyTrends['wastage_cost'][] = round($row['wastage_cost'], 2);
                $monthlyTrends['units_produced'][] = (int)$row['units_produced'];
                $monthlyTrends['cost_per_unit'][] = $row['units_produced'] > 0 ? 
                    round($row['total_cost'] / $row['units_produced'], 2) : 0;
            }
            
            $response = [
                'success' => true,
                'summary' => $summary,
                'categoryAnalysis' => $categoryAnalysis,
                'monthlyTrends' => $monthlyTrends
            ];
            break;

        case 'raw_material':
            // Get date range conditions
            $dateRangeQuery = getDateRangeQuery($dateRange, $startDate, $endDate, 'created_at');
            
            // Get raw materials summary
            $summary = [];
            
            // Total materials
            $sql = "SELECT 
                COUNT(*) as total_materials,
                SUM(CASE WHEN current_stock <= min_stock_level AND current_stock > 0 THEN 1 ELSE 0 END) as low_stock_items,
                SUM(CASE WHEN current_stock <= 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN current_stock > min_stock_level THEN 1 ELSE 0 END) as healthy_stock,
                SUM(current_stock * cost_per_unit) as total_value,
                SUM(reserved_quantity) as reserved_quantity,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_materials
                FROM raw_materials";
            
            $result = $connect->query($sql);
            if($result) {
                $summary = $result->fetch_assoc();
            }
            
            // Get raw materials list with categories
            $sql = "SELECT 
                rm.*,
                rc.name as category_name,
                (rm.current_stock * rm.cost_per_unit) as total_value
                FROM raw_materials rm
                LEFT JOIN raw_material_categories rc ON rm.category_id = rc.id
                ORDER BY rm.current_stock ASC";
            
            $result = $connect->query($sql);
            $materials = [];
            if($result) {
                while($row = $result->fetch_assoc()) {
                    $materials[] = $row;
                }
            }
            
            // Get stock movements trend
            $sql = "SELECT 
                DATE(created_at) as movement_date,
                movement_type,
                SUM(quantity) as total_quantity
                FROM raw_material_movements" . 
                $dateRangeQuery . 
                " GROUP BY movement_date, movement_type
                ORDER BY movement_date ASC";
            
            $result = $connect->query($sql);
            $movements = [
                'dates' => [],
                'stock_in' => [],
                'stock_out' => []
            ];
            
            if($result) {
                $movementData = [];
                while($row = $result->fetch_assoc()) {
                    $date = $row['movement_date'];
                    if(!in_array($date, $movements['dates'])) {
                        $movements['dates'][] = $date;
                        $movementData[$date] = ['in' => 0, 'out' => 0];
                    }
                    if($row['movement_type'] == 'in') {
                        $movementData[$date]['in'] = $row['total_quantity'];
                    } else {
                        $movementData[$date]['out'] = $row['total_quantity'];
                    }
                }
                
                foreach($movements['dates'] as $date) {
                    $movements['stock_in'][] = $movementData[$date]['in'];
                    $movements['stock_out'][] = $movementData[$date]['out'];
                }
            }
            
            $response = [
                'success' => true,
                'summary' => $summary,
                'materials' => $materials,
                'movements' => $movements
            ];
            break;

        case 'purchase_order':
            // Get date range conditions
            $dateRangeQuery = getDateRangeQuery($dateRange, $startDate, $endDate, 'p.purchase_date');
            
            // Get purchase orders summary
            $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN payment_status = 'Paid' THEN 1 ELSE 0 END) as paid_orders,
                    SUM(CASE WHEN payment_status = 'Unpaid' THEN 1 ELSE 0 END) as unpaid_orders,
                    SUM(CASE WHEN payment_status = 'Partial' THEN 1 ELSE 0 END) as partial_orders,
                    SUM(grand_total) as total_amount,
                    SUM(paid_amount) as total_paid,
                    SUM(grand_total - paid_amount) as total_pending,
                    COUNT(DISTINCT supplier_id) as total_suppliers
                    FROM purchases p" . $dateRangeQuery;
            
            $result = $connect->query($sql);
            $summary = $result->fetch_assoc();
            
            // Get detailed purchase orders
            $sql = "SELECT 
                    p.purchase_number,
                    p.purchase_date,
                    s.company_name as supplier_name,
                    p.sub_total,
                    p.vat_amount,
                    p.grand_total,
                    p.paid_amount,
                    p.payment_status,
                    p.status,
                    u.username as created_by
                    FROM purchases p
                    LEFT JOIN suppliers s ON p.supplier_id = s.id
                    LEFT JOIN users u ON p.created_by = u.user_id" . 
                    $dateRangeQuery . 
                    " ORDER BY p.purchase_date DESC";
            
            $result = $connect->query($sql);
            $orders = [];
            while($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
            
            // Get purchase trends (last 7 days)
            $sql = "SELECT 
                    DATE(purchase_date) as order_date,
                    COUNT(*) as order_count,
                    SUM(grand_total) as daily_total
                    FROM purchases p" . 
                    $dateRangeQuery . 
                    " GROUP BY DATE(purchase_date)
                    ORDER BY purchase_date ASC";
            
            $result = $connect->query($sql);
            $trends = [
                'dates' => [],
                'counts' => [],
                'amounts' => []
            ];
            
            while($row = $result->fetch_assoc()) {
                $trends['dates'][] = $row['order_date'];
                $trends['counts'][] = (int)$row['order_count'];
                $trends['amounts'][] = (float)$row['daily_total'];
            }
            
            $response = [
                'success' => true,
                'summary' => $summary,
                'orders' => $orders,
                'trends' => $trends
            ];
            break;

        case 'sales_order':
            // Get date range conditions
            $dateRangeQuery = getDateRangeQuery($dateRange, $startDate, $endDate, 'so.order_date');
            
            // Get sales orders summary
            $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
                    SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_orders,
                    SUM(CASE WHEN payment_status = 'partial' THEN 1 ELSE 0 END) as partial_orders,
                    SUM(total_amount) as total_amount,
                    SUM(paid_amount) as total_paid,
                    SUM(total_amount - paid_amount) as total_pending,
                    COUNT(DISTINCT client_id) as total_clients
                    FROM sales_orders so" . $dateRangeQuery;
            
            $result = $connect->query($sql);
            $summary = $result->fetch_assoc();
            
            // Get detailed sales orders
            $sql = "SELECT 
                    so.order_number,
                    so.order_date,
                    c.company_name as client_name,
                    so.subtotal,
                    so.tax_amount,
                    so.discount_amount,
                    so.total_amount,
                    so.paid_amount,
                    so.payment_status,
                    so.order_status,
                    u.username as created_by
                    FROM sales_orders so
                    LEFT JOIN clients c ON so.client_id = c.id
                    LEFT JOIN users u ON so.created_by = u.user_id" . 
                    $dateRangeQuery . 
                    " ORDER BY so.order_date DESC";
            
            $result = $connect->query($sql);
            $orders = [];
            while($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
            
            // Get sales trends (last 7 days)
            $sql = "SELECT 
                    DATE(order_date) as order_date,
                    COUNT(*) as order_count,
                    SUM(total_amount) as daily_total
                    FROM sales_orders so" . 
                    $dateRangeQuery . 
                    " GROUP BY DATE(order_date)
                    ORDER BY order_date ASC";
            
            $result = $connect->query($sql);
            $trends = [
                'dates' => [],
                'counts' => [],
                'amounts' => []
            ];
            
            while($row = $result->fetch_assoc()) {
                $trends['dates'][] = $row['order_date'];
                $trends['counts'][] = (int)$row['order_count'];
                $trends['amounts'][] = (float)$row['daily_total'];
            }
            
            $response = [
                'success' => true,
                'summary' => $summary,
                'orders' => $orders,
                'trends' => $trends
            ];
            break;

        case 'sales_performance':
            // Get date range conditions
            $dateRangeQuery = getDateRangeQuery($dateRange, $startDate, $endDate, 'so.order_date');
            
            // Get sales performance summary
            $sql = "SELECT 
                    COUNT(DISTINCT so.id) as total_orders,
                    COUNT(DISTINCT so.client_id) as total_customers,
                    SUM(so.total_amount) as total_revenue,
                    SUM(so.paid_amount) as total_collected,
                    SUM(so.total_amount - so.paid_amount) as total_outstanding,
                    ROUND(AVG(CASE WHEN so.payment_status = 'paid' THEN 1 ELSE 0 END) * 100, 2) as payment_success_rate,
                    COUNT(DISTINCT CASE WHEN so.payment_status = 'paid' THEN so.id END) as completed_orders,
                    COUNT(DISTINCT CASE WHEN so.payment_status = 'unpaid' THEN so.id END) as pending_orders
                    FROM sales_orders so" . $dateRangeQuery;
            
            $result = $connect->query($sql);
            $summary = $result->fetch_assoc();
            
            // Get top selling products
            $sql = "SELECT 
                    pp.name as product_name,
                    COUNT(DISTINCT soi.sales_order_id) as order_count,
                    SUM(soi.quantity) as total_quantity,
                    SUM(soi.total) as total_revenue
                    FROM sales_order_items soi
                    JOIN production_products pp ON soi.product_id = pp.id
                    JOIN sales_orders so ON soi.sales_order_id = so.id" . 
                    $dateRangeQuery . 
                    " GROUP BY pp.id, pp.name
                    ORDER BY total_revenue DESC
                    LIMIT 5";
            
            $result = $connect->query($sql);
            $topProducts = [];
            while($row = $result->fetch_assoc()) {
                $topProducts[] = $row;
            }
            
            // Get daily sales trend
            $sql = "SELECT 
                    DATE(so.order_date) as sale_date,
                    COUNT(DISTINCT so.id) as order_count,
                    SUM(so.total_amount) as daily_revenue,
                    COUNT(DISTINCT so.client_id) as customer_count
                    FROM sales_orders so" . 
                    $dateRangeQuery . 
                    " GROUP BY DATE(so.order_date)
                    ORDER BY sale_date ASC";
            
            $result = $connect->query($sql);
            $trends = [
                'dates' => [],
                'orders' => [],
                'revenue' => [],
                'customers' => []
            ];
            
            while($row = $result->fetch_assoc()) {
                $trends['dates'][] = $row['sale_date'];
                $trends['orders'][] = (int)$row['order_count'];
                $trends['revenue'][] = (float)$row['daily_revenue'];
                $trends['customers'][] = (int)$row['customer_count'];
            }
            
            // Get customer performance
            $sql = "SELECT 
                    c.company_name as customer_name,
                    COUNT(DISTINCT so.id) as order_count,
                    SUM(so.total_amount) as total_spent,
                    MAX(so.order_date) as last_order_date
                    FROM sales_orders so
                    JOIN clients c ON so.client_id = c.id" . 
                    $dateRangeQuery . 
                    " GROUP BY c.id, c.company_name
                    ORDER BY total_spent DESC
                    LIMIT 10";
            
            $result = $connect->query($sql);
            $topCustomers = [];
            while($row = $result->fetch_assoc()) {
                $topCustomers[] = $row;
            }
            
            $response = [
                'success' => true,
                'summary' => $summary,
                'topProducts' => $topProducts,
                'trends' => $trends,
                'topCustomers' => $topCustomers
            ];
            break;

        case 'sales_analysis':
            // Get date range conditions
            $dateRangeQuery = getDateRangeQuery($dateRange, $startDate, $endDate, 'so.order_date');
            
            // Get sales analysis summary
            $sql = "SELECT 
                    COUNT(DISTINCT so.id) as total_orders,
                    SUM(so.total_amount) as total_revenue,
                    ROUND(AVG(so.total_amount), 2) as avg_order_value,
                    SUM(so.discount_amount) as total_discounts,
                    COUNT(DISTINCT so.client_id) as unique_customers,
                    ROUND(SUM(so.total_amount) / COUNT(DISTINCT so.client_id), 2) as avg_customer_value,
                    ROUND(SUM(soi.quantity), 2) as total_units_sold,
                    ROUND(SUM(so.total_amount) / SUM(soi.quantity), 2) as avg_unit_price
                    FROM sales_orders so
                    JOIN sales_order_items soi ON so.id = soi.sales_order_id" . 
                    $dateRangeQuery;
            
            $result = $connect->query($sql);
            $summary = $result->fetch_assoc();
            
            // Get product category performance
            $sql = "SELECT 
                    pc.name as category_name,
                    COUNT(DISTINCT so.id) as order_count,
                    SUM(soi.quantity) as units_sold,
                    SUM(soi.total) as revenue,
                    ROUND(AVG(soi.unit_price), 2) as avg_price
                    FROM sales_orders so
                    JOIN sales_order_items soi ON so.id = soi.sales_order_id
                    JOIN production_products pp ON soi.product_id = pp.id
                    LEFT JOIN production_categories pc ON pp.category_id = pc.id" .
                    $dateRangeQuery .
                    " GROUP BY pc.id, pc.name
                    ORDER BY revenue DESC";
            
            $result = $connect->query($sql);
            $categoryAnalysis = [];
            while($row = $result->fetch_assoc()) {
                $categoryAnalysis[] = $row;
            }
            
            // Get monthly sales trend
            $sql = "SELECT 
                    DATE_FORMAT(so.order_date, '%Y-%m') as month,
                    COUNT(DISTINCT so.id) as order_count,
                    SUM(so.total_amount) as revenue,
                    COUNT(DISTINCT so.client_id) as customer_count,
                    ROUND(AVG(so.total_amount), 2) as avg_order_value
                    FROM sales_orders so" .
                    $dateRangeQuery .
                    " GROUP BY DATE_FORMAT(so.order_date, '%Y-%m')
                    ORDER BY month ASC";
            
            $result = $connect->query($sql);
            $monthlyTrends = [
                'months' => [],
                'orders' => [],
                'revenue' => [],
                'customers' => [],
                'avg_order_value' => []
            ];
            
            while($row = $result->fetch_assoc()) {
                $monthlyTrends['months'][] = $row['month'];
                $monthlyTrends['orders'][] = (int)$row['order_count'];
                $monthlyTrends['revenue'][] = (float)$row['revenue'];
                $monthlyTrends['customers'][] = (int)$row['customer_count'];
                $monthlyTrends['avg_order_value'][] = (float)$row['avg_order_value'];
            }
            
            // Get payment method analysis
            $sql = "SELECT 
                    sp.payment_method,
                    COUNT(*) as transaction_count,
                    SUM(sp.amount) as total_amount,
                    ROUND(AVG(sp.amount), 2) as avg_amount
                    FROM sales_payments sp
                    JOIN sales_orders so ON sp.sales_order_id = so.id" .
                    $dateRangeQuery .
                    " GROUP BY sp.payment_method
                    ORDER BY total_amount DESC";
            
            $result = $connect->query($sql);
            $paymentAnalysis = [];
            while($row = $result->fetch_assoc()) {
                $paymentAnalysis[] = $row;
            }
            
            $response = [
                'success' => true,
                'summary' => $summary,
                'categoryAnalysis' => $categoryAnalysis,
                'monthlyTrends' => $monthlyTrends,
                'paymentAnalysis' => $paymentAnalysis
            ];
            break;

        default:
            throw new Exception("Invalid report type");
    }

} catch(Exception $e) {
    $response = array(
        'success' => false,
        'error' => $e->getMessage()
    );
}

echo json_encode($response);
?> 