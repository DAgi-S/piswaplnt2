<?php
require_once 'core.php';
require_once 'db_connect.php';

try {
    // Start transaction
    $connect->begin_transaction();

    // Create production categories if not exists
    $sql = "INSERT INTO production_categories (name, description, status, created_by, created_at) 
            VALUES 
            ('Raw Materials', 'Raw materials used in production', 'active', 1, NOW()),
            ('Semi-Finished', 'Semi-finished products', 'active', 1, NOW()),
            ('Finished Products', 'Final products ready for sale', 'active', 1, NOW()),
            ('Packaging', 'Packaging materials', 'active', 1, NOW())";
    
    if($connect->query($sql)) {
        echo "Production categories created successfully\n";
    }

    // Create production brands if not exists
    $sql = "INSERT INTO production_brands (name, description, status, created_by, created_at)
            VALUES 
            ('House Brand', 'Our own brand', 'active', 1, NOW()),
            ('OEM', 'Original Equipment Manufacturer', 'active', 1, NOW())";
    
    if($connect->query($sql)) {
        echo "Production brands created successfully\n";
    }

    $connect->commit();
    echo "Initial data created successfully";

} catch(Exception $e) {
    $connect->rollback();
    echo "Error: " . $e->getMessage();
}

$connect->close();
?> 