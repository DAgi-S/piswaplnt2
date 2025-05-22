<?php
require_once 'core.php';




try {
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/../sql/create_production_tables.sql');
    
    // Execute multiple SQL statements
    if ($connect->multi_query($sql)) {
        do {
            // Store first result set
            if ($result = $connect->store_result()) {
                $result->free();
            }
        } while ($connect->next_result());
    }
    
    echo "Production tables created successfully!";
    
} catch (Exception $e) {
    echo "Error creating tables: " . $e->getMessage();
}

$connect->close();