<?php
require_once 'core.php';

/**
 * Log an audit event
 * @param string $activityType Type of activity (purchase, sales, quotation, stock, price, user)
 * @param string $description Description of the activity
 * @param mixed $oldValue Previous value (optional)
 * @param mixed $newValue New value (optional)
 * @param string $referenceId Reference ID related to the activity (optional)
 * @return bool True if logging successful, false otherwise
 */
function logAuditEvent($activityType, $description, $oldValue = null, $newValue = null, $referenceId = null) {
    global $connect;
    
    try {
        $userId = isset($_SESSION['userId']) ? $_SESSION['userId'] : null;
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        
        $query = "INSERT INTO audit_log (
            user_id, 
            activity_type, 
            description, 
            old_value, 
            new_value, 
            ip_address, 
            reference_id, 
            timestamp
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $connect->prepare($query);
        return $stmt->execute([
            $userId,
            $activityType,
            $description,
            $oldValue,
            $newValue,
            $ipAddress,
            $referenceId
        ]);
    } catch (Exception $e) {
        error_log("Audit Log Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log purchase-related events
 */
function logPurchaseEvent($action, $purchaseId, $oldData = null, $newData = null) {
    return logAuditEvent(
        'purchase',
        "Purchase {$action} - ID: {$purchaseId}",
        $oldData ? json_encode($oldData) : null,
        $newData ? json_encode($newData) : null,
        $purchaseId
    );
}

/**
 * Log sales-related events
 */
function logSalesEvent($action, $orderId, $oldData = null, $newData = null) {
    return logAuditEvent(
        'sales',
        "Sales {$action} - ID: {$orderId}",
        $oldData ? json_encode($oldData) : null,
        $newData ? json_encode($newData) : null,
        $orderId
    );
}

/**
 * Log quotation-related events
 */
function logQuotationEvent($action, $quotationId, $oldData = null, $newData = null) {
    return logAuditEvent(
        'quotation',
        "Quotation {$action} - ID: {$quotationId}",
        $oldData ? json_encode($oldData) : null,
        $newData ? json_encode($newData) : null,
        $quotationId
    );
}

/**
 * Log stock changes
 */
function logStockChange($productId, $oldQuantity, $newQuantity, $reason) {
    return logAuditEvent(
        'stock',
        "Stock change for Product ID: {$productId} - {$reason}",
        $oldQuantity,
        $newQuantity,
        $productId
    );
}

/**
 * Log price updates
 */
function logPriceUpdate($productId, $oldPrice, $newPrice) {
    return logAuditEvent(
        'price',
        "Price update for Product ID: {$productId}",
        $oldPrice,
        $newPrice,
        $productId
    );
}

/**
 * Log user actions (login, logout, profile updates, etc.)
 */
function logUserAction($userId, $action, $details = null) {
    return logAuditEvent(
        'user',
        "User {$action} - ID: {$userId}",
        null,
        $details,
        $userId
    );
} 