<?php
require_once 'db_connect.php';

$sql = "CREATE TABLE IF NOT EXISTS stock_movements (
    movement_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT,
    reference_type VARCHAR(50),
    reference_id VARCHAR(50),
    quantity DECIMAL(10,2),
    movement_type ENUM('in', 'out'),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
)";

if ($connect->query($sql)) {
    echo "Table stock_movements created successfully";
} else {
    echo "Error creating table: " . $connect->error;
}

$connect->close(); 