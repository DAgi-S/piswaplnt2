<?php
require_once 'core.php';
require_once 'db_connect.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $transactionIds = explode(',', $_POST['transactionId']);
    $categoryId = $_POST['categoryId'];
    $assigned = 0;

    // Start transaction
    $connect->begin_transaction();

    try {
        // First delete any existing category assignments for these transactions
        $sql = "DELETE FROM digital_transaction_categories WHERE transaction_id IN (" . implode(',', array_fill(0, count($transactionIds), '?')) . ")";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($transactionIds)), ...$transactionIds);
        $stmt->execute();

        // Then insert new category assignments
        $sql = "INSERT INTO digital_transaction_categories (transaction_id, category_id) VALUES (?, ?)";
        $stmt = $connect->prepare($sql);
        
        foreach($transactionIds as $transactionId) {
            $stmt->bind_param("ii", $transactionId, $categoryId);
            if($stmt->execute()) {
                $assigned++;
            }
        }

        // If all went well, commit the transaction
        $connect->commit();
        
        $valid['success'] = true;
        $valid['messages'] = "$assigned transactions were successfully categorized";
    } catch (Exception $e) {
        // If there was an error, rollback the transaction
        $connect->rollback();
        $valid['success'] = false;
        $valid['messages'] = "Error while assigning categories: " . $e->getMessage();
    }

    $stmt->close();
    $connect->close();

    echo json_encode($valid);
} 