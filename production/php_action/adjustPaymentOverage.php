<?php
// Disable all error reporting for production
error_reporting(0);
ini_set('display_errors', 0);

// Start fresh output buffer
if (ob_get_level()) ob_end_clean();
ob_start();

require_once 'core.php';
require_once 'db_connect.php';

// Set proper headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Debug log
error_log("Adjustment request received: " . json_encode($_POST));

try {
    // Check if required fields are set
    if(!isset($_POST['sale_id']) || empty($_POST['sale_id']) || 
       !isset($_POST['adjustment_amount']) || empty($_POST['adjustment_amount'])) {
        throw new Exception('Sale ID and adjustment amount are required');
    }
    
    // Log incoming data for debugging
    error_log("Adjustment data: " . json_encode($_POST));
    
    $saleId = intval($_POST['sale_id']);
    $adjustmentAmount = floatval($_POST['adjustment_amount']);
    $adjustmentType = isset($_POST['adjustment_type']) ? $_POST['adjustment_type'] : 'refund';
    $notes = isset($_POST['notes']) ? $_POST['notes'] : 'Payment overage adjustment';
    
    // Get current sale information
    $query = "SELECT paid_amount, total_amount, balance FROM sales_orders WHERE id = ?";
    $stmt = $connect->prepare($query);
    $stmt->bind_param("i", $saleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $saleData = $result->fetch_assoc();
    
    // Validate if sale exists
    if(!$saleData) {
        throw new Exception('Sale not found');
    }
    
    // Get current values
    $currentPaidAmount = floatval($saleData['paid_amount']);
    $totalAmount = floatval($saleData['total_amount']);
    $currentBalance = floatval($saleData['balance']);
    
    // Log current sale data for debugging
    error_log("Sale data before adjustment: " . json_encode($saleData));
    
    // Check for valid adjustment
    if($adjustmentAmount <= 0) {
        throw new Exception('Adjustment amount must be greater than zero');
    }
    
    // Check if there's actually an overpayment
    $overpaymentAmount = $currentPaidAmount - $totalAmount;
    if($overpaymentAmount <= 0) {
        throw new Exception('This sale does not have an overpayment to adjust');
    }
    
    // Check if adjustment is not larger than the overpayment
    if($adjustmentAmount > $overpaymentAmount) {
        throw new Exception('Adjustment amount exceeds the overpayment amount of ' . number_format($overpaymentAmount, 2));
    }
    
    // Prepare response array
    $response = array(
        'success' => false,
        'message' => 'Processing not completed'
    );
    
    // Start transaction
    $connect->begin_transaction();
    
    try {
        // 1. Add negative payment record for refund/adjustment
        $query = "INSERT INTO sales_payments (
                    sales_order_id, 
                    payment_date, 
                    payment_method, 
                    amount, 
                    reference_number, 
                    notes, 
                    status,
                    account_id,
                    created_by,
                    created_at
                ) VALUES (?, NOW(), ?, ?, ?, ?, 'approved', ?, ?, NOW())";
        
        $stmt = $connect->prepare($query);
        $userId = $_SESSION['userId'];
        $negativeAmount = -1 * $adjustmentAmount; // Store as negative amount
        $paymentMethod = $adjustmentType == 'refund' ? 'Refund' : 'Adjustment';
        $referenceNumber = 'ADJ-' . date('YmdHis');
        
        // Get default account ID (first active account)
        $accountQuery = "SELECT id FROM accounts WHERE status = 1 ORDER BY id LIMIT 1";
        $accountStmt = $connect->prepare($accountQuery);
        $accountStmt->execute();
        $accountResult = $accountStmt->get_result();
        $accountId = null;
        
        if ($accountResult->num_rows > 0) {
            $accountId = $accountResult->fetch_assoc()['id'];
        } else {
            // No account found, try to create one
            $createAccountQuery = "INSERT INTO accounts (
                account_owner, 
                account_name, 
                account_platform, 
                currency, 
                status, 
                created_by, 
                created_at
            ) VALUES (
                'System Default',
                'Default Account', 
                'Default Platform', 
                'USD', 
                1, 
                ?, 
                NOW()
            )";
            
            $createAccountStmt = $connect->prepare($createAccountQuery);
            $createAccountStmt->bind_param('i', $userId);
            
            if (!$createAccountStmt->execute()) {
                // If we can't create an account, try using direct SQL to determine which fields are actually in the table
                $fieldsQuery = "SHOW COLUMNS FROM accounts";
                $fieldsResult = $connect->query($fieldsQuery);
                $accountFields = [];
                
                if ($fieldsResult) {
                    while ($field = $fieldsResult->fetch_assoc()) {
                        $accountFields[] = $field['Field'];
                    }
                }
                
                // Create dynamic query based on existing fields
                $insertFields = [];
                $insertValues = [];
                $bindTypes = '';
                $bindParams = [];
                
                $defaultAccountData = [
                    'account_owner' => 'System Default',
                    'account_name' => 'Default Account',
                    'account_platform' => 'Default Platform',
                    'owner' => 'System Default',
                    'name' => 'Default Account', 
                    'platform' => 'Default Platform',
                    'currency' => 'USD',
                    'status' => 1,
                    'created_by' => $userId,
                    'created_at' => 'NOW()'
                ];
                
                foreach ($defaultAccountData as $field => $value) {
                    if (in_array($field, $accountFields)) {
                        if ($field == 'created_at') {
                            $insertFields[] = $field;
                            $insertValues[] = 'NOW()';
                        } else {
                            $insertFields[] = $field;
                            $insertValues[] = '?';
                            
                            if ($field == 'status' || $field == 'created_by') {
                                $bindTypes .= 'i';
                            } else {
                                $bindTypes .= 's';
                            }
                            
                            $bindParams[] = $value;
                        }
                    }
                }
                
                if (!empty($insertFields)) {
                    $createDynamicQuery = "INSERT INTO accounts (" . implode(', ', $insertFields) . 
                                       ") VALUES (" . implode(', ', $insertValues) . ")";
                    
                    $createDynamicStmt = $connect->prepare($createDynamicQuery);
                    
                    if ($createDynamicStmt) {
                        if (!empty($bindParams)) {
                            $bindParamsRefs = [];
                            foreach ($bindParams as $key => $value) {
                                $bindParamsRefs[$key] = &$bindParams[$key];
                            }
                            
                            $params = array_merge([$bindTypes], $bindParamsRefs);
                            call_user_func_array([$createDynamicStmt, 'bind_param'], $params);
                        }
                        
                        if ($createDynamicStmt->execute()) {
                            $accountId = $connect->insert_id;
                        }
                    }
                }
            } else {
                $accountId = $connect->insert_id;
            }
            
            if (!$accountId) {
                // If all else fails, use account_id = 1 for backward compatibility
                $accountId = 1;
                
                // Check if this ID exists
                $checkQuery = "SELECT 1 FROM accounts WHERE id = 1 LIMIT 1";
                $checkResult = $connect->query($checkQuery);
                
                if ($checkResult && $checkResult->num_rows == 0) {
                    // Force insert with ID = 1
                    $forceInsertQuery = "INSERT INTO accounts (
                        id, account_owner, account_name, currency, status, created_by, created_at
                    ) VALUES (
                        1, 'System Default', 'Default Account', 'USD', 1, ?, NOW()
                    )";
                    
                    $forceStmt = $connect->prepare($forceInsertQuery);
                    $forceStmt->bind_param('i', $userId);
                    $forceStmt->execute();
                }
            }
        }
        
        // Debug the prepared statement parameters
        error_log("Binding parameters: saleId=" . $saleId . ", paymentMethod=" . $paymentMethod . 
                 ", negativeAmount=" . $negativeAmount . ", referenceNumber=" . $referenceNumber . 
                 ", notes=" . $notes . ", accountId=" . $accountId . ", userId=" . $userId);
        
        // Try binding with corrected parameters
        try {
            $stmt->bind_param("isdssis", $saleId, $paymentMethod, $negativeAmount, $referenceNumber, $notes, $accountId, $userId);
            if (!$stmt->execute()) {
                throw new Exception("Error executing payment insert: " . $stmt->error);
            }
        } catch (Exception $bindException) {
            error_log("Bind parameters failed, trying alternative binding: " . $bindException->getMessage());
            
            // Alternative binding format if the first one fails
            try {
                $stmt->bind_param("isdssii", $saleId, $paymentMethod, $negativeAmount, $referenceNumber, $notes, $accountId, $userId);
            } catch (Exception $altBindException) {
                error_log("Alternative binding failed too, trying adaptive binding: " . $altBindException->getMessage());
                
                // Prepare a new statement with adaptive parameter types
                $connect->rollback();
                $connect->begin_transaction();
                
                // Use direct type detection
                $adaptiveQuery = "INSERT INTO sales_payments (
                    sales_order_id, payment_date, payment_method, amount, reference_number, notes, status, account_id, created_by, created_at
                ) VALUES (?, NOW(), ?, ?, ?, ?, 'approved', ?, ?, NOW())";
                
                $adaptiveStmt = $connect->prepare($adaptiveQuery);
                
                if (!$adaptiveStmt) {
                    throw new Exception("Error preparing adaptive statement: " . $connect->error);
                }
                
                $adaptiveStmt->bind_param("isdssis", $saleId, $paymentMethod, $negativeAmount, $referenceNumber, $notes, $accountId, $userId);
                
                if (!$adaptiveStmt->execute()) {
                    // Last resort - use simple SQL without prepared statement (safe in this context)
                    error_log("Adaptive bind failed too, trying direct SQL");
                    
                    $directQuery = "INSERT INTO sales_payments (
                        sales_order_id, payment_date, payment_method, amount, reference_number, notes, status, account_id, created_by, created_at
                    ) VALUES (
                        {$saleId}, NOW(), '{$connect->real_escape_string($paymentMethod)}', 
                        {$negativeAmount}, '{$connect->real_escape_string($referenceNumber)}', 
                        '{$connect->real_escape_string($notes)}', 'approved', {$accountId}, {$userId}, NOW()
                    )";
                    
                    if (!$connect->query($directQuery)) {
                        throw new Exception("Direct SQL insert failed: " . $connect->error);
                    }
                }
            }
        }
        
        // 2. Calculate new paid amount and determine payment status
        $newPaidAmount = $currentPaidAmount - $adjustmentAmount;
        $newBalance = $totalAmount - $newPaidAmount;
        $paymentStatus = 'unpaid';
        
        if($newPaidAmount >= $totalAmount) {
            $paymentStatus = 'paid';
        } else if($newPaidAmount > 0) {
            $paymentStatus = 'partial';
        }
        
        // 3. Update sales_orders table
        $query = "UPDATE sales_orders SET 
                  paid_amount = ?, 
                  payment_status = ?,
                  balance = ?,
                  updated_at = NOW()
                  WHERE id = ?";
        
        $stmt = $connect->prepare($query);
        $stmt->bind_param("dsdi", $newPaidAmount, $paymentStatus, $newBalance, $saleId);
        if (!$stmt->execute()) {
            throw new Exception("Error updating order: " . $stmt->error);
        }
        
        // Commit transaction
        $connect->commit();
        
        // Success response
        $response = array(
            'success' => true,
            'message' => ($adjustmentType == 'refund' ? 'Refund' : 'Adjustment') . ' of ' . number_format($adjustmentAmount, 2) . ' processed successfully',
            'payment_status' => $paymentStatus,
            'paid_amount' => $newPaidAmount,
            'balance' => $newBalance
        );
        
        // Log success for debugging
        error_log("Adjustment successful: " . json_encode($response));
    } catch (Exception $e) {
        // Roll back transaction on error
        $connect->rollback();
        throw $e;
    }
} catch(Exception $e) {
    // Rollback transaction on error
    if(isset($connect) && $connect->ping()) {
        $connect->rollback();
    }
    
    // Error response
    $response = array(
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    );
    
    // Log error for debugging
    error_log("Adjustment error: " . $e->getMessage());
}

// Clean any buffered output
while (ob_get_level()) ob_end_clean();

// Encode the response with options to handle UTF-8 correctly
$json_response = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Check for JSON encoding errors
if ($json_response === false) {
    error_log("JSON encoding error: " . json_last_error_msg());
    $json_response = '{"success":false,"message":"Server error: Unable to encode response"}';
}

// Output the JSON response
echo $json_response;
exit;
?> 