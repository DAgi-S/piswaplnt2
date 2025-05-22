<?php
require_once 'core.php';
require_once 'db_connect.php';

try {
    // Start transaction
    $connect->begin_transaction();

    // First, ensure we have categories and brands
    $sql = "INSERT IGNORE INTO production_categories (id, name, description, status, created_by) VALUES 
            (1, 'Raw Materials', 'Raw materials used in production', 'active', 1),
            (2, 'Semi-Finished', 'Semi-finished products', 'active', 1),
            (3, 'Finished Products', 'Final products ready for sale', 'active', 1)";
    $connect->query($sql);

    $sql = "INSERT IGNORE INTO production_brands (id, name, description, status, created_by) VALUES 
            (1, 'House Brand', 'Our own brand', 'active', 1),
            (2, 'OEM', 'Original Equipment Manufacturer', 'active', 1)";
    $connect->query($sql);

    // Add sample products
    $sql = "INSERT IGNORE INTO production_products 
            (id, product_code, name, category_id, brand_id, unit, current_stock, min_stock_level, production_cost, selling_price, description, status) VALUES 
            (1, 'FP-001', 'Premium Chair', 3, 1, 'pcs', 50, 10, 150.00, 299.99, 'Premium wooden chair', 'active'),
            (2, 'FP-002', 'Dining Table', 3, 1, 'pcs', 20, 5, 450.00, 899.99, 'Wooden dining table', 'active'),
            (3, 'FP-003', 'Coffee Table', 3, 1, 'pcs', 30, 8, 200.00, 399.99, 'Modern coffee table', 'active')";
    $connect->query($sql);

    // Add sample production orders
    $sql = "INSERT IGNORE INTO production_orders 
            (order_number, product_id, target_quantity, completed_quantity, start_date, expected_completion_date, status, notes, created_by) VALUES 
            ('PO-20240101-001', 1, 100, 0, '2024-01-15', '2024-01-30', 'draft', 'Bulk order for chairs', 1),
            ('PO-20240101-002', 2, 50, 0, '2024-01-20', '2024-02-10', 'confirmed', 'Priority order for dining tables', 1),
            ('PO-20240101-003', 3, 75, 25, '2024-01-10', '2024-01-25', 'in_progress', 'Regular production batch', 1),
            ('PO-20240101-004', 1, 150, 150, '2024-01-01', '2024-01-15', 'completed', 'Completed chair order', 1),
            ('PO-20240101-005', 2, 30, 0, '2024-01-05', '2024-01-20', 'cancelled', 'Cancelled due to design change', 1)";
    $connect->query($sql);

    $connect->commit();
    echo "Sample data created successfully! Created:
    - 3 categories
    - 2 brands
    - 3 products
    - 5 production orders with different statuses";

} catch(Exception $e) {
    $connect->rollback();
    echo "Error: " . $e->getMessage();
}

$connect->close();
?> 