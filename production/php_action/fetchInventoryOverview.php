<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set header type to JSON
header('Content-Type: application/json');

// Get the year parameter
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

try {
    // Create products table if not exists
    $createProductsTable = "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        category VARCHAR(100),
        unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        quantity INT NOT NULL DEFAULT 0,
        reorder_level INT NOT NULL DEFAULT 10,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $connect->query($createProductsTable);

    // Create raw_materials table if not exists
    $createMaterialsTable = "CREATE TABLE IF NOT EXISTS raw_materials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        supplier VARCHAR(255),
        unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        quantity INT NOT NULL DEFAULT 0,
        reorder_level INT NOT NULL DEFAULT 10,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $connect->query($createMaterialsTable);

    // Insert sample data for products if empty
    $checkProducts = $connect->query("SELECT COUNT(*) as count FROM products")->fetch_assoc();
    if ($checkProducts['count'] == 0) {
        $connect->query("INSERT INTO products (name, description, category, unit_price, quantity, reorder_level) VALUES 
            ('Product A', 'Description for Product A', 'Category 1', 100.00, 5, 10),
            ('Product B', 'Description for Product B', 'Category 2', 150.00, 8, 15),
            ('Product C', 'Description for Product C', 'Category 1', 200.00, 12, 20)"
        );
    }

    // Insert sample data for raw_materials if empty
    $checkMaterials = $connect->query("SELECT COUNT(*) as count FROM raw_materials")->fetch_assoc();
    if ($checkMaterials['count'] == 0) {
        $connect->query("INSERT INTO raw_materials (name, description, supplier, unit_price, quantity, reorder_level) VALUES 
            ('Material X', 'Description for Material X', 'Supplier 1', 50.00, 8, 15),
            ('Material Y', 'Description for Material Y', 'Supplier 2', 75.00, 6, 12),
            ('Material Z', 'Description for Material Z', 'Supplier 1', 60.00, 10, 20)"
        );
    }

    // Check if inventory_overview table exists
    $tableCheck = $connect->query("SHOW TABLES LIKE 'inventory_overview'");
    if ($tableCheck->num_rows == 0) {
        // Create inventory_overview table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS inventory_overview (
            id INT AUTO_INCREMENT PRIMARY KEY,
            item_type ENUM('product', 'material') NOT NULL,
            item_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 0,
            reorder_level INT NOT NULL DEFAULT 0,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY item_unique (item_type, item_id)
        )";
        $connect->query($createTable);

        // Insert some sample data
        $sampleData = "INSERT INTO inventory_overview 
            (item_type, item_id, quantity, reorder_level, unit_price)
            VALUES 
            ('product', 1, 50, 20, 100.00),
            ('material', 1, 200, 50, 25.00)";
        $connect->query($sampleData);
    }

    // Get products stock distribution
    $productsQuery = "SELECT 
        COUNT(*) as totalCount,
        SUM(quantity * unit_price) as totalValue,
        SUM(CASE WHEN quantity > reorder_level THEN 1 ELSE 0 END) as normal,
        SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END) as low
        FROM inventory_overview 
        WHERE item_type = 'product'";
    
    $productsResult = $connect->query($productsQuery);
    $productsData = $productsResult->fetch_assoc();

    // Get materials stock distribution
    $materialsQuery = "SELECT 
        COUNT(*) as totalCount,
        SUM(quantity * unit_price) as totalValue,
        SUM(CASE WHEN quantity > reorder_level THEN 1 ELSE 0 END) as normal,
        SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END) as low
        FROM inventory_overview 
        WHERE item_type = 'material'";
    
    $materialsResult = $connect->query($materialsQuery);
    $materialsData = $materialsResult->fetch_assoc();

    // Fetch low stock products
    $productQuery = "SELECT 
        product_name,
        quantity,
        reorder_level,
        CASE 
            WHEN quantity = 0 THEN 'Out of Stock'
            WHEN quantity <= reorder_level THEN 'Low Stock'
        END as status
    FROM products 
    WHERE quantity <= reorder_level
    ORDER BY quantity ASC";
    
    $productResult = $connect->query($productQuery);
    $lowStockProducts = [];
    
    while ($row = $productResult->fetch_assoc()) {
        $lowStockProducts[] = [
            'name' => $row['product_name'],
            'quantity' => (int)$row['quantity'],
            'reorderLevel' => (int)$row['reorder_level'],
            'status' => $row['status']
        ];
    }

    // Fetch low stock raw materials
    $materialQuery = "SELECT 
        material_name,
        quantity,
        reorder_level,
        CASE 
            WHEN quantity = 0 THEN 'Out of Stock'
            WHEN quantity <= reorder_level THEN 'Low Stock'
        END as status
    FROM raw_materials 
    WHERE quantity <= reorder_level
    ORDER BY quantity ASC";
    
    $materialResult = $connect->query($materialQuery);
    $lowStockMaterials = [];
    
    while ($row = $materialResult->fetch_assoc()) {
        $lowStockMaterials[] = [
            'name' => $row['material_name'],
            'quantity' => (int)$row['quantity'],
            'reorderLevel' => (int)$row['reorder_level'],
            'status' => $row['status']
        ];
    }

    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'productsStock' => array(
                'labels' => ['Normal Stock', 'Low Stock'],
                'data' => [(int)$productsData['normal'], (int)$productsData['low']],
                'totalCount' => (int)$productsData['totalCount'],
                'totalValue' => (float)$productsData['totalValue']
            ),
            'materialsStock' => array(
                'labels' => ['Normal Stock', 'Low Stock'],
                'data' => [(int)$materialsData['normal'], (int)$materialsData['low']],
                'totalCount' => (int)$materialsData['totalCount'],
                'totalValue' => (float)$materialsData['totalValue']
            ),
            'lowStockProducts' => $lowStockProducts,
            'lowStockMaterials' => $lowStockMaterials
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error fetching inventory overview: ' . $e->getMessage()
    ];
    http_response_code(500);
    echo json_encode($response);
}

$connect->close(); 