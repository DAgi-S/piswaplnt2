<?php

// ... existing code ...
case 'sales_order':
    $dateRangeQuery = getDateRangeQuery($dateRange, $startDate, $endDate, 'created_at');
    
    // Get summary data
    $summaryQuery = "SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN payment_status = 'Paid' THEN 1 ELSE 0 END) as paid_orders,
        SUM(CASE WHEN payment_status = 'Unpaid' THEN 1 ELSE 0 END) as unpaid_orders,
        SUM(total_amount) as total_amount,
        SUM(paid_amount) as total_paid,
        COUNT(DISTINCT client_id) as total_clients
    FROM sales_orders 
    WHERE 1=1 $dateRangeQuery";
    
    $summaryResult = mysqli_query($conn, $summaryQuery);
    $summary = mysqli_fetch_assoc($summaryResult);
    
    // Get detailed orders data
    $ordersQuery = "SELECT 
        so.order_number,
        so.created_at,
        c.client_name,
        so.subtotal,
        so.tax,
        so.discount,
        so.total_amount,
        so.paid_amount,
        so.payment_status,
        so.order_status,
        u.username as created_by
    FROM sales_orders so
    LEFT JOIN clients c ON so.client_id = c.id
    LEFT JOIN users u ON so.created_by = u.id
    WHERE 1=1 $dateRangeQuery
    ORDER BY so.created_at DESC";
    
    $ordersResult = mysqli_query($conn, $ordersQuery);
    $orders = [];
    while ($row = mysqli_fetch_assoc($ordersResult)) {
        $orders[] = $row;
    }
    
    // Get payment status distribution for pie chart
    $statusQuery = "SELECT 
        payment_status,
        COUNT(*) as count
    FROM sales_orders 
    WHERE 1=1 $dateRangeQuery
    GROUP BY payment_status";
    
    $statusResult = mysqli_query($conn, $statusQuery);
    $statusData = [];
    while ($row = mysqli_fetch_assoc($statusResult)) {
        $statusData[] = $row;
    }
    
    // Get orders trend data
    $trendQuery = "SELECT 
        DATE(created_at) as date,
        COUNT(*) as order_count,
        SUM(total_amount) as total_amount
    FROM sales_orders 
    WHERE 1=1 $dateRangeQuery
    GROUP BY DATE(created_at)
    ORDER BY date";
    
    $trendResult = mysqli_query($conn, $trendQuery);
    $trendData = [];
    while ($row = mysqli_fetch_assoc($trendResult)) {
        $trendData[] = $row;
    }
    
    $response = [
        'summary' => $summary,
        'orders' => $orders,
        'statusData' => $statusData,
        'trendData' => $trendData
    ];
    
    echo json_encode($response);
    break;
// ... existing code ... 