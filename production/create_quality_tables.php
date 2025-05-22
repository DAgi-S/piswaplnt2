<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Creating Quality Control Tables</h2>";

// Try to include the database connection
try {
    require_once 'includes/db_connect.php';
    echo "<p style='color:green'>Database connection file included successfully</p>";
} catch(Exception $e) {
    echo "<p style='color:red'>Error including database connection file: " . $e->getMessage() . "</p>";
    exit;
}

// Check if the database connection variable exists
if (!isset($connect)) {
    echo "<p style='color:red'>Database connection variable not set</p>";
    exit;
} 

// Array of SQL statements to create the tables
$sql_statements = [
    // Quality Control Table
    "CREATE TABLE IF NOT EXISTS `quality_control` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `production_order_id` int(11) NOT NULL,
        `inspection_date` date NOT NULL,
        `quantity_checked` decimal(10,2) NOT NULL,
        `quantity_passed` decimal(10,2) NOT NULL,
        `quantity_failed` decimal(10,2) NOT NULL,
        `defect_type` varchar(100) DEFAULT NULL,
        `notes` text DEFAULT NULL,
        `status` enum('passed','failed','partially_passed') NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `created_by` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `fk_qc_production_orders` (`production_order_id`)
    )",

    // Quality Control Parameters
    "CREATE TABLE IF NOT EXISTS `quality_control_parameters` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `parameter_name` varchar(255) DEFAULT NULL,
        `parameter_type` varchar(50) DEFAULT NULL,
        `unit_of_measure` varchar(50) DEFAULT NULL,
        `acceptable_range` varchar(100) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    )",

    // Quality Control Results
    "CREATE TABLE IF NOT EXISTS `quality_control_results` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `control_date` datetime DEFAULT NULL,
        `parameter_id` int(11) DEFAULT NULL,
        `measured_value` varchar(100) DEFAULT NULL,
        `result_status` varchar(50) DEFAULT NULL,
        `inspector_id` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `fk_qcr_parameters` (`parameter_id`)
    )",

    // Quality Defect Types
    "CREATE TABLE IF NOT EXISTS `quality_defect_types` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `defect_name` varchar(255) DEFAULT NULL,
        `description` text DEFAULT NULL,
        `severity_level` varchar(50) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    )",

    // Quality Inspection Points
    "CREATE TABLE IF NOT EXISTS `quality_inspection_points` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `inspection_name` varchar(255) DEFAULT NULL,
        `description` text DEFAULT NULL,
        `frequency` varchar(50) DEFAULT NULL,
        `active` tinyint(1) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    )",

    // Production Orders table (if not exists)
    "CREATE TABLE IF NOT EXISTS `production_orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_number` varchar(50) NOT NULL,
        `product_id` int(11) NOT NULL,
        `target_quantity` decimal(10,2) NOT NULL,
        `completed_quantity` decimal(10,2) DEFAULT 0.00,
        `start_date` date NOT NULL,
        `expected_completion_date` date NOT NULL,
        `actual_completion_date` date DEFAULT NULL,
        `status` enum('draft','confirmed','inprogress','completed','cancelled') DEFAULT 'draft',
        `notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `created_by` int(11) DEFAULT NULL,
        `warehouse_id` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `order_number` (`order_number`),
        KEY `idx_product_id` (`product_id`),
        KEY `idx_status` (`status`),
        KEY `idx_created_by` (`created_by`),
        KEY `idx_warehouse_id` (`warehouse_id`)
    )",

    // Production Products table (if not exists)
    "CREATE TABLE IF NOT EXISTS `production_products` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `product_code` varchar(50) NOT NULL,
        `name` varchar(100) NOT NULL,
        `category_id` int(11) DEFAULT NULL,
        `brand_id` int(11) DEFAULT NULL,
        `unit` varchar(20) NOT NULL,
        `current_stock` decimal(10,2) DEFAULT 0.00,
        `min_stock_level` decimal(10,2) DEFAULT 0.00,
        `production_cost` decimal(10,2) DEFAULT 0.00,
        `selling_price` decimal(10,2) DEFAULT 0.00,
        `description` text DEFAULT NULL,
        `status` enum('active','inactive') DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `created_by` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `product_code` (`product_code`),
        KEY `idx_category_id` (`category_id`),
        KEY `idx_brand_id` (`brand_id`),
        KEY `idx_created_by` (`created_by`)
    )"
];

// Execute each SQL statement
foreach ($sql_statements as $i => $sql) {
    try {
        $connect->exec($sql);
        echo "<p style='color:green'>Table #" . ($i + 1) . " created successfully</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error creating table #" . ($i + 1) . ": " . $e->getMessage() . "</p>";
    }
}

// Insert sample data for production_products
$sample_products_sql = "INSERT INTO production_products 
    (product_code, name, unit, description, status)
VALUES 
    ('PROD001', 'Metal Frame', 'pcs', 'Standard metal frame for industrial use', 'active'),
    ('PROD002', 'Plastic Container', 'pcs', 'Durable plastic container', 'active'),
    ('PROD003', 'Aluminum Sheet', 'kg', 'High quality aluminum sheet', 'active')
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    unit = VALUES(unit),
    description = VALUES(description),
    status = VALUES(status)";

try {
    $connect->exec($sample_products_sql);
    echo "<p style='color:green'>Sample products data inserted successfully</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error inserting sample products: " . $e->getMessage() . "</p>";
}

// Insert sample data for production_orders
$sample_orders_sql = "INSERT INTO production_orders 
    (order_number, product_id, target_quantity, start_date, expected_completion_date, status)
VALUES 
    ('ORD2024001', 1, 100.00, '2024-04-01', '2024-04-15', 'inprogress'),
    ('ORD2024002', 2, 200.00, '2024-04-05', '2024-04-20', 'inprogress'),
    ('ORD2024003', 3, 150.00, '2024-04-10', '2024-04-25', 'confirmed')
ON DUPLICATE KEY UPDATE 
    product_id = VALUES(product_id),
    target_quantity = VALUES(target_quantity),
    start_date = VALUES(start_date),
    expected_completion_date = VALUES(expected_completion_date),
    status = VALUES(status)";

try {
    $connect->exec($sample_orders_sql);
    echo "<p style='color:green'>Sample orders data inserted successfully</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error inserting sample orders: " . $e->getMessage() . "</p>";
}

// Insert sample data for quality_control
$sample_qc_sql = "INSERT INTO quality_control 
    (production_order_id, inspection_date, quantity_checked, quantity_passed, quantity_failed, defect_type, notes, status)
VALUES 
    (1, '2024-04-11', 25.00, 22.00, 3.00, 'Dimensional issue', 'Minor defects found in some pieces', 'partially_passed'),
    (2, '2024-04-11', 50.00, 50.00, 0.00, NULL, 'All items passed inspection', 'passed'),
    (1, '2024-04-10', 20.00, 15.00, 5.00, 'Surface finish', 'Multiple surface finish issues', 'partially_passed')";

try {
    $connect->exec($sample_qc_sql);
    echo "<p style='color:green'>Sample quality control data inserted successfully</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error inserting sample quality control data: " . $e->getMessage() . "</p>";
}

// Insert sample data for quality_defect_types
$sample_defect_types_sql = "INSERT INTO quality_defect_types 
    (defect_name, description, severity_level)
VALUES 
    ('Dimensional issue', 'Product dimensions outside of acceptable tolerance range', 'Medium'),
    ('Surface finish', 'Problems with the surface finish or appearance', 'Low'),
    ('Structural defect', 'Issues with the structural integrity of the product', 'High'),
    ('Assembly issue', 'Problems with component assembly or fit', 'Medium')";

try {
    $connect->exec($sample_defect_types_sql);
    echo "<p style='color:green'>Sample defect types data inserted successfully</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error inserting sample defect types: " . $e->getMessage() . "</p>";
}

// Insert sample data for quality_control_parameters
$sample_parameters_sql = "INSERT INTO quality_control_parameters 
    (parameter_name, parameter_type, unit_of_measure, acceptable_range)
VALUES 
    ('Length', 'Dimensional', 'mm', '100 ± 0.5'),
    ('Width', 'Dimensional', 'mm', '50 ± 0.3'),
    ('Weight', 'Physical', 'g', '250 ± 5'),
    ('Hardness', 'Material', 'HRC', '45-50'),
    ('Appearance', 'Visual', 'Rating', '4-5')";

try {
    $connect->exec($sample_parameters_sql);
    echo "<p style='color:green'>Sample quality parameters data inserted successfully</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error inserting sample quality parameters: " . $e->getMessage() . "</p>";
}

// Insert sample data for quality_inspection_points
$sample_inspection_points_sql = "INSERT INTO quality_inspection_points 
    (inspection_name, description, frequency, active)
VALUES 
    ('Incoming Materials', 'Inspection of raw materials at receiving', 'Every batch', 1),
    ('In-process Inspection', 'Quality check during production process', 'Hourly', 1),
    ('Final Inspection', 'Complete inspection before packaging', 'Every lot', 1),
    ('Packaging Check', 'Verification of proper packaging', 'Random sampling', 1)";

try {
    $connect->exec($sample_inspection_points_sql);
    echo "<p style='color:green'>Sample inspection points data inserted successfully</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error inserting sample inspection points: " . $e->getMessage() . "</p>";
}

// Insert sample data for quality_control_results
$sample_results_sql = "INSERT INTO quality_control_results 
    (control_date, parameter_id, measured_value, result_status, inspector_id)
VALUES 
    (NOW(), 1, '100.2', 'Passed', 1),
    (NOW(), 2, '50.4', 'Passed', 1),
    (NOW(), 3, '248', 'Passed', 1),
    (NOW(), 4, '47', 'Passed', 1),
    (NOW(), 5, '4', 'Passed', 1)";

try {
    $connect->exec($sample_results_sql);
    echo "<p style='color:green'>Sample quality results data inserted successfully</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>Error inserting sample quality results: " . $e->getMessage() . "</p>";
}

echo "<p style='color:green'>All tables and sample data have been created successfully</p>";
echo "<p><a href='test_db.php' class='btn btn-primary'>Test Database Connection</a> <a href='quality_control.php' class='btn btn-success'>Go to Quality Control Page</a></p>";

// Close the connection
$connect = null;
?>