<?php
require_once 'core.php';
require_once 'db_connect.php';

if($_POST) {
    $supplierId = $_POST['supplierId'];

    if($supplierId) {
        // Check if supplier exists
        $checkSql = "SELECT * FROM suppliers WHERE id = ?";
        $stmt = $connect->prepare($checkSql);
        $stmt->bind_param("i", $supplierId);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows > 0) {
            // Instead of deleting, we'll update the active status to 0
            $updateSql = "UPDATE suppliers SET active = 0 WHERE id = ?";
            $updateStmt = $connect->prepare($updateSql);
            $updateStmt->bind_param("i", $supplierId);
            $updateResult = $updateStmt->execute();

            if($updateResult) {
                $valid['success'] = true;
                $valid['messages'] = "Supplier removed successfully";
            } else {
                $valid['success'] = false;
                $valid['messages'] = "Error while removing the supplier";
            }
            $updateStmt->close();
        } else {
            $valid['success'] = false;
            $valid['messages'] = "Supplier not found";
        }
        $stmt->close();
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Invalid supplier ID";
    }

    $connect->close();

    echo json_encode($valid);
} 