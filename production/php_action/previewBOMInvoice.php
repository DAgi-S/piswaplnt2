<?php
require_once 'core.php';
require_once 'db_connect.php';

if (!isset($_GET['product_id'])) {
    die('Product ID is required');
}

$productId = (int)$_GET['product_id'];

try {
    // Get product details
    $stmt = $connect->prepare("
        SELECT p.*, c.name as category_name, b.name as brand_name 
        FROM production_products p 
        LEFT JOIN production_categories c ON p.category_id = c.id 
        LEFT JOIN production_brands b ON p.brand_id = b.id 
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        die('Product not found');
    }

    // Get BOM items with DISTINCT to prevent duplicates
    $stmt = $connect->prepare("
        SELECT DISTINCT 
            b.id,
            b.material_id,
            rm.name as material_name,
            b.quantity_required,
            rm.unit,
            rm.cost_per_unit,
            b.wastage_percent,
            b.status
        FROM product_bom b
        JOIN raw_materials rm ON b.material_id = rm.id
        WHERE b.product_id = ? AND b.status = 'active'
        GROUP BY b.material_id
        ORDER BY rm.name ASC
    ");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $bomItems = $result->fetch_all(MYSQLI_ASSOC);

    // Calculate totals
    $totalCost = 0;
    foreach ($bomItems as &$item) {
        $quantity = $item['quantity_required'];
        $wastage = $quantity * ($item['wastage_percent'] / 100);
        $totalQuantity = $quantity + $wastage;
        $item['total_quantity'] = $totalQuantity;
        $item['item_cost'] = $totalQuantity * $item['cost_per_unit'];
        $totalCost += $item['item_cost'];
    }

    // Start output buffering
    ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>BOM Invoice - <?php echo htmlspecialchars($product['name']); ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            border-bottom: 2px solid #333;
        }
        .product-info {
            margin-bottom: 30px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .product-info h3 {
            margin-top: 0;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .totals {
            margin-top: 30px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
            .header { border-bottom-color: #000; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Bill of Materials Invoice</h2>
        <button class="no-print" onclick="window.print()">Print Invoice</button>
    </div>

    <div class="product-info">
        <h3>Product Details</h3>
        <p><strong>Product Code:</strong> <?php echo htmlspecialchars($product['product_code']); ?></p>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($product['name']); ?></p>
        <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
        <p><strong>Brand:</strong> <?php echo htmlspecialchars($product['brand_name']); ?></p>
        <p><strong>Unit:</strong> <?php echo htmlspecialchars($product['unit']); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Raw Material</th>
                <th>Base Quantity</th>
                <th>Wastage %</th>
                <th>Total Quantity</th>
                <th>Unit</th>
                <th>Cost per Unit</th>
                <th>Total Cost</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bomItems as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['material_name']); ?></td>
                <td><?php echo number_format($item['quantity_required'], 2); ?></td>
                <td><?php echo number_format($item['wastage_percent'], 2); ?>%</td>
                <td><?php echo number_format($item['total_quantity'], 2); ?></td>
                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                <td><?php echo number_format($item['cost_per_unit'], 2); ?></td>
                <td><?php echo number_format($item['item_cost'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <p><strong>Total Material Cost:</strong> <?php echo number_format($totalCost, 2); ?></p>
        <p><strong>Production Cost:</strong> <?php echo number_format($product['production_cost'], 2); ?></p>
        <p><strong>Total Cost:</strong> <?php echo number_format($totalCost + $product['production_cost'], 2); ?></p>
    </div>

    <div class="footer">
        <p>Generated on <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>
</body>
</html>
<?php
    // Output the buffer
    echo ob_get_clean();

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

$connect->close(); 