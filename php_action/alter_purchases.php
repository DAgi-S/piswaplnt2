<?php
require_once 'core.php';

// SQL command to add payment tracking columns
$sql = "ALTER TABLE `purchases` 
        ADD COLUMN IF NOT EXISTS `payment_status` VARCHAR(20) DEFAULT 'Unpaid' AFTER `note`,
        ADD COLUMN IF NOT EXISTS `paid_amount` DECIMAL(25,2) DEFAULT 0.00 AFTER `payment_status`,
        ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`";

if($connect->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $connect->store_result()) {
            $result->free();
        }
    } while ($connect->more_results() && $connect->next_result());

    if ($connect->errno) {
        echo "Error executing queries: " . $connect->error;
    } else {
        // Update existing records to have 'Unpaid' status if not set
        $updateSql = "UPDATE `purchases` SET 
                     `payment_status` = 'Unpaid',
                     `paid_amount` = 0.00 
                     WHERE `payment_status` IS NULL";
        
        if($connect->query($updateSql)) {
            echo "Successfully added and initialized payment tracking columns";
        } else {
            echo "Error updating existing records: " . $connect->error;
        }
    }
} else {
    echo "Error adding columns: " . $connect->error;
}

$connect->close();

// If you need to run this directly in phpMyAdmin, use this SQL:
/*
ALTER TABLE `purchases` 
ADD COLUMN IF NOT EXISTS `payment_status` VARCHAR(20) DEFAULT 'Unpaid' AFTER `note`,
ADD COLUMN IF NOT EXISTS `paid_amount` DECIMAL(25,2) DEFAULT 0.00 AFTER `payment_status`,
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

UPDATE `purchases` SET 
`payment_status` = 'Unpaid',
`paid_amount` = 0.00 
WHERE `payment_status` IS NULL;
*/
?> 