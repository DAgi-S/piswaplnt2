<?php
// For setup only - direct database connection
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "pistocklnt";

// Create connection
$connect = new mysqli($localhost, $username, $password, $dbname);

// Check connection
if ($connect->connect_error) {
    die(json_encode(array('success' => false, 'message' => 'Connection failed: ' . $connect->connect_error)));
}

try {
    // Start transaction
    $connect->begin_transaction();
    
    // Define default components
    $components = array(
        // Quick Action Buttons
        array('buttons', 'add_product', 'Add Product', 'fas fa-plus', 1),
        array('buttons', 'add_order', 'Add Order', 'fas fa-shopping-cart', 1),
        array('buttons', 'add_category', 'Add Category', 'fas fa-folder-plus', 1),
        array('buttons', 'add_brand', 'Add Brand', 'fas fa-tag', 1),
        array('buttons', 'manage_users', 'Manage Users', 'fas fa-users-cog', 0),
        array('buttons', 'reports', 'View Reports', 'fas fa-chart-bar', 0),
        
        // Information Cards
        array('cards', 'total_products', 'Total Products', 'fas fa-box', 1),
        array('cards', 'low_stock', 'Low Stock Items', 'fas fa-exclamation-triangle', 1),
        array('cards', 'total_orders', 'Total Orders', 'fas fa-shopping-bag', 1),
        array('cards', 'total_sales', 'Total Sales', 'fas fa-dollar-sign', 1),
        array('cards', 'recent_orders', 'Recent Orders (24h)', 'fas fa-clock', 0),
        array('cards', 'pending_orders', 'Pending Orders', 'fas fa-hourglass-half', 0),
        array('cards', 'total_categories', 'Total Categories', 'fas fa-folder', 0),
        array('cards', 'total_brands', 'Total Brands', 'fas fa-tags', 0),
        
        // Analytics Widgets
        array('analytics', 'sales_trend', 'Sales Trend', 'fas fa-chart-line', 1),
        array('analytics', 'top_products', 'Top Products', 'fas fa-chart-bar', 1),
        array('analytics', 'category_distribution', 'Category Distribution', 'fas fa-chart-pie', 0),
        array('analytics', 'stock_alerts', 'Stock Alerts', 'fas fa-exclamation-circle', 1)
    );
    
    // Prepare insert statement
    $query = "INSERT INTO dashboard_available_components 
             (section_type, component_key, component_name, component_icon, is_default) 
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
             component_name = VALUES(component_name),
             component_icon = VALUES(component_icon),
             is_default = VALUES(is_default)";
             
    $stmt = $connect->prepare($query);
    
    // Insert components
    foreach ($components as $component) {
        $stmt->bind_param("ssssi", $component[0], $component[1], $component[2], $component[3], $component[4]);
        $stmt->execute();
    }
    
    // Commit transaction
    $connect->commit();
    
    echo json_encode(array('success' => true, 'message' => 'Default components added successfully'));
    
} catch (Exception $e) {
    // Rollback on error
    $connect->rollback();
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
}

$connect->close();
?> 