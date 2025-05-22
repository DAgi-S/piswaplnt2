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
    die("Connection failed: " . $connect->connect_error);
}

// Create product table
$createTableQuery = "CREATE TABLE IF NOT EXISTS `product` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `product_image` text DEFAULT NULL,
  `brand_id` int(11) NOT NULL,
  `categories_id` int(11) NOT NULL,
  `quantity` varchar(255) NOT NULL,
  `rate` varchar(255) NOT NULL,
  `active` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`),
  KEY `brand_id` (`brand_id`),
  KEY `categories_id` (`categories_id`),
  CONSTRAINT `product_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `product_ibfk_2` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`categories_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

try {
    if($connect->query($createTableQuery)) {
        echo "Successfully created product table<br>";
        
        // Add some sample products
        $sampleProducts = array(
            array('Sample Product 1', 1, 1, '100', '10.99', 1, 1),
            array('Sample Product 2', 1, 1, '50', '15.99', 1, 1),
            array('Sample Product 3', 1, 1, '75', '20.99', 1, 1)
        );
        
        $insertQuery = "INSERT INTO product (product_name, brand_id, categories_id, quantity, rate, active, status) 
                       VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $connect->prepare($insertQuery);
        
        foreach($sampleProducts as $product) {
            $stmt->bind_param("siissii", $product[0], $product[1], $product[2], $product[3], $product[4], $product[5], $product[6]);
            $stmt->execute();
        }
        
        echo "Added sample products";
    } else {
        echo "Error creating table: " . $connect->error;
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}

$connect->close();
?> 