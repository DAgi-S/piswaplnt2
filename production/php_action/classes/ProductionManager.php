<?php
/**
 * ProductionManager Class
 * 
 * Handles production orders operations including:
 * - Material requirements calculation
 * - Production order creation and management
 * - Stock reservations for production
 */

class ProductionManager {
    private $conn;
    
    /**
     * Constructor
     * 
     * @param mixed $dbConnection Database connection object (mysqli or PDO)
     */
    public function __construct($dbConnection) {
        if (!$dbConnection) {
            throw new Exception("Database connection required");
        }
        $this->conn = $dbConnection;
    }
    
    /**
     * Calculate material requirements for a production order
     * 
     * @param string $orderNumber The production order number
     * @param int $productId The product ID to be produced
     * @param float $targetQuantity The quantity to be produced
     * @return array Status and message with requirements data
     */
    public function calculateProductionRequirements($orderNumber, $productId, $targetQuantity) {
        try {
            // Validate inputs
            if (empty($orderNumber)) {
                return ['status' => false, 'message' => 'Order number is required'];
            }
            
            if (empty($productId) || !is_numeric($productId)) {
                return ['status' => false, 'message' => 'Valid product ID is required'];
            }
            
            if (empty($targetQuantity) || !is_numeric($targetQuantity) || $targetQuantity <= 0) {
                return ['status' => false, 'message' => 'Valid target quantity is required'];
            }
            
            // First, check if the product exists
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare("SELECT * FROM production_products WHERE id = ?");
                $stmt->execute([$productId]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->conn->prepare("SELECT * FROM production_products WHERE id = ?");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();
                $stmt->close();
            }
            
            if (!$product) {
                return ['status' => false, 'message' => 'Product not found'];
            }
            
            // Make sure product has a product_name field
            if (!isset($product['product_name']) && isset($product['name'])) {
                $product['product_name'] = $product['name'];
            }
            
            // Get BOM (Bill of Materials) for the product
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare("
                    SELECT b.*, m.name as material_name, m.unit, 
                           COALESCE(m.cost_per_unit, 0) as unit_cost
                    FROM product_bom b
                    JOIN raw_materials m ON b.material_id = m.id
                    WHERE b.product_id = ?
                ");
                $stmt->execute([$productId]);
                $bomItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT b.*, m.name as material_name, m.unit, 
                           COALESCE(m.cost_per_unit, 0) as unit_cost
                    FROM product_bom b
                    JOIN raw_materials m ON b.material_id = m.id
                    WHERE b.product_id = ?
                ");
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $result = $stmt->get_result();
                $bomItems = [];
                while ($row = $result->fetch_assoc()) {
                    $bomItems[] = $row;
                }
                $stmt->close();
            }
            
            if (empty($bomItems)) {
                return ['status' => false, 'message' => 'No BOM (Bill of Materials) found for this product'];
            }
            
            // Calculate required quantities for each material
            $requiredMaterials = [];
            $totalCost = 0;
            
            foreach ($bomItems as $item) {
                // Calculate with wastage if available
                $wastagePercent = isset($item['wastage_percent']) ? floatval($item['wastage_percent']) : 0;
                $quantityWithWastage = $item['quantity_required'] * (1 + ($wastagePercent / 100));
                $requiredQty = $targetQuantity * $quantityWithWastage;
                
                $unitCost = isset($item['unit_cost']) ? floatval($item['unit_cost']) : 0;
                $materialCost = $requiredQty * $unitCost;
                $totalCost += $materialCost;
                
                // Add debugging info
                $debug = [
                    'material_id' => $item['material_id'],
                    'material_name' => $item['material_name'],
                    'unit_cost_isset' => isset($item['unit_cost']),
                    'unit_cost_value' => $unitCost,
                    'required_qty' => $requiredQty,
                    'calculated_cost' => $materialCost
                ];
                
                $requiredMaterials[] = [
                    'material_id' => $item['material_id'],
                    'material_name' => $item['material_name'],
                    'unit' => $item['unit'],
                    'quantity_per_unit' => $item['quantity_required'],
                    'wastage_percent' => $wastagePercent,
                    'required_quantity' => $requiredQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $materialCost,
                    'debug' => $debug
                ];
            }
            
            // Check if the order exists, create or update material requirements
            $this->updateOrderMaterials($orderNumber, $productId, $requiredMaterials);
            
            return [
                'status' => true,
                'message' => 'Production requirements calculated successfully',
                'data' => [
                    'product' => $product,
                    'required_materials' => $requiredMaterials,
                    'total_cost' => $totalCost,
                    'target_quantity' => $targetQuantity
                ]
            ];
            
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Error calculating requirements: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update the material requirements for a production order
     * 
     * @param string $orderNumber The production order number
     * @param int $productId The product ID
     * @param array $requiredMaterials Array of required materials with quantities
     * @return bool Success status
     */
    private function updateOrderMaterials($orderNumber, $productId, $requiredMaterials) {
        try {
            // First, get the order ID
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare("SELECT id FROM production_orders WHERE order_number = ?");
                $stmt->execute([$orderNumber]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->conn->prepare("SELECT id FROM production_orders WHERE order_number = ?");
                $stmt->bind_param("s", $orderNumber);
                $stmt->execute();
                $result = $stmt->get_result();
                $order = $result->fetch_assoc();
                $stmt->close();
            }
            
            if (!$order) {
                throw new Exception("Production order not found: $orderNumber");
            }
            
            $orderId = $order['id'];
            
            // Begin transaction
            if ($this->conn instanceof PDO) {
                $this->conn->beginTransaction();
            } else {
                $this->conn->begin_transaction();
            }
            
            // Delete existing material requirements for this order
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare("DELETE FROM production_order_materials WHERE production_order_id = ?");
                $stmt->execute([$orderId]);
            } else {
                $stmt = $this->conn->prepare("DELETE FROM production_order_materials WHERE production_order_id = ?");
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $stmt->close();
            }
            
            // Insert new material requirements
            foreach ($requiredMaterials as $material) {
                if ($this->conn instanceof PDO) {
                    $stmt = $this->conn->prepare("
                        INSERT INTO production_order_materials 
                        (production_order_id, material_id, required_quantity, reserved_quantity, consumed_quantity, reservation_status) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $orderId,
                        $material['material_id'],
                        $material['required_quantity'],
                        0.0,
                        0.0,
                        'pending'
                    ]);
                } else {
                    $stmt = $this->conn->prepare("
                        INSERT INTO production_order_materials 
                        (production_order_id, material_id, required_quantity, reserved_quantity, consumed_quantity, reservation_status) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $zero = 0.0;
                    $status = 'pending';
                    $stmt->bind_param("iiddss", $orderId, $material['material_id'], $material['required_quantity'], $zero, $zero, $status);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
            // Commit transaction
            if ($this->conn instanceof PDO) {
                $this->conn->commit();
            } else {
                $this->conn->commit();
            }
            
            return true;
            
        } catch (Exception $e) {
            // Rollback transaction
            if ($this->conn instanceof PDO) {
                $this->conn->rollBack();
            } else {
                $this->conn->rollback();
            }
            
            throw $e;
        }
    }
    
    /**
     * Create a new production order
     * 
     * @param array $orderData Order data including product_id, target_quantity, etc.
     * @return array Status and message with order ID
     */
    public function createProductionOrder($orderData) {
        try {
            // Validate required fields
            if (empty($orderData['product_id']) || !is_numeric($orderData['product_id'])) {
                return ['status' => false, 'message' => 'Valid product ID is required'];
            }
            
            // Enhanced target quantity validation with specific error messages
            if (!isset($orderData['target_quantity'])) {
                return ['status' => false, 'message' => 'Target quantity is required'];
            }
            
            if (!is_numeric($orderData['target_quantity'])) {
                return ['status' => false, 'message' => 'Target quantity must be a number'];
            }
            
            $targetQty = floatval($orderData['target_quantity']);
            if ($targetQty <= 0) {
                return ['status' => false, 'message' => 'Target quantity must be greater than zero'];
            }
            
            // Generate order number if not provided
            if (empty($orderData['order_number'])) {
                $orderData['order_number'] = 'PO-' . date('Ymd') . '-' . rand(1000, 9999);
            }
            
            // Set default values if not provided
            $orderData['status'] = $orderData['status'] ?? 'draft';
            $orderData['start_date'] = $orderData['start_date'] ?? date('Y-m-d');
            $orderData['expected_completion_date'] = $orderData['expected_completion_date'] ?? date('Y-m-d', strtotime('+7 days'));
            $orderData['completed_quantity'] = $orderData['completed_quantity'] ?? 0;
            $orderData['notes'] = $orderData['notes'] ?? '';
            $orderData['created_by'] = $orderData['created_by'] ?? null;
            
            // Insert production order
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare("
                    INSERT INTO production_orders 
                    (order_number, product_id, target_quantity, completed_quantity, 
                     start_date, expected_completion_date, status, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $orderData['order_number'],
                    $orderData['product_id'],
                    $orderData['target_quantity'],
                    $orderData['completed_quantity'],
                    $orderData['start_date'],
                    $orderData['expected_completion_date'],
                    $orderData['status'],
                    $orderData['notes'],
                    $orderData['created_by']
                ]);
                $orderId = $this->conn->lastInsertId();
            } else {
                $stmt = $this->conn->prepare("
                    INSERT INTO production_orders 
                    (order_number, product_id, target_quantity, completed_quantity, 
                     start_date, expected_completion_date, status, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param(
                    "siddssssi", 
                    $orderData['order_number'],
                    $orderData['product_id'],
                    $orderData['target_quantity'],
                    $orderData['completed_quantity'],
                    $orderData['start_date'],
                    $orderData['expected_completion_date'],
                    $orderData['status'],
                    $orderData['notes'],
                    $orderData['created_by']
                );
                $stmt->execute();
                $orderId = $stmt->insert_id;
                $stmt->close();
            }
            
            // Calculate material requirements for the order
            $reqResult = $this->calculateProductionRequirements(
                $orderData['order_number'], 
                $orderData['product_id'], 
                $orderData['target_quantity']
            );
            
            if (!$reqResult['status']) {
                return ['status' => false, 'message' => 'Order created but ' . $reqResult['message']];
            }
            
            return [
                'status' => true,
                'message' => 'Production order created successfully',
                'order_id' => $orderId,
                'order_number' => $orderData['order_number'],
                'materials' => $reqResult['data']['required_materials']
            ];
            
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Error creating production order: ' . $e->getMessage()];
        }
    }
    
    /**
     * Update the status of a production order
     * 
     * @param int $orderId The order ID
     * @param string $status The new status
     * @param string $notes Optional notes
     * @return array Status and message
     */
    public function updateOrderStatus($orderId, $status, $notes = '') {
        try {
            // Validate inputs
            if (empty($orderId) || !is_numeric($orderId)) {
                return ['status' => false, 'message' => 'Valid order ID is required'];
            }
            
            $validStatuses = ['draft', 'planned', 'in_progress', 'completed', 'cancelled'];
            if (empty($status) || !in_array($status, $validStatuses)) {
                return ['status' => false, 'message' => 'Valid status is required: ' . implode(', ', $validStatuses)];
            }
            
            // Update the order status
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare("
                    UPDATE production_orders 
                    SET status = ?, notes = CONCAT(notes, '\n', ?)
                    WHERE id = ?
                ");
                $updateNotes = "[Status updated to $status] " . $notes;
                $stmt->execute([$status, $updateNotes, $orderId]);
                $affected = $stmt->rowCount();
            } else {
                $stmt = $this->conn->prepare("
                    UPDATE production_orders 
                    SET status = ?, notes = CONCAT(IFNULL(notes, ''), '\n', ?)
                    WHERE id = ?
                ");
                $updateNotes = "[Status updated to $status] " . $notes;
                $stmt->bind_param("ssi", $status, $updateNotes, $orderId);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();
            }
            
            if ($affected === 0) {
                return ['status' => false, 'message' => 'Order not found or status unchanged'];
            }
            
            return ['status' => true, 'message' => "Order status updated to $status"];
            
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Error updating order status: ' . $e->getMessage()];
        }
    }
    
    /**
     * Record production progress for an order
     * 
     * @param int $orderId The order ID
     * @param float $quantity The quantity produced
     * @param string $notes Optional notes
     * @return array Status and message
     */
    public function recordProductionProgress($orderId, $quantity, $notes = '') {
        try {
            // Validate inputs
            if (empty($orderId) || !is_numeric($orderId)) {
                return ['status' => false, 'message' => 'Valid order ID is required'];
            }
            
            if (empty($quantity) || !is_numeric($quantity) || $quantity <= 0) {
                return ['status' => false, 'message' => 'Valid quantity is required'];
            }
            
            // Get the current order details
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare("
                    SELECT target_quantity, completed_quantity, status, created_by
                    FROM production_orders 
                    WHERE id = ?
                ");
                $stmt->execute([$orderId]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT target_quantity, completed_quantity, status, created_by 
                    FROM production_orders 
                    WHERE id = ?
                ");
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $result = $stmt->get_result();
                $order = $result->fetch_assoc();
                $stmt->close();
            }
            
            if (!$order) {
                return ['status' => false, 'message' => 'Order not found'];
            }
            
            // Get current user ID from session or use the order's creator as fallback
            $userId = null;
            if (isset($_SESSION['userId'])) {
                $userId = $_SESSION['userId'];
            } elseif (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
            } elseif (!empty($order['created_by'])) {
                $userId = $order['created_by'];
            }
            
            // Check if the order is in a valid status for progress update
            if ($order['status'] === 'cancelled' || $order['status'] === 'completed') {
                return ['status' => false, 'message' => "Cannot update progress for order in {$order['status']} status"];
            }
            
            // Calculate new completed quantity
            $newCompletedQty = $order['completed_quantity'] + $quantity;
            
            // Check if the new quantity exceeds the target
            if ($newCompletedQty > $order['target_quantity']) {
                return ['status' => false, 'message' => 'Completed quantity would exceed target quantity'];
            }
            
            // Begin transaction
            if ($this->conn instanceof PDO) {
                $this->conn->beginTransaction();
            } else {
                $this->conn->begin_transaction();
            }
            
            // Insert progress record
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare("
                    INSERT INTO production_progress 
                    (production_order_id, quantity, notes, created_by, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$orderId, $quantity, $notes, $userId]);
            } else {
                $stmt = $this->conn->prepare("
                    INSERT INTO production_progress 
                    (production_order_id, quantity, notes, created_by, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("idsi", $orderId, $quantity, $notes, $userId);
                $stmt->execute();
                $stmt->close();
            }
            
            // Update the order's completed quantity
            $newStatus = $order['status'];
            if ($newCompletedQty >= $order['target_quantity']) {
                $newStatus = 'completed';
            } else if ($order['status'] === 'draft' || $order['status'] === 'planned') {
                $newStatus = 'in_progress';
            }
            
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare("
                    UPDATE production_orders 
                    SET completed_quantity = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$newCompletedQty, $newStatus, $orderId]);
            } else {
                $stmt = $this->conn->prepare("
                    UPDATE production_orders 
                    SET completed_quantity = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->bind_param("dsi", $newCompletedQty, $newStatus, $orderId);
                $stmt->execute();
                $stmt->close();
            }
            
            // Commit transaction
            if ($this->conn instanceof PDO) {
                $this->conn->commit();
            } else {
                $this->conn->commit();
            }
            
            return [
                'status' => true, 
                'message' => "Progress recorded: $quantity units. Total completed: $newCompletedQty",
                'completed' => $newCompletedQty,
                'target' => $order['target_quantity'],
                'new_status' => $newStatus
            ];
            
        } catch (Exception $e) {
            // Rollback transaction
            if ($this->conn instanceof PDO) {
                $this->conn->rollBack();
            } else {
                $this->conn->rollback();
            }
            
            return ['status' => false, 'message' => 'Error recording progress: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get production order details including materials
     * 
     * @param int $orderId The order ID
     * @return array Status and message with order details
     */
    public function getProductionOrderDetails($orderId) {
        try {
            // Validate inputs
            if (empty($orderId) || !is_numeric($orderId)) {
                return ['status' => false, 'message' => 'Valid order ID is required'];
            }
            
            // Get order details
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare("
                    SELECT o.*, p.product_name, p.product_code, p.category, u.username as created_by_name
                    FROM production_orders o
                    LEFT JOIN production_products p ON o.product_id = p.id
                    LEFT JOIN users u ON o.created_by = u.user_id
                    WHERE o.id = ?
                ");
                $stmt->execute([$orderId]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT o.*, p.product_name, p.product_code, p.category, u.username as created_by_name
                    FROM production_orders o
                    LEFT JOIN production_products p ON o.product_id = p.id
                    LEFT JOIN users u ON o.created_by = u.user_id
                    WHERE o.id = ?
                ");
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $result = $stmt->get_result();
                $order = $result->fetch_assoc();
                $stmt->close();
            }
            
            if (!$order) {
                return ['status' => false, 'message' => 'Order not found'];
            }
            
            // Get materials for the order
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare("
                    SELECT m.*, r.name as material_name, r.unit
                    FROM production_order_materials m
                    JOIN raw_materials r ON m.material_id = r.id
                    WHERE m.production_order_id = ?
                ");
                $stmt->execute([$orderId]);
                $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT m.*, r.name as material_name, r.unit
                    FROM production_order_materials m
                    JOIN raw_materials r ON m.material_id = r.id
                    WHERE m.production_order_id = ?
                ");
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $result = $stmt->get_result();
                $materials = [];
                while ($row = $result->fetch_assoc()) {
                    $materials[] = $row;
                }
                $stmt->close();
            }
            
            // Get progress records
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare("
                    SELECT p.*, u.username as created_by_name
                    FROM production_progress p
                    LEFT JOIN users u ON p.created_by = u.user_id
                    WHERE p.production_order_id = ?
                    ORDER BY p.created_at DESC
                ");
                $stmt->execute([$orderId]);
                $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT p.*, u.username as created_by_name
                    FROM production_progress p
                    LEFT JOIN users u ON p.created_by = u.user_id
                    WHERE p.production_order_id = ?
                    ORDER BY p.created_at DESC
                ");
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $result = $stmt->get_result();
                $progress = [];
                while ($row = $result->fetch_assoc()) {
                    $progress[] = $row;
                }
                $stmt->close();
            }
            
            return [
                'status' => true,
                'order' => $order,
                'materials' => $materials,
                'progress' => $progress
            ];
            
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Error retrieving order details: ' . $e->getMessage()];
        }
    }

    /**
     * Get production orders with optional filters
     * 
     * @param array $filters Optional filters (status, date_range, product_id, search)
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return array List of production orders
     */
    public function getProductionOrders($filters = array(), $limit = 10, $offset = 0) {
        try {
            $sql = "SELECT po.*, p.product_name, p.product_code, p.product_image, 
                    u.username as created_by_username
                    FROM production_orders po
                    LEFT JOIN production_products p ON po.product_id = p.id
                    LEFT JOIN users u ON po.created_by = u.user_id";
            
            $where = array();
            $params = array();
            
            // Apply filters
            if (isset($filters['status']) && !empty($filters['status'])) {
                $where[] = "po.status = ?";
                $params[] = $filters['status'];
            }
            
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $where[] = "po.date_created >= ?";
                $params[] = $filters['start_date'] . ' 00:00:00';
            }
            
            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $where[] = "po.date_created <= ?";
                $params[] = $filters['end_date'] . ' 23:59:59';
            }
            
            if (isset($filters['product_id']) && !empty($filters['product_id'])) {
                $where[] = "po.product_id = ?";
                $params[] = $filters['product_id'];
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $where[] = "(po.order_number LIKE ? OR p.product_name LIKE ? OR p.product_code LIKE ?)";
                $searchTerm = "%" . $filters['search'] . "%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Combine WHERE clauses
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            // Add ordering
            $sql .= " ORDER BY po.date_created DESC";
            
            // Add limit and offset
            $sql .= " LIMIT ?, ?";
            $params[] = (int)$offset;
            $params[] = (int)$limit;
            
            // Handle different connection types (mysqli or PDO)
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare($sql);
                
                // Bind parameters for PDO
                if (!empty($params)) {
                    foreach ($params as $index => $param) {
                        $stmt->bindValue($index + 1, $param);
                    }
                }
                
                $stmt->execute();
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->conn->prepare($sql);
                
                if ($stmt === false) {
                    throw new Exception("Failed to prepare statement: " . $this->conn->error);
                }
                
                // Bind parameters dynamically for mysqli
                if (!empty($params)) {
                    $types = '';
                    foreach ($params as $param) {
                        if (is_int($param)) {
                            $types .= 'i';
                        } elseif (is_float($param)) {
                            $types .= 'd';
                        } else {
                            $types .= 's';
                        }
                    }
                    
                    $stmt->bind_param($types, ...$params);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                $orders = array();
                while ($row = $result->fetch_assoc()) {
                    $orders[] = $row;
                }
                
                $stmt->close();
            }
            
            // Process each order for additional data
            foreach ($orders as &$order) {
                // Calculate completion percentage
                $completionPercentage = 0;
                if (isset($order['target_quantity']) && $order['target_quantity'] > 0) {
                    $completionPercentage = round(($order['completed_quantity'] / $order['target_quantity']) * 100);
                }
                $order['completion_percentage'] = $completionPercentage;
                
                // Format dates for readability
                if (isset($order['date_created'])) {
                    $order['formatted_date_created'] = date('M d, Y', strtotime($order['date_created']));
                }
                if (isset($order['expected_completion_date'])) {
                    $order['formatted_target_date'] = date('M d, Y', strtotime($order['expected_completion_date']));
                }
            }
            
            return $orders;
            
        } catch (Exception $e) {
            throw new Exception("Failed to get production orders: " . $e->getMessage());
        }
    }

    /**
     * Get total count of production orders with filters
     * 
     * @param array $filters Optional filters
     * @return int Total count of matching production orders
     */
    public function getProductionOrdersCount($filters = array()) {
        try {
            $sql = "SELECT COUNT(*) as total FROM production_orders po
                    LEFT JOIN production_products p ON po.product_id = p.id";
            
            $where = array();
            $params = array();
            
            // Apply filters
            if (isset($filters['status']) && !empty($filters['status'])) {
                $where[] = "po.status = ?";
                $params[] = $filters['status'];
            }
            
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $where[] = "po.date_created >= ?";
                $params[] = $filters['start_date'] . ' 00:00:00';
            }
            
            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $where[] = "po.date_created <= ?";
                $params[] = $filters['end_date'] . ' 23:59:59';
            }
            
            if (isset($filters['product_id']) && !empty($filters['product_id'])) {
                $where[] = "po.product_id = ?";
                $params[] = $filters['product_id'];
            }
            
            if (isset($filters['search']) && !empty($filters['search'])) {
                $where[] = "(po.order_number LIKE ? OR p.product_name LIKE ? OR p.product_code LIKE ?)";
                $searchTerm = "%" . $filters['search'] . "%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Combine WHERE clauses
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            // Handle different connection types (mysqli or PDO)
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->prepare($sql);
                
                // Bind parameters for PDO
                if (!empty($params)) {
                    foreach ($params as $index => $param) {
                        $stmt->bindValue($index + 1, $param);
                    }
                }
                
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['total'];
            } else {
                $stmt = $this->conn->prepare($sql);
                
                if ($stmt === false) {
                    throw new Exception("Failed to prepare statement: " . $this->conn->error);
                }
                
                // Bind parameters dynamically for mysqli
                if (!empty($params)) {
                    $types = '';
                    foreach ($params as $param) {
                        if (is_int($param)) {
                            $types .= 'i';
                        } elseif (is_float($param)) {
                            $types .= 'd';
                        } else {
                            $types .= 's';
                        }
                    }
                    
                    $stmt->bind_param($types, ...$params);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                
                return $row['total'];
            }
        } catch (Exception $e) {
            throw new Exception("Failed to get production orders count: " . $e->getMessage());
        }
    }
} 