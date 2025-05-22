<?php
/**
 * RawMaterialManager Class
 * Handles all raw material consumption and stock management
 */
class RawMaterialManager {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Consume raw material for a production order
     * 
     * @param int $productionOrderId
     * @param int $materialId
     * @param float $quantity
     * @param string $notes
     * @param int $userId
     * @return array
     */
    public function consumeMaterial($productionOrderId, $materialId, $quantity, $notes, $userId) {
        try {
            // Start transaction
            $this->conn->begin_transaction();
            
            // Get current material details
            $materialSql = "SELECT 
                               pom.required_quantity,
                               pom.consumed_quantity,
                               rm.current_stock,
                               rm.name,
                               rm.id
                           FROM production_order_materials pom
                           JOIN raw_materials rm ON pom.material_id = rm.id
                           WHERE pom.production_order_id = ? AND pom.material_id = ?";
            
            $materialStmt = $this->conn->prepare($materialSql);
            $materialStmt->bind_param("ii", $productionOrderId, $materialId);
            $materialStmt->execute();
            $result = $materialStmt->get_result();
            
            if($result->num_rows == 0) {
                throw new Exception("Material not found in production order");
            }
            
            $materialData = $result->fetch_assoc();
            $materialStmt->close();
            
            // Validate quantities
            if($quantity > $materialData['current_stock']) {
                throw new Exception("Insufficient stock available");
            }
            
            $newConsumedQuantity = $materialData['consumed_quantity'] + $quantity;
            if($newConsumedQuantity > $materialData['required_quantity']) {
                throw new Exception("Cannot consume more than required quantity");
            }
            
            // Update material consumption
            $consumptionStatus = $newConsumedQuantity >= $materialData['required_quantity'] ? 
                               'fully_consumed' : 'partially_consumed';
            
            $updateSql = "UPDATE production_order_materials 
                         SET consumed_quantity = ?,
                             status = ?,
                             updated_at = CURRENT_TIMESTAMP
                         WHERE production_order_id = ? AND material_id = ?";
            
            $updateStmt = $this->conn->prepare($updateSql);
            $updateStmt->bind_param("dsii", $newConsumedQuantity, $consumptionStatus, 
                                  $productionOrderId, $materialId);
            
            if(!$updateStmt->execute()) {
                throw new Exception("Error updating material consumption");
            }
            $updateStmt->close();
            
            // Update raw material stock
            $stockSql = "UPDATE raw_materials 
                        SET current_stock = current_stock - ?,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE id = ?";
            
            $stockStmt = $this->conn->prepare($stockSql);
            $stockStmt->bind_param("di", $quantity, $materialId);
            
            if(!$stockStmt->execute()) {
                throw new Exception("Error updating material stock");
            }
            $stockStmt->close();
            
            // Record consumption
            $consumptionSql = "INSERT INTO material_consumption 
                              (production_order_id, material_id, quantity, notes, created_by, created_at) 
                              VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
            
            $consumptionStmt = $this->conn->prepare($consumptionSql);
            $consumptionStmt->bind_param("iidsi", $productionOrderId, $materialId, 
                                       $quantity, $notes, $userId);
            
            if(!$consumptionStmt->execute()) {
                throw new Exception("Error recording consumption");
            }
            $consumptionStmt->close();
            
            // Get production order details
            $orderSql = "SELECT po.order_number, p.name as product_name 
                        FROM production_orders po
                        JOIN production_products p ON po.product_id = p.id
                        WHERE po.id = ?";
            
            $orderStmt = $this->conn->prepare($orderSql);
            $orderStmt->bind_param("i", $productionOrderId);
            $orderStmt->execute();
            $orderResult = $orderStmt->get_result();
            $orderData = $orderResult->fetch_assoc();
            $orderStmt->close();
            
            // Create detailed notes
            $detailedNotes = sprintf(
                "Material consumed in Production Order #%s - %s. %s",
                $orderData['order_number'],
                $orderData['product_name'],
                $notes
            );
            
            // Record stock movement
            $movementSql = "INSERT INTO raw_material_movements 
                           (material_id, movement_type, quantity, reference_type, 
                            reference_id, notes, created_by, created_at) 
                           VALUES (?, 'out', ?, 'production', ?, ?, ?, CURRENT_TIMESTAMP)";
            
            $movementStmt = $this->conn->prepare($movementSql);
            $movementStmt->bind_param("idisi", $materialId, $quantity, $productionOrderId, 
                                    $detailedNotes, $userId);
            
            if(!$movementStmt->execute()) {
                throw new Exception("Error recording stock movement");
            }
            $movementStmt->close();
            
            // Commit transaction
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => "Material successfully consumed",
                'consumed_quantity' => $newConsumedQuantity,
                'status' => $consumptionStatus
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get material consumption status for a production order
     * 
     * @param int $productionOrderId
     * @return array
     */
    public function getMaterialConsumptionStatus($productionOrderId) {
        $sql = "SELECT 
                    pom.material_id,
                    rm.name as material_name,
                    pom.required_quantity,
                    COALESCE(pom.consumed_quantity, 0) as consumed_quantity,
                    pom.status,
                    rm.current_stock
                FROM production_order_materials pom
                JOIN raw_materials rm ON pom.material_id = rm.id
                WHERE pom.production_order_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $productionOrderId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $materials = [];
        while($row = $result->fetch_assoc()) {
            $materials[] = $row;
        }
        
        return $materials;
    }
} 