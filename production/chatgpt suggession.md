✅ What You Can Do Instead

You can implement the entire calculation and logic using PHP, by:
🔄 Step-by-Step Equivalent Logic in PHP

Here’s how you can replicate what the procedure is trying to do in PHP:
1. Start a Transaction in PHP

$pdo->beginTransaction();

2. Get Production Order ID

$stmt = $pdo->prepare("SELECT id FROM production_orders WHERE order_number = ?");
$stmt->execute([$p_order_number]);
$order = $stmt->fetch();

if (!$order) {
    throw new Exception("Production order not found.");
}
$v_order_id = $order['id'];

3. Delete Existing Materials for the Order

$pdo->prepare("DELETE FROM production_order_materials WHERE production_order_id = ?")->execute([$v_order_id]);

4. Check If BOM Exists

$stmt = $pdo->prepare("SELECT * FROM product_bom WHERE product_id = ? AND status = 'active'");
$stmt->execute([$p_product_id]);
$bom = $stmt->fetchAll();

if (!$bom) {
    throw new Exception("No Bill of Materials found for this product.");
}

5. Loop Through BOM and Calculate Requirements

$error = false;
$error_message = '';

foreach ($bom as $row) {
    $required_quantity = $p_target_quantity * ($row['quantity_required'] * (1 + $row['wastage_percent'] / 100));

    // Check Stock
    $stmt = $pdo->prepare("
        SELECT current_stock, reserved_quantity 
        FROM raw_materials 
        WHERE id = ?
    ");
    $stmt->execute([$row['material_id']]);
    $material = $stmt->fetch();

    if (!$material || ($material['current_stock'] - $material['reserved_quantity']) < $required_quantity) {
        $error = true;
        $error_message = "Insufficient stock for material ID: " . $row['material_id'];
        break;
    }

    // Insert if stock is okay
    $pdo->prepare("INSERT INTO production_order_materials (production_order_id, material_id, required_quantity, consumed_quantity, reservation_status) VALUES (?, ?, ?, 0, 'pending')")
        ->execute([$v_order_id, $row['material_id'], $required_quantity]);
}

6. Commit or Rollback

if ($error) {
    $pdo->rollBack();
    throw new Exception($error_message);
} else {
    $pdo->commit();
}