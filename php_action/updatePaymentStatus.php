<?php
require_once 'core.php';

// Set header to return JSON
header('Content-Type: application/json');

if(!isset($_POST['purchaseId']) || !isset($_POST['status'])) {
    echo json_encode(array('success' => false, 'messages' => 'Missing required parameters'));
    exit();
}

$purchaseId = (int)$_POST['purchaseId'];
$status = $_POST['status'];

// Validate status
$validStatuses = array('Paid', 'Partial', 'Unpaid');
if(!in_array($status, $validStatuses)) {
    echo json_encode(array('success' => false, 'messages' => 'Invalid payment status'));
    exit();
}

try {
    // Start transaction
    $connect->begin_transaction();

    // Get current purchase details
    $getPurchaseSql = "SELECT payment_status, grand_total, paid_amount FROM purchases WHERE id = ?";
    $stmt = $connect->prepare($getPurchaseSql);
    $stmt->bind_param("i", $purchaseId);
    $stmt->execute();
    $result = $stmt->get_result();
    $purchase = $result->fetch_assoc();

    if (!$purchase) {
        throw new Exception("Purchase not found");
    }

    $currentPaidAmount = $purchase['paid_amount'];
    $grandTotal = $purchase['grand_total'];

    // Handle different status updates
    if ($status === 'Paid') {
        $paidAmount = $grandTotal;
    } else if ($status === 'Partial') {
        // For partial payments, we need additional data
        if (!isset($_POST['payment_date']) || !isset($_POST['amount']) || !isset($_POST['payment_method'])) {
            throw new Exception("Missing payment details for partial payment");
        }

        $paymentDate = $_POST['payment_date'];
        $paymentAmount = floatval($_POST['amount']);
        $paymentMethod = $_POST['payment_method'];
        $referenceNumber = isset($_POST['reference_number']) ? $_POST['reference_number'] : '';
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

        // Validate payment amount
        if ($paymentAmount <= 0) {
            throw new Exception("Payment amount must be greater than zero");
        }

        if (($currentPaidAmount + $paymentAmount) > $grandTotal) {
            throw new Exception("Total paid amount cannot exceed the purchase total");
        }

        // Insert payment record
        $insertPaymentSql = "INSERT INTO purchase_payments (
            purchase_id, 
            payment_date,
            amount,
            payment_method,
            reference_number,
            notes,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $stmtPayment = $connect->prepare($insertPaymentSql);
        $stmtPayment->bind_param("isdsss", 
            $purchaseId,
            $paymentDate,
            $paymentAmount,
            $paymentMethod,
            $referenceNumber,
            $notes
        );

        if (!$stmtPayment->execute()) {
            throw new Exception("Error recording payment: " . $stmtPayment->error);
        }

        $paidAmount = $currentPaidAmount + $paymentAmount;
        
        // If paid amount equals grand total, update status to Paid
        if ($paidAmount >= $grandTotal) {
            $status = 'Paid';
        }
    } else {
        // Unpaid status
        $paidAmount = 0;
        
        // Delete all payment records for this purchase
        $deletePaymentsSql = "DELETE FROM purchase_payments WHERE purchase_id = ?";
        $stmtDelete = $connect->prepare($deletePaymentsSql);
        $stmtDelete->bind_param("i", $purchaseId);
        $stmtDelete->execute();
    }

    // Update purchase status and paid amount
    $sql = "UPDATE purchases SET payment_status = ?, paid_amount = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("sdi", $status, $paidAmount, $purchaseId);
    
    if($stmt->execute()) {
        // Commit transaction
        $connect->commit();
        
        echo json_encode(array(
            'success' => true,
            'messages' => 'Payment status updated successfully'
        ));
    } else {
        throw new Exception($connect->error);
    }

} catch (Exception $e) {
    // Rollback transaction on error
    if ($connect->connect_errno === 0) {
        $connect->rollback();
    }
    
    echo json_encode(array(
        'success' => false,
        'messages' => $e->getMessage()
    ));
}

$connect->close(); 