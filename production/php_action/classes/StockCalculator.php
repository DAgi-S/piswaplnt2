<?php
namespace Production;

class StockCalculator {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Calculate available stock considering all factors
     * @param int $productId
     * @return array Stock details including available, reserved, and pending quantities
     */
    public function calculateAvailableStock($productId) {
        try {
            $this->db->begin_transaction();

            // Get current stock level
            $sql = "SELECT 
                        COALESCE(SUM(
                            CASE 
                                WHEN movement_type = 'in' THEN quantity 
                                WHEN movement_type = 'out' THEN -quantity 
                            END
                        ), 0) as current_stock
                    FROM stock_movements 
                    WHERE product_id = ? 
                    AND status = 'active'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentStock = $result->fetch_assoc()['current_stock'];

            // Get reserved quantity (from pending production orders)
            $sql = "SELECT COALESCE(SUM(required_quantity), 0) as reserved
                    FROM production_materials pm
                    JOIN production_orders po ON pm.order_id = po.id
                    WHERE pm.product_id = ?
                    AND po.status IN ('pending', 'in_progress')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $reservedQuantity = $result->fetch_assoc()['reserved'];

            // Get pending orders (approved purchase orders not yet received)
            $sql = "SELECT COALESCE(SUM(quantity), 0) as pending
                    FROM purchase_order_items poi
                    JOIN purchase_orders po ON poi.order_id = po.id
                    WHERE poi.product_id = ?
                    AND po.status = 'approved'
                    AND po.delivery_status = 'pending'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $pendingOrders = $result->fetch_assoc()['pending'];

            // Calculate safety stock
            $safetyStock = $this->calculateSafetyStock($productId);

            // Calculate reorder point
            $reorderPoint = $this->calculateReorderPoint($productId, $safetyStock);

            $this->db->commit();

            return [
                'current_stock' => $currentStock,
                'reserved_quantity' => $reservedQuantity,
                'pending_orders' => $pendingOrders,
                'available_stock' => $currentStock - $reservedQuantity,
                'safety_stock' => $safetyStock,
                'reorder_point' => $reorderPoint,
                'needs_reorder' => ($currentStock - $reservedQuantity) <= $reorderPoint
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Calculate safety stock level
     * @param int $productId
     * @return float Safety stock quantity
     */
    private function calculateSafetyStock($productId) {
        // Get average daily usage over the last 30 days
        $sql = "SELECT 
                    COALESCE(SUM(
                        CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END
                    ) / 30, 0) as avg_daily_usage
                FROM stock_movements
                WHERE product_id = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $avgDailyUsage = $result->fetch_assoc()['avg_daily_usage'];

        // Get lead time from product settings
        $sql = "SELECT lead_time_days, safety_factor 
                FROM product_settings 
                WHERE product_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $settings = $result->fetch_assoc();
        
        $leadTimeDays = $settings['lead_time_days'] ?? 7; // Default 7 days if not set
        $safetyFactor = $settings['safety_factor'] ?? 1.5; // Default 1.5 if not set

        // Calculate safety stock
        return ($avgDailyUsage * $leadTimeDays) * $safetyFactor;
    }

    /**
     * Calculate reorder point
     * @param int $productId
     * @param float $safetyStock
     * @return float Reorder point quantity
     */
    private function calculateReorderPoint($productId, $safetyStock) {
        // Get average daily usage over the last 30 days
        $sql = "SELECT 
                    COALESCE(SUM(
                        CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END
                    ) / 30, 0) as avg_daily_usage
                FROM stock_movements
                WHERE product_id = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $avgDailyUsage = $result->fetch_assoc()['avg_daily_usage'];

        // Get lead time from product settings
        $sql = "SELECT lead_time_days 
                FROM product_settings 
                WHERE product_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $settings = $result->fetch_assoc();
        
        $leadTimeDays = $settings['lead_time_days'] ?? 7; // Default 7 days if not set

        // Calculate reorder point
        return ($avgDailyUsage * $leadTimeDays) + $safetyStock;
    }

    /**
     * Validate if a stock movement is possible
     * @param int $productId
     * @param float $quantity
     * @param string $movementType
     * @return array Validation result with status and message
     */
    public function validateStockMovement($productId, $quantity, $movementType) {
        try {
            if ($movementType !== 'in') {
                $stockDetails = $this->calculateAvailableStock($productId);
                
                if ($quantity > $stockDetails['available_stock']) {
                    return [
                        'valid' => false,
                        'message' => sprintf(
                            "Insufficient stock. Available: %.2f, Required: %.2f",
                            $stockDetails['available_stock'],
                            $quantity
                        )
                    ];
                }

                if ($stockDetails['needs_reorder']) {
                    return [
                        'valid' => true,
                        'warning' => sprintf(
                            "Stock will be below reorder point (%.2f). Consider placing a new order.",
                            $stockDetails['reorder_point']
                        )
                    ];
                }
            }

            return ['valid' => true];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => "Error validating stock movement: " . $e->getMessage()
            ];
        }
    }
} 