<?php
require_once 'core.php';
require_once 'db_connect.php';

class StockMovementManager {
    private $conn;
    private $user_id;

    public function __construct($conn, $user_id) {
        $this->conn = $conn;
        $this->user_id = $user_id;
    }

    /**
     * Create a new stock movement with transaction support
     */
    public function createStockMovement($data) {
        try {
            // Start transaction
            $this->conn->begin_transaction();

            // Validate data
            $this->validateMovementData($data);

            // Check stock availability for outgoing movements
            if ($data['movement_type'] === 'out') {
                $this->checkStockAvailability($data);
            }

            // Insert movement record
            $sql = "INSERT INTO stock_movements (
                product_id,
                movement_type,
                quantity,
                reference_type,
                reference_id,
                notes,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "isdsisi",
                $data['item_id'],
                $data['movement_type'],
                $data['quantity'],
                $data['reference_type'],
                $data['reference_id'],
                $data['notes'],
                $this->user_id
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to create movement record: " . $stmt->error);
            }

            $movement_id = $stmt->insert_id;

            // Update warehouse stock
            $this->updateWarehouseStock($data);

            // Add to audit log
            $this->addAuditLog($movement_id, $data);

            // Commit transaction
            $this->conn->commit();

            return [
                'success' => true,
                'messages' => 'Stock movement created successfully',
                'movement_id' => $movement_id
            ];

        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            return [
                'success' => false,
                'messages' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate movement data
     */
    private function validateMovementData($data) {
        // Required fields validation
        $required_fields = ['item_id', 'quantity', 'movement_type', 
                          'reference_type', 'reference_id'];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        // Validate quantity
        if (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
            throw new Exception("Invalid quantity value");
        }

        // Validate item exists
        $item_exists = $this->checkItemExists($data['item_id']);
        if (!$item_exists) {
            throw new Exception("Invalid item");
        }

        // Validate reference exists
        $reference_exists = $this->checkReferenceExists($data['reference_type'], $data['reference_id']);
        if (!$reference_exists) {
            throw new Exception("Invalid reference");
        }

        // Validate warehouse
        if (isset($data['warehouse_id'])) {
            $warehouse_exists = $this->checkWarehouseExists($data['warehouse_id']);
            if (!$warehouse_exists) {
                throw new Exception("Invalid warehouse");
            }
        }

        // Check for duplicate movement
        if ($this->isDuplicateMovement($data)) {
            throw new Exception("Duplicate movement detected");
        }
    }

    /**
     * Check if item exists
     */
    private function checkItemExists($item_id) {
        $sql = "SELECT product_id FROM products WHERE product_id = ? AND status = 'active'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    /**
     * Check if reference exists
     */
    private function checkReferenceExists($reference_type, $reference_id) {
        $valid_references = [
            'purchase' => 'purchases',
            'sale' => 'sales_orders',
            'production' => 'production_orders',
            'adjustment' => 'inventory_adjustments'
        ];

        if (!isset($valid_references[$reference_type])) {
            return false;
        }

        $table = $valid_references[$reference_type];
        $sql = "SELECT id FROM {$table} WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $reference_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    /**
     * Check warehouse exists
     */
    private function checkWarehouseExists($warehouse_id) {
        $sql = "SELECT id FROM warehouses WHERE id = ? AND status = 'active'";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $warehouse_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    /**
     * Check for duplicate movement
     */
    private function isDuplicateMovement($data) {
        $sql = "SELECT movement_id FROM stock_movements 
                WHERE product_id = ? 
                AND reference_type = ? AND reference_id = ?
                AND movement_type = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('isss', 
            $data['item_id'],
            $data['reference_type'],
            $data['reference_id'],
            $data['movement_type']
        );
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    /**
     * Check stock availability
     */
    private function checkStockAvailability($data) {
        $sql = "SELECT quantity FROM warehouse_stock 
                WHERE warehouse_id = ? AND item_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', 
            $data['warehouse_id'],
            $data['item_id']
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row || $row['quantity'] < $data['quantity']) {
            throw new Exception("Insufficient stock available");
        }
    }

    /**
     * Insert movement record
     */
    private function insertMovementRecord($data) {
        $sql = "INSERT INTO stock_movements (
                    item_type, item_id, quantity, movement_type,
                    reference_type, reference_id, warehouse_id,
                    source_type, source_id, destination_type, destination_id,
                    notes, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sidssiisssssi',
            $data['item_type'],
            $data['item_id'],
            $data['quantity'],
            $data['movement_type'],
            $data['reference_type'],
            $data['reference_id'],
            $data['warehouse_id'],
            $data['source_type'],
            $data['source_id'],
            $data['destination_type'],
            $data['destination_id'],
            $data['notes'],
            $this->user_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting stock movement: " . $stmt->error);
        }
        
        return $stmt->insert_id;
    }

    /**
     * Update warehouse stock
     */
    private function updateWarehouseStock($data) {
        if ($data['movement_type'] === 'in') {
            $sql = "INSERT INTO warehouse_stock (warehouse_id, item_id, quantity)
                   VALUES (?, ?, ?)
                   ON DUPLICATE KEY UPDATE quantity = quantity + ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('iidi',
                $data['warehouse_id'],
                $data['item_id'],
                $data['quantity'],
                $data['quantity']
            );
        } else {
            $sql = "UPDATE warehouse_stock 
                   SET quantity = quantity - ?
                   WHERE warehouse_id = ? AND item_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('dii',
                $data['quantity'],
                $data['warehouse_id'],
                $data['item_id']
            );
        }

        if (!$stmt->execute()) {
            throw new Exception("Error updating warehouse stock: " . $stmt->error);
        }
    }

    /**
     * Add audit log
     */
    private function addAuditLog($movement_id, $data) {
        $sql = "INSERT INTO audit_logs (
            user_id, 
            action, 
            details,
            ip_address
        ) VALUES (?, ?, ?, ?)";

        $action = $data['movement_type'] === 'in' ? 'stock_in' : 'stock_out';
        $details = json_encode([
            'movement_id' => $movement_id,
            'item_id' => $data['item_id'],
            'quantity' => $data['quantity'],
            'reference_type' => $data['reference_type'],
            'reference_id' => $data['reference_id'],
            'notes' => $data['notes']
        ]);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('isss',
            $this->user_id,
            $action,
            $details,
            $ip_address
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to create audit log: " . $stmt->error);
        }
    }
}

// Example usage:
/*
$stockManager = new StockMovementManager($connect, $_SESSION['userId']);
$result = $stockManager->createStockMovement([
    'item_type' => 'raw_material',
    'item_id' => 1,
    'quantity' => 10,
    'movement_type' => 'in',
    'reference_type' => 'purchase',
    'reference_id' => 123,
    'warehouse_id' => 1,
    'source_type' => 'supplier',
    'source_id' => 1,
    'destination_type' => 'warehouse',
    'destination_id' => 1,
    'notes' => 'Stock received from supplier'
]);
*/ 