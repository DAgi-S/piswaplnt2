<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $supplierId = $_POST['supplierId'];

    // Check if supplier has any associated purchases
    $checkSql = "SELECT COUNT(*) as count FROM purchases WHERE supplier_id = ? AND active = 1";
    $checkStmt = $connect->prepare($checkSql);
    $checkStmt->bind_param("i", $supplierId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();

    if($row['count'] > 0) {
        $valid['success'] = false;
        $valid['messages'] = "Cannot delete supplier. There are purchases associated with this supplier.";
    } else {
        // Soft delete the supplier
        $sql = "UPDATE suppliers SET active = 0, updated_at = NOW() WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $supplierId);

        if($stmt->execute()) {
            $valid['success'] = true;
            $valid['messages'] = "Supplier removed successfully";
        } else {
            $valid['success'] = false;
            $valid['messages'] = "Error while removing supplier";
        }

        $stmt->close();
    }

    $checkStmt->close();
    $connect->close();

    echo json_encode($valid);
} 