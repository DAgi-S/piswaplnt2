<?php
require_once 'core.php';
require_once '../includes/db_connect.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('success' => false, 'messages' => 'Invalid request method'));
    exit();
}

try {
    // Validate required fields
    $required_fields = ['item_id', 'item_type', 'movement_type', 'quantity', 'warehouse_id', 'reference_type', 'reference_id'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("$field is required");
        }
    }

    // Start transaction
    $connect->beginTransaction();

    try {
        // Get current stock
        $stmt = $connect->prepare("SELECT current_stock FROM production_products WHERE id = ?");
        $stmt->execute([$_POST['item_id']]);
        $current_stock = $stmt->fetchColumn();

        if ($current_stock === false) {
            throw new Exception("Product not found");
        }

        // Calculate new stock
        $quantity = floatval($_POST['quantity']);
        $new_stock = $current_stock;
        
        if ($_POST['movement_type'] === 'in') {
            $new_stock += $quantity;
        } else {
            if ($quantity > $current_stock) {
                throw new Exception("Cannot remove more than current stock");
            }
            $new_stock -= $quantity;
        }

        // Insert movement record
        $sql = "INSERT INTO stock_movements (
                    product_id,
                    movement_type,
                    quantity,
                    warehouse_id,
                    reference_type,
                    reference_id,
                    notes,
                    created_at,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

        $stmt = $connect->prepare($sql);
        $stmt->execute([
            $_POST['item_id'],
            $_POST['movement_type'],
            $quantity,
            $_POST['warehouse_id'],
            $_POST['reference_type'],
            $_POST['reference_id'],
            $_POST['notes'] ?? '',
            isset($_SESSION['userId']) ? $_SESSION['userId'] : null
        ]);

        // Update product stock
        $sql = "UPDATE production_products SET current_stock = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->execute([$new_stock, $_POST['item_id']]);

        // Commit transaction
        $connect->commit();

        echo json_encode([
            'success' => true,
            'messages' => 'Stock movement recorded successfully'
        ]);

    } catch (Exception $e) {
        $connect->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in createStockMovement.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'messages' => $e->getMessage()
    ]);
}

// Close the connection
if (isset($connect)) {
    $connect->close();
}
?> 