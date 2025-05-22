<?php
class LogManager {
    private $db;
    private $userId;
    
    public function __construct($db, $userId) {
        $this->db = $db;
        $this->userId = $userId;
    }
    
    /**
     * Log an activity
     * @param string $action The action performed
     * @param string $module The module where action was performed
     * @param int $referenceId Related record ID
     * @return bool Success status
     */
    public function logActivity($action, $module, $referenceId) {
        try {
            $query = "INSERT INTO activity_log (user_id, action, module, reference_id) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$this->userId, $action, $module, $referenceId]);
        } catch (Exception $e) {
            error_log("Error logging activity: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log an audit record
     * @param string $activityType Type of activity
     * @param string $description Description of changes
     * @param mixed $oldValue Previous value
     * @param mixed $newValue New value
     * @param int $referenceId Related record ID
     * @return bool Success status
     */
    public function logAudit($activityType, $description, $oldValue, $newValue, $referenceId) {
        try {
            $query = "INSERT INTO audit_log (user_id, activity_type, description, old_value, new_value, 
                                           reference_id, ip_address) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                $this->userId,
                $activityType,
                $description,
                is_array($oldValue) ? json_encode($oldValue) : $oldValue,
                is_array($newValue) ? json_encode($newValue) : $newValue,
                $referenceId,
                $_SERVER['REMOTE_ADDR']
            ]);
        } catch (Exception $e) {
            error_log("Error logging audit: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log a payment status change
     * @param int $paymentId Payment ID
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     * @param string $notes Additional notes
     * @return bool Success status
     */
    public function logPaymentStatusChange($paymentId, $oldStatus, $newStatus, $notes = '') {
        try {
            // Log activity
            $this->logActivity(
                "Payment status changed from $oldStatus to $newStatus",
                "sales_payments",
                $paymentId
            );
            
            // Log audit
            $this->logAudit(
                "payment_status_change",
                "Payment status updated",
                $oldStatus,
                $newStatus,
                $paymentId
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Error logging payment status change: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log payment creation
     * @param int $paymentId Payment ID
     * @param array $paymentData Payment details
     * @return bool Success status
     */
    public function logPaymentCreation($paymentId, $paymentData) {
        try {
            // Log activity
            $this->logActivity(
                "New payment created",
                "sales_payments",
                $paymentId
            );
            
            // Log audit
            $this->logAudit(
                "payment_creation",
                "New payment created",
                null,
                $paymentData,
                $paymentId
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Error logging payment creation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log payment update
     * @param int $paymentId Payment ID
     * @param array $oldData Previous payment data
     * @param array $newData Updated payment data
     * @return bool Success status
     */
    public function logPaymentUpdate($paymentId, $oldData, $newData) {
        try {
            // Log activity
            $this->logActivity(
                "Payment updated",
                "sales_payments",
                $paymentId
            );
            
            // Log audit
            $this->logAudit(
                "payment_update",
                "Payment details updated",
                $oldData,
                $newData,
                $paymentId
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Error logging payment update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log production order creation
     * @param int $orderId Production order ID
     * @param array $orderData Order details
     * @return bool Success status
     */
    public function logProductionOrderCreation($orderId, $orderData) {
        try {
            // Log activity
            $this->logActivity(
                "New production order created",
                "production_orders",
                $orderId
            );
            
            // Log audit
            $this->logAudit(
                "production_order_creation",
                "New production order created",
                null,
                $orderData,
                $orderId
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Error logging production order creation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log production order update
     * @param int $orderId Production order ID
     * @param array $oldData Previous order data
     * @param array $newData Updated order data
     * @return bool Success status
     */
    public function logProductionOrderUpdate($orderId, $oldData, $newData) {
        try {
            // Log activity
            $this->logActivity(
                "Production order updated",
                "production_orders",
                $orderId
            );
            
            // Log audit
            $this->logAudit(
                "production_order_update",
                "Production order details updated",
                $oldData,
                $newData,
                $orderId
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Error logging production order update: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log production order status change
     * @param int $orderId Production order ID
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     * @param string $notes Additional notes
     * @return bool Success status
     */
    public function logProductionOrderStatusChange($orderId, $oldStatus, $newStatus, $notes = '') {
        try {
            // Log activity
            $this->logActivity(
                "Production order status changed from $oldStatus to $newStatus",
                "production_orders",
                $orderId
            );
            
            // Log audit
            $this->logAudit(
                "production_order_status_change",
                "Status changed" . ($notes ? ": $notes" : ''),
                $oldStatus,
                $newStatus,
                $orderId
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Error logging production order status change: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log production order completion
     * @param int $orderId Production order ID
     * @param array $completionData Completion details
     * @return bool Success status
     */
    public function logProductionOrderCompletion($orderId, $completionData) {
        try {
            // Log activity
            $this->logActivity(
                "Production order completed",
                "production_orders",
                $orderId
            );
            
            // Log audit
            $this->logAudit(
                "production_order_completion",
                "Production order marked as completed",
                null,
                $completionData,
                $orderId
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Error logging production order completion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log production order deletion
     * @param int $orderId Production order ID
     * @param array $orderData Order details before deletion
     * @return bool Success status
     */
    public function logProductionOrderDeletion($orderId, $orderData) {
        try {
            // Log activity
            $this->logActivity(
                "Production order deleted",
                "production_orders",
                $orderId
            );
            
            // Log audit
            $this->logAudit(
                "production_order_deletion",
                "Production order deleted",
                $orderData,
                null,
                $orderId
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Error logging production order deletion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log production material allocation
     * @param int $orderId Production order ID
     * @param array $materialData Material allocation details
     * @return bool Success status
     */
    public function logProductionMaterialAllocation($orderId, $materialData) {
        try {
            // Log activity
            $this->logActivity(
                "Materials allocated for production order",
                "production_orders",
                $orderId
            );
            
            // Log audit
            $this->logAudit(
                "production_material_allocation",
                "Materials allocated to production order",
                null,
                $materialData,
                $orderId
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Error logging material allocation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log production progress update
     * @param int $orderId Production order ID
     * @param array $progressData Progress update details
     * @return bool Success status
     */
    public function logProductionProgressUpdate($orderId, $progressData) {
        try {
            // Log activity
            $this->logActivity(
                "Production progress updated",
                "production_orders",
                $orderId
            );
            
            // Log audit
            $this->logAudit(
                "production_progress_update",
                "Production progress updated",
                null,
                $progressData,
                $orderId
            );
            
            return true;
        } catch (Exception $e) {
            error_log("Error logging progress update: " . $e->getMessage());
            return false;
        }
    }
} 