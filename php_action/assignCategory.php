<?php
require_once 'core.php';
require_once 'db_connect.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $transactionId = $_POST['transactionId'];
    $categoryId = $_POST['categoryId'];

    // Validate that transaction exists
    $sql = "SELECT id FROM digitalswap WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows === 0) {
        $valid['success'] = false;
        $valid['messages'] = "Transaction not found";
    } else {
        // First delete any existing category assignment
        $sql = "DELETE FROM digital_transaction_categories WHERE transaction_id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();

        // Then insert new category assignment
        $sql = "INSERT INTO digital_transaction_categories (transaction_id, category_id) VALUES (?, ?)";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("ii", $transactionId, $categoryId);

        if($stmt->execute()) {
            $valid['success'] = true;
            $valid['messages'] = "Category successfully assigned";
        } else {
            $valid['success'] = false;
            $valid['messages'] = "Error while assigning category";
        }
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 