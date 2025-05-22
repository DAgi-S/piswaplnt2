<?php
require_once 'core.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Add withholding tax columns to purchases table
    $sql = "ALTER TABLE purchases 
            ADD COLUMN IF NOT EXISTS withholding_tax_enabled TINYINT(1) DEFAULT 0,
            ADD COLUMN IF NOT EXISTS withholding_tax_amount DECIMAL(25,2) DEFAULT 0.00";
    
    if ($connect->query($sql) === TRUE) {
        echo "Withholding tax columns added to purchases table successfully<br>";
    } else {
        echo "Error adding columns to purchases table: " . $connect->error . "<br>";
    }

    // Add withholding tax columns to orders table
    $sql = "ALTER TABLE orders 
            ADD COLUMN IF NOT EXISTS withholding_tax_enabled TINYINT(1) DEFAULT 0,
            ADD COLUMN IF NOT EXISTS withholding_tax_amount DECIMAL(25,2) DEFAULT 0.00";
    
    if ($connect->query($sql) === TRUE) {
        echo "Withholding tax columns added to orders table successfully<br>";
    } else {
        echo "Error adding columns to orders table: " . $connect->error . "<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$connect->close(); 