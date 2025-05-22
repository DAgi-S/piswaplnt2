<?php
class StockMovementManager {
    private $db;
    private $userId;
    private $logger;

    public function __construct($db, $userId) {
        $this->db = $db;
        $this->userId = $userId;
        $this->logger = new Logger();
    }

    /**
     * Execute a stock movement with transaction management
     * @param array $data Movement data
     * @return array Response with success status and message
     */
    public function executeMovement($data) {
        try {
            // Validate input data
            $this->validateMovementData($data);
            
            // Start transaction
            $this->db->begin_transaction();

            // Verify stock levels
            $this->verifyStockLevels($data);

            // Create movement record
            $movementId = $this->createMovementRecord($data);

            // Update source stock
            if ($data['source_type'] === 'warehouse') {
                $this->updateWarehouseStock(
                    $data['source_id'], 
                    $data['item_type'], 
                    $data['item_id'], 
                    -$data['quantity']
                );
            }

            // Update destination stock
            if ($data['destination_type'] === 'warehouse') {
                $this->updateWarehouseStock(
                    $data['destination_id'], 
                    $data['item_type'], 
                    $data['item_id'], 
                    $data['quantity']
                );
            }

            // Log the movement
            $this->logMovement($movementId, $data);

            // Commit transaction
            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Stock movement completed successfully',
                'movement_id' => $movementId
            ];

        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->rollback();
            $this->logger->logError('Stock Movement Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate movement data
     * @param array $data Movement data
     * @throws Exception if validation fails
     */
    private function validateMovementData($data) {
        $required = [
            'item_type', 'item_id', 'quantity',
            'source_type', 'destination_type',
            'movement_type', 'reference_type', 'reference_id'
        ];

        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new Exception("Missing required field: {$field}");
            }
        }

        if (!in_array($data['item_type'], ['raw_material', 'finished_good', 'product'])) {
            throw new Exception("Invalid item type");
        }

        if (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
            throw new Exception("Invalid quantity");
        }

        // Validate source and destination types
        $validLocations = ['warehouse', 'workstation', 'supplier', 'customer', 'production'];
        if (!in_array($data['source_type'], $validLocations) || 
            !in_array($data['destination_type'], $validLocations)) {
            throw new Exception("Invalid source or destination type");
        }

        // Validate warehouse IDs based on movement type
        if ($data['source_type'] === 'warehouse' && empty($data['source_id'])) {
            throw new Exception("Source warehouse ID is required when source type is warehouse");
        }
        if ($data['destination_type'] === 'warehouse' && empty($data['destination_id'])) {
            throw new Exception("Destination warehouse ID is required when destination type is warehouse");
        }
    }

    /**
     * Verify stock levels before movement
     * @param array $data Movement data
     * @throws Exception if stock level is insufficient
     */
    private function verifyStockLevels($data) {
        if ($data['source_type'] === 'warehouse' && $data['source_id']) {
            $sql = "SELECT quantity FROM warehouse_stock 
                   WHERE warehouse_id = ? AND item_type = ? AND item_id = ?
                   FOR UPDATE";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("isi", 
                $data['source_id'], 
                $data['item_type'], 
                $data['item_id']
            );
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0 || $result->fetch_assoc()['quantity'] < $data['quantity']) {
                throw new Exception("Insufficient stock in source warehouse");
            }
        }
    }

    /**
     * Create movement record in inventory_movement_log
     * @param array $data Movement data
     * @return int Movement ID
     * @throws Exception if insert fails
     */
    private function createMovementRecord($data) {
        $sql = "INSERT INTO inventory_movement_log (
            item_type, item_id, source_type, source_id,
            destination_type, destination_id, quantity,
            movement_type, reference_type, reference_id,
            status, created_by, notes, batch_number
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            "sisissdsssiss",
            $data['item_type'],
            $data['item_id'],
            $data['source_type'],
            $data['source_id'],
            $data['destination_type'],
            $data['destination_id'],
            $data['quantity'],
            $data['movement_type'],
            $data['reference_type'],
            $data['reference_id'],
            $this->userId,
            $data['notes'],
            $data['batch_number']
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to create movement record: " . $stmt->error);
        }

        return $stmt->insert_id;
    }

    /**
     * Update warehouse stock levels
     * @param int $warehouseId Warehouse ID
     * @param string $itemType Item type
     * @param int $itemId Item ID
     * @param float $quantity Quantity change (positive for increase, negative for decrease)
     * @throws Exception if update fails
     */
    private function updateWarehouseStock($warehouseId, $itemType, $itemId, $quantity) {
        if (!$warehouseId) return; // Skip if no warehouse ID

        $sql = "INSERT INTO warehouse_stock 
                (warehouse_id, item_type, item_id, quantity) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE quantity = quantity + ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isidi", 
            $warehouseId, 
            $itemType, 
            $itemId, 
            $quantity,
            $quantity
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to update warehouse stock: " . $stmt->error);
        }

        // Verify the stock doesn't go negative after update
        $sql = "SELECT quantity FROM warehouse_stock 
                WHERE warehouse_id = ? AND item_type = ? AND item_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isi", $warehouseId, $itemType, $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $currentStock = $result->fetch_assoc()['quantity'];
            if ($currentStock < 0) {
                throw new Exception("Operation would result in negative stock");
            }
        }
    }

    /**
     * Log movement details in warehouse_stock_movements
     * @param int $movementId Movement ID
     * @param array $data Movement data
     * @throws Exception if logging fails
     */
    private function logMovement($movementId, $data) {
        $sql = "INSERT INTO warehouse_stock_movements (
            warehouse_id, item_type, item_id,
            movement_type, quantity, reference_type,
            reference_id, notes, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Log for source warehouse if applicable
        if ($data['source_type'] === 'warehouse') {
            $stmt = $this->db->prepare($sql);
            $movementType = 'out';
            $stmt->bind_param(
                "iissdsiis",
                $data['source_id'],
                $data['item_type'],
                $data['item_id'],
                $movementType,
                $data['quantity'],
                $data['reference_type'],
                $data['reference_id'],
                $data['notes'] ?? '',
                $this->userId
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to log source movement: " . $stmt->error);
            }
        }

        // Log for destination warehouse if applicable
        if ($data['destination_type'] === 'warehouse') {
            $stmt = $this->db->prepare($sql);
            $movementType = 'in';
            $stmt->bind_param(
                "iissdsiis",
                $data['destination_id'],
                $data['item_type'],
                $data['item_id'],
                $movementType,
                $data['quantity'],
                $data['reference_type'],
                $data['reference_id'],
                $data['notes'] ?? '',
                $this->userId
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to log destination movement: " . $stmt->error);
            }
        }
    }
}

class Logger {
    public function logError($message) {
        error_log($message);
    }
} 