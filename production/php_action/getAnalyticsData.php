<?php
require_once 'core.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode(array('success' => false, 'message' => 'User not logged in'));
    exit();
}

// Get the widget key from the request
$widgetKey = isset($_GET['widget']) ? $_GET['widget'] : null;

if (!$widgetKey) {
    echo json_encode(array('success' => false, 'message' => 'No widget specified'));
    exit();
}

try {
    $data = array();
    
    // Fetch data based on widget key
    switch ($widgetKey) {
        case 'sales_trend':
            // Get daily sales for the last 30 days
            $query = "SELECT 
                        DATE(order_date) as date,
                        COUNT(*) as orders,
                        SUM(grand_total) as total
                     FROM orders 
                     WHERE order_status = 1 
                     AND order_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                     GROUP BY DATE(order_date)
                     ORDER BY date";
            
            $result = $connect->query($query);
            $labels = array();
            $values = array();
            
            while ($row = $result->fetch_assoc()) {
                $labels[] = date('M j', strtotime($row['date']));
                $values[] = $row['total'];
            }
            
            $data = array(
                'type' => 'chart',
                'config' => array(
                    'type' => 'line',
                    'data' => array(
                        'labels' => $labels,
                        'datasets' => array(
                            array(
                                'label' => 'Daily Sales',
                                'data' => $values,
                                'borderColor' => '#4e73df',
                                'tension' => 0.3,
                                'fill' => false
                            )
                        )
                    ),
                    'options' => array(
                        'responsive' => true,
                        'maintainAspectRatio' => false
                    )
                )
            );
            break;
            
        case 'top_products':
            // Get top 5 selling products
            $query = "SELECT 
                        p.product_name,
                        COUNT(od.product_id) as total_orders,
                        SUM(od.quantity) as total_quantity
                     FROM order_details od
                     JOIN product p ON od.product_id = p.product_id
                     JOIN orders o ON od.order_id = o.order_id
                     WHERE o.order_status = 1
                     GROUP BY od.product_id
                     ORDER BY total_quantity DESC
                     LIMIT 5";
            
            $result = $connect->query($query);
            $labels = array();
            $values = array();
            
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['product_name'];
                $values[] = $row['total_quantity'];
            }
            
            $data = array(
                'type' => 'chart',
                'config' => array(
                    'type' => 'bar',
                    'data' => array(
                        'labels' => $labels,
                        'datasets' => array(
                            array(
                                'label' => 'Units Sold',
                                'data' => $values,
                                'backgroundColor' => '#4e73df'
                            )
                        )
                    ),
                    'options' => array(
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'scales' => array(
                            'y' => array(
                                'beginAtZero' => true
                            )
                        )
                    )
                )
            );
            break;
            
        case 'category_distribution':
            // Get product distribution by category
            $query = "SELECT 
                        c.categories_name,
                        COUNT(p.product_id) as product_count
                     FROM categories c
                     LEFT JOIN product p ON c.categories_id = p.categories_id
                     WHERE c.categories_status = 1
                     GROUP BY c.categories_id
                     ORDER BY product_count DESC";
            
            $result = $connect->query($query);
            $labels = array();
            $values = array();
            
            while ($row = $result->fetch_assoc()) {
                $labels[] = $row['categories_name'];
                $values[] = $row['product_count'];
            }
            
            $data = array(
                'type' => 'chart',
                'config' => array(
                    'type' => 'pie',
                    'data' => array(
                        'labels' => $labels,
                        'datasets' => array(
                            array(
                                'data' => $values,
                                'backgroundColor' => [
                                    '#4e73df', '#1cc88a', '#36b9cc',
                                    '#f6c23e', '#e74a3b', '#858796'
                                ]
                            )
                        )
                    ),
                    'options' => array(
                        'responsive' => true,
                        'maintainAspectRatio' => false
                    )
                )
            );
            break;
            
        case 'stock_alerts':
            // Get low stock products
            $query = "SELECT 
                        product_name,
                        quantity,
                        rate
                     FROM product
                     WHERE quantity <= 10 AND status = 1
                     ORDER BY quantity ASC
                     LIMIT 10";
            
            $result = $connect->query($query);
            $content = '<div class="stock-alerts-table">';
            $content .= '<table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Stock</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>';
            
            while ($row = $result->fetch_assoc()) {
                $content .= '<tr>
                                <td>' . htmlspecialchars($row['product_name']) . '</td>
                                <td>' . $row['quantity'] . '</td>
                                <td>₱' . number_format($row['rate'], 2) . '</td>
                            </tr>';
            }
            
            $content .= '</tbody></table></div>';
            
            $data = array(
                'type' => 'table',
                'content' => $content
            );
            break;
            
        default:
            echo json_encode(array('success' => false, 'message' => 'Invalid widget key'));
            exit();
    }
    
    echo json_encode(array('success' => true, 'data' => $data));
    
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
}

$connect->close();
?> 