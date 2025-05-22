<?php
require_once '../../php_action/core.php';

$response = array(
    'success' => false,
    'messages' => array()
);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $orderNumber = isset($_POST['orderNumber']) ? mysqli_real_escape_string($connect, $_POST['orderNumber']) : '';
    $orderDate = isset($_POST['orderDate']) ? mysqli_real_escape_string($connect, $_POST['orderDate']) : '';
    $unitPrice = isset($_POST['unitPrice']) ? (float)$_POST['unitPrice'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $totalPrice = $unitPrice * $quantity;
    $hasCredit = isset($_POST['hasCredit']) ? (int)$_POST['hasCredit'] : 0;
    $creditAmount = ($hasCredit && isset($_POST['creditAmount'])) ? (float)$_POST['creditAmount'] : 0;

    // Validate required fields
    if (empty($orderNumber) || empty($orderDate) || $unitPrice <= 0 || $quantity <= 0) {
        $response['messages'] = 'Please fill all required fields with valid values.';
    } else {
        $sql = "INSERT INTO gps_orders (
                    order_number, 
                    order_date, 
                    unit_price, 
                    quantity, 
                    total_price, 
                    has_credit, 
                    credit_amount, 
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $connect->prepare($sql);
        $stmt->bind_param(
            'ssdiidi', 
            $orderNumber, 
            $orderDate, 
            $unitPrice, 
            $quantity, 
            $totalPrice, 
            $hasCredit, 
            $creditAmount
        );

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['messages'] = 'Order created successfully.';
        } else {
            $response['messages'] = 'Error creating order: ' . $stmt->error;
        }

        $stmt->close();
    }
}

$connect->close();

header('Content-Type: application/json');
echo json_encode($response); 