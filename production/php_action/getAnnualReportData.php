<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set proper content type for JSON response
header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode(['status' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    $data = [
        'summary' => getExecutiveSummary($connect),
        'inventory' => getInventoryData($connect),
        'production' => getProductionData($connect),
        'quality' => getQualityData($connect),
        'financial' => getFinancialData($connect),
        'movement' => getMovementData($connect)
    ];

    echo json_encode(['status' => true, 'data' => $data]);
} catch (Exception $e) {
    error_log("Error in getAnnualReportData.php: " . $e->getMessage());
    echo json_encode(['status' => false, 'message' => 'Failed to generate report data']);
}

// Get Executive Summary Data
function getExecutiveSummary($connect) {
    // Total Products
    $query = "SELECT COUNT(*) as total FROM products WHERE status = 1";
    $result = $connect->query($query);
    $totalProducts = $result->fetch_assoc()['total'];

    // Total Revenue from Orders
    $query = "SELECT COALESCE(SUM(total_amount), 0) as total 
              FROM orders 
              WHERE YEAR(order_date) = YEAR(CURRENT_DATE)";
    $result = $connect->query($query);
    $totalRevenue = $result->fetch_assoc()['total'];

    // Total Production Orders
    $query = "SELECT COUNT(*) as total 
              FROM production_orders 
              WHERE YEAR(created_at) = YEAR(CURRENT_DATE)";
    $result = $connect->query($query);
    $totalOrders = $result->fetch_assoc()['total'];

    // Quality Rate from Quality Control
    $query = "SELECT 
                COALESCE(
                    (SUM(quantity_passed) * 100.0 / NULLIF(SUM(quantity_checked), 0)), 
                    0
                ) as rate 
              FROM quality_control
              WHERE YEAR(created_at) = YEAR(CURRENT_DATE)";
    $result = $connect->query($query);
    $qualityRate = $result->fetch_assoc()['rate'];

    return [
        'totalProducts' => (int)$totalProducts,
        'totalRevenue' => (float)$totalRevenue,
        'totalOrders' => (int)$totalOrders,
        'qualityRate' => (float)$qualityRate
    ];
}

// Get Inventory Data
function getInventoryData($connect) {
    // Stock Value Distribution by Category
    $query = "SELECT 
                COALESCE(c.name, 'Uncategorized') as category,
                SUM(p.current_stock * p.cost) as value
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.category_id
              GROUP BY c.category_id
              ORDER BY value DESC
              LIMIT 5";
    $result = $connect->query($query);
    
    $stockValue = ['labels' => [], 'values' => []];
    while ($row = $result->fetch_assoc()) {
        $stockValue['labels'][] = $row['category'];
        $stockValue['values'][] = (float)$row['value'];
    }

    // Low Stock Alert
    $query = "SELECT 
                p.name,
                p.current_stock,
                p.min_stock_level,
                COALESCE(b.name, 'No Brand') as brand_name
              FROM products p
              LEFT JOIN brands b ON p.brand_id = b.brand_id
              WHERE p.current_stock <= p.min_stock_level
              ORDER BY (p.current_stock / NULLIF(p.min_stock_level, 0))
              LIMIT 10";
    $result = $connect->query($query);
    
    $lowStock = [];
    while ($row = $result->fetch_assoc()) {
        $lowStock[] = [
            'name' => $row['name'] . ' (' . $row['brand_name'] . ')',
            'current_stock' => (int)$row['current_stock'],
            'min_stock_level' => (int)$row['min_stock_level']
        ];
    }

    return [
        'stockValue' => $stockValue,
        'lowStock' => $lowStock
    ];
}

// Get Production Data
function getProductionData($connect) {
    // Monthly Production Trends
    $query = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total_orders,
                COALESCE(SUM(target_quantity), 0) as target_qty,
                COALESCE(SUM(completed_quantity), 0) as completed_qty
              FROM production_orders
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')
              ORDER BY month";
    $result = $connect->query($query);
    
    $trends = ['labels' => [], 'target' => [], 'actual' => []];
    while ($row = $result->fetch_assoc()) {
        $trends['labels'][] = $row['month'];
        $trends['target'][] = (int)$row['target_qty'];
        $trends['actual'][] = (int)$row['completed_qty'];
    }

    // If no data, add current month with zero values
    if (empty($trends['labels'])) {
        $trends['labels'][] = date('Y-m');
        $trends['target'][] = 0;
        $trends['actual'][] = 0;
    }

    // Production Efficiency
    $query = "SELECT 
                COALESCE(
                    (SUM(completed_quantity) * 100.0 / NULLIF(SUM(target_quantity), 0)),
                    0
                ) as efficiency
              FROM production_orders
              WHERE YEAR(created_at) = YEAR(CURRENT_DATE)";
    $result = $connect->query($query);
    $efficiency = $result->fetch_assoc()['efficiency'] ?? 0;

    return [
        'trends' => $trends,
        'efficiency' => (float)$efficiency
    ];
}

// Get Quality Data
function getQualityData($connect) {
    // Monthly Quality Metrics
    $query = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COALESCE(SUM(quantity_passed), 0) as passed,
                COALESCE(SUM(quantity_failed), 0) as failed,
                COALESCE(SUM(quantity_checked), 0) as total
              FROM quality_control
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')
              ORDER BY month";
    $result = $connect->query($query);
    
    $metrics = ['labels' => [], 'passRate' => [], 'failRate' => []];
    while ($row = $result->fetch_assoc()) {
        $total = $row['total'] ?: 1; // Avoid division by zero
        $metrics['labels'][] = $row['month'];
        $metrics['passRate'][] = ($row['passed'] / $total) * 100;
        $metrics['failRate'][] = ($row['failed'] / $total) * 100;
    }

    // If no data, add current month with zero values
    if (empty($metrics['labels'])) {
        $metrics['labels'][] = date('Y-m');
        $metrics['passRate'][] = 0;
        $metrics['failRate'][] = 0;
    }

    // Defect Analysis by Product
    $query = "SELECT 
                COALESCE(pp.name, po.order_number) as name,
                COALESCE(
                    (SUM(qc.quantity_failed) * 100.0 / NULLIF(SUM(qc.quantity_checked), 0)),
                    0
                ) as defect_rate
              FROM quality_control qc
              JOIN production_orders po ON qc.production_order_id = po.id
              LEFT JOIN production_products pp ON po.product_id = pp.id
              WHERE qc.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                AND qc.quantity_failed > 0
              GROUP BY po.id, pp.id, pp.name, po.order_number
              ORDER BY defect_rate DESC
              LIMIT 5";
    $result = $connect->query($query);
    
    $defects = ['labels' => [], 'values' => []];
    while ($row = $result->fetch_assoc()) {
        $defects['labels'][] = $row['name'];
        $defects['values'][] = (float)$row['defect_rate'];
    }

    // If no defects data, add placeholder
    if (empty($defects['labels'])) {
        $defects['labels'][] = 'No Defects';
        $defects['values'][] = 0;
    }

    return [
        'metrics' => $metrics,
        'defects' => $defects
    ];
}

// Get Financial Data
function getFinancialData($connect) {
    // Monthly Revenue and Expenses
    $query = "SELECT 
                DATE_FORMAT(o.order_date, '%Y-%m') as month,
                SUM(o.total_amount) as revenue,
                COALESCE(SUM(p.grand_total), 0) as expenses
              FROM orders o
              LEFT JOIN purchases p ON DATE_FORMAT(o.order_date, '%Y-%m') = DATE_FORMAT(p.created_at, '%Y-%m')
              WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY DATE_FORMAT(o.order_date, '%Y-%m')
              ORDER BY month";
    $result = $connect->query($query);
    
    $months = [];
    $revenue = [];
    $expenses = [];
    
    while ($row = $result->fetch_assoc()) {
        $months[] = $row['month'];
        $revenue[] = (float)$row['revenue'];
        $expenses[] = (float)$row['expenses'];
    }

    // Total Financial Metrics
    $query = "SELECT 
                (SELECT COALESCE(SUM(total_amount), 0) 
                 FROM orders 
                 WHERE YEAR(order_date) = YEAR(CURRENT_DATE)) as total_sales,
                (SELECT COALESCE(SUM(grand_total), 0) 
                 FROM purchases 
                 WHERE YEAR(created_at) = YEAR(CURRENT_DATE)) as total_purchases";
    $result = $connect->query($query);
    $totals = $result->fetch_assoc();
    
    $totalSales = (float)$totals['total_sales'];
    $totalPurchases = (float)$totals['total_purchases'];
    $netProfit = $totalSales - $totalPurchases;

    return [
        'months' => $months,
        'revenue' => $revenue,
        'expenses' => $expenses,
        'totalSales' => $totalSales,
        'totalPurchases' => $totalPurchases,
        'netProfit' => $netProfit
    ];
}

// Get Movement Data
function getMovementData($connect) {
    // Daily Movement Trends
    $query = "SELECT 
                DATE(created_at) as date,
                movement_type,
                COALESCE(SUM(quantity), 0) as total
              FROM stock_movements
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              GROUP BY DATE(created_at), movement_type
              ORDER BY date";
    $result = $connect->query($query);
    
    $dates = [];
    $inQty = [];
    $outQty = [];
    $currentDate = null;
    $currentIn = 0;
    $currentOut = 0;
    
    while ($row = $result->fetch_assoc()) {
        if ($currentDate !== $row['date']) {
            if ($currentDate !== null) {
                $dates[] = $currentDate;
                $inQty[] = $currentIn;
                $outQty[] = $currentOut;
            }
            $currentDate = $row['date'];
            $currentIn = 0;
            $currentOut = 0;
        }
        if ($row['movement_type'] === 'in') {
            $currentIn = (int)$row['total'];
        } else {
            $currentOut = (int)$row['total'];
        }
    }
    
    if ($currentDate !== null) {
        $dates[] = $currentDate;
        $inQty[] = $currentIn;
        $outQty[] = $currentOut;
    }

    // If no movement data, add current date with zero values
    if (empty($dates)) {
        $dates[] = date('Y-m-d');
        $inQty[] = 0;
        $outQty[] = 0;
    }

    // Monthly Movement Summary
    $query = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COALESCE(SUM(quantity), 0) as total_quantity
              FROM stock_movements
              WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY DATE_FORMAT(created_at, '%Y-%m')
              ORDER BY month";
    $result = $connect->query($query);
    
    $monthly = ['labels' => [], 'total' => []];
    while ($row = $result->fetch_assoc()) {
        $monthly['labels'][] = $row['month'];
        $monthly['total'][] = (int)$row['total_quantity'];
    }

    // If no monthly data, add current month with zero values
    if (empty($monthly['labels'])) {
        $monthly['labels'][] = date('Y-m');
        $monthly['total'][] = 0;
    }

    return [
        'daily' => [
            'labels' => $dates,
            'in' => $inQty,
            'out' => $outQty
        ],
        'monthly' => $monthly
    ];
} 