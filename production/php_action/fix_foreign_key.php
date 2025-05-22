<?php
require_once 'core.php';
require_once 'db_connect.php';

try {
    // Start transaction
    $connect->begin_transaction();

    // Drop existing foreign key constraint
    $sql = "ALTER TABLE production_orders 
            DROP FOREIGN KEY production_orders_ibfk_1";
    $connect->query($sql);

    // Add new foreign key constraint with correct reference
    $sql = "ALTER TABLE production_orders 
            ADD CONSTRAINT fk_production_orders_product 
            FOREIGN KEY (product_id) 
            REFERENCES production_products(id)";
    $connect->query($sql);

    $connect->commit();
    echo "Foreign key constraint fixed successfully";

} catch(Exception $e) {
    $connect->rollback();
    echo "Error: " . $e->getMessage();
}

$connect->close();
?> 