<?php
require_once 'db_connect.php';
require_once 'core.php';

// Prepare response array
$response = array(
    'success' => false,
    'messages' => ''
);

if($_POST) {
    $userId = $_SESSION['userId'];
    
    // Sanitize and validate inputs
    $orderNumber = isset($_POST['orderNumber']) ? mysqli_real_escape_string($connect, $_POST['orderNumber']) : '';
    $targetQuantity = filter_var($_POST['targetQuantity'], FILTER_VALIDATE_INT);
    $startDate = mysqli_real_escape_string($connect, $_POST['startDate']);
    $completionDate = mysqli_real_escape_string($connect, $_POST['completionDate']);
    $notes = isset($_POST['notes']) ? mysqli_real_escape_string($connect, $_POST['notes']) : '';

    // Validate required fields
    if(!$targetQuantity || $targetQuantity <= 0) {
        $response['messages'] = 'Invalid target quantity';
        echo json_encode($response);
        exit();
    }

    // Generate order number if not provided
    if(empty($orderNumber)) {
        $orderNumber = 'PO-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
    }

    try {
        // Start transaction
        $connect->begin_transaction();

        // Insert production order
        $sql = "INSERT INTO production_orders (
                    order_number, status, target_quantity, start_date, 
                    completion_date, notes, created_by
                ) VALUES (?, 'draft', ?, ?, ?, ?, ?)";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('sisssi', 
            $orderNumber, $targetQuantity, $startDate, 
            $completionDate, $notes, $userId
        );
        
        if(!$stmt->execute()) {
            throw new Exception("Error creating production order: " . $stmt->error);
        }
        
        $productionOrderId = $connect->insert_id;

        // Process materials
        if(!isset($_POST['materials']) || !is_array($_POST['materials'])) {
            throw new Exception("No materials specified");
        }

        // Prepare statement for materials
        $materialSql = "INSERT INTO production_order_materials (
                           production_order_id, product_id, required_quantity
                       ) VALUES (?, ?, ?)";
        $materialStmt = $connect->prepare($materialSql);

        // Check stock availability for all materials
        foreach($_POST['materials'] as $key => $productId) {
            if(!isset($_POST['quantities'][$key])) {
                throw new Exception("Missing quantity for material #" . ($key + 1));
            }

            $quantity = filter_var($_POST['quantities'][$key], FILTER_VALIDATE_FLOAT);
            if(!$quantity || $quantity <= 0) {
                throw new Exception("Invalid quantity for material #" . ($key + 1));
            }

            // Check stock availability
            $stockSql = "SELECT quantity FROM products WHERE product_id = ?";
            $stockStmt = $connect->prepare($stockSql);
            $stockStmt->bind_param('i', $productId);
            $stockStmt->execute();
            $result = $stockStmt->get_result();
            
            if($result->num_rows === 0) {
                throw new Exception("Product not found: " . $productId);
            }
            
            $currentStock = $result->fetch_assoc()['quantity'];
            if($currentStock < $quantity) {
                throw new Exception("Insufficient stock for product ID: " . $productId);
            }

            // Insert material requirement
            $materialStmt->bind_param('iid', $productionOrderId, $productId, $quantity);
            if(!$materialStmt->execute()) {
                throw new Exception("Error adding material: " . $materialStmt->error);
            }
        }

        // Log the production order creation in stock movements
        $movementSql = "INSERT INTO stock_movements (
                           product_id, reference_type, reference_id, 
                           quantity, movement_type, notes, created_by
                       ) VALUES (?, 'production_order', ?, ?, 'out', ?, ?)";
        $movementStmt = $connect->prepare($movementSql);

        foreach($_POST['materials'] as $key => $productId) {
            $quantity = $_POST['quantities'][$key];
            $movementNote = "Reserved for Production Order: " . $orderNumber;
            $movementStmt->bind_param('iidsi', 
                $productId, $productionOrderId, $quantity, $movementNote, $userId
            );
            if(!$movementStmt->execute()) {
                throw new Exception("Error logging stock movement: " . $movementStmt->error);
            }
        }

        // Commit transaction
        $connect->commit();
        
        $response['success'] = true;
        $response['messages'] = 'Production order created successfully';
        
    } catch(Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        $response['messages'] = $e->getMessage();
    }
}

// Close database connection
$connect->close();

echo json_encode($response); 