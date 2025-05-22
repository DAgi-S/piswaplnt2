<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $materialId = $_POST['materialId'];

    // Start transaction
    $connect->begin_transaction();

    try {
        // Check if material is being used in any production order
        $checkSql = "SELECT COUNT(*) as count 
                    FROM production_order_materials 
                    WHERE product_id = ? 
                    AND status != 'fully_consumed'";
                    
        $checkStmt = $connect->prepare($checkSql);
        $checkStmt->bind_param("i", $materialId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        
        if($row['count'] > 0) {
            throw new Exception("Cannot delete material. It is being used in active production orders.");
        }
        $checkStmt->close();

        // Soft delete by setting status to inactive
        $sql = "UPDATE raw_materials SET status = 'inactive' WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("i", $materialId);
        
        if($stmt->execute()) {
            $valid['success'] = true;
            $valid['messages'] = "Raw material successfully deleted";
            
            // Commit transaction
            $connect->commit();
        } else {
            throw new Exception("Error deleting raw material");
        }
        
        $stmt->close();

    } catch (Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        
        $valid['success'] = false;
        $valid['messages'] = $e->getMessage();
    }

    $connect->close();

    echo json_encode($valid);
} 