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

    // Get BOM items
    $stmt = $connect->prepare("
        SELECT b.*, rm.name as material_name, rm.unit
        FROM product_bom b
        JOIN raw_materials rm ON b.material_id = rm.id
        WHERE b.product_id = ?
        ORDER BY rm.name ASC
    ");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $bomItems = $result->fetch_all(MYSQLI_ASSOC);

    // Start output buffering
    ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bill of Materials - <?php echo htmlspecialchars($product['name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .product-info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Bill of Materials</h2>
        <button class="no-print" onclick="window.print()">Print</button>
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
                <th>Quantity Required</th>
                <th>Unit</th>
                <th>Wastage %</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bomItems as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['material_name']); ?></td>
                <td><?php echo number_format($item['quantity_required'], 2); ?></td>
                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                <td><?php echo number_format($item['wastage_percent'], 2); ?>%</td>
                <td><?php echo strtoupper(htmlspecialchars($item['status'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        <p>Printed on <?php echo date('Y-m-d H:i:s'); ?></p>
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