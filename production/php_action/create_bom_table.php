<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Fix the include path
$root = realpath(dirname(__FILE__) . '/..');
require_once $root . '/includes/db_connect.php';

try {
    // Check database connection
    if (!$connect) {
        throw new Exception("Database connection failed");
    }

    // SQL to create bill_of_materials table
    $sql = "CREATE TABLE IF NOT EXISTS `bill_of_materials` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `product_id` int(11) NOT NULL,
        `raw_material_id` int(11) NOT NULL,
        `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
        `wastage` decimal(5,2) NOT NULL DEFAULT 0.00,
        `status` enum('active','inactive') NOT NULL DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `product_id` (`product_id`),
        KEY `raw_material_id` (`raw_material_id`),
        CONSTRAINT `bom_product_fk` FOREIGN KEY (`product_id`) REFERENCES `production_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `bom_raw_material_fk` FOREIGN KEY (`raw_material_id`) REFERENCES `raw_materials` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    // Execute the query
    if ($connect->exec($sql) !== false) {
        echo "Table 'bill_of_materials' created successfully";
    } else {
        throw new Exception("Error creating table: " . print_r($connect->errorInfo(), true));
    }

} catch (Exception $e) {
    error_log("Error creating BOM table: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
} 