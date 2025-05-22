<?php
require_once '../../php_action/core.php';

$response = array(
    'success' => false,
    'messages' => array()
);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $orderId = isset($_POST['orderId']) ? (int)$_POST['orderId'] : 0;
    $orderNumber = isset($_POST['editOrderNumber']) ? mysqli_real_escape_string($connect, $_POST['editOrderNumber']) : '';
    $orderDate = isset($_POST['editOrderDate']) ? mysqli_real_escape_string($connect, $_POST['editOrderDate']) : '';
    $unitPrice = isset($_POST['editUnitPrice']) ? (float)$_POST['editUnitPrice'] : 0;
    $quantity = isset($_POST['editQuantity']) ? (int)$_POST['editQuantity'] : 0;
    $totalPrice = $unitPrice * $quantity;
    $hasCredit = isset($_POST['editHasCredit']) ? (int)$_POST['editHasCredit'] : 0;
    $creditAmount = ($hasCredit && isset($_POST['editCreditAmount'])) ? (float)$_POST['editCreditAmount'] : 0;

    // Validate required fields
    if ($orderId <= 0 || empty($orderNumber) || empty($orderDate) || $unitPrice <= 0 || $quantity <= 0) {
        $response['messages'] = 'Please fill all required fields with valid values.';
    } else {
        $sql = "UPDATE gps_orders SET 
                order_number = ?, 
                order_date = ?, 
                unit_price = ?, 
                quantity = ?, 
                total_price = ?, 
                has_credit = ?, 
                credit_amount = ? 
                WHERE id = ?";

        $stmt = $connect->prepare($sql);
        $stmt->bind_param(
            'ssdiidii', 
            $orderNumber, 
            $orderDate, 
            $unitPrice, 
            $quantity, 
            $totalPrice, 
            $hasCredit, 
            $creditAmount,
            $orderId
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = 'Order updated successfully.';
        } else {
            $response['messages'] = 'Error updating order: ' . $stmt->error;
        }

        $stmt->close();
    }
}

$connect->close();

header('Content-Type: application/json');
echo json_encode($response); 