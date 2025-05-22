<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $materialId = $_POST['materialId'];
    $materialCode = mysqli_real_escape_string($connect, $_POST['editMaterialCode']);
    $materialName = mysqli_real_escape_string($connect, $_POST['editMaterialName']);
    $materialCategory = mysqli_real_escape_string($connect, $_POST['editMaterialCategory']);
    $materialUnit = mysqli_real_escape_string($connect, $_POST['editMaterialUnit']);
    $minStockLevel = mysqli_real_escape_string($connect, $_POST['editMinStockLevel']);
    $materialDescription = mysqli_real_escape_string($connect, $_POST['editMaterialDescription']);

    // Start transaction
    $connect->begin_transaction();

    try {
        // Check if material code exists for other materials
        $checkSql = "SELECT id FROM raw_materials WHERE material_code = ? AND id != ?";
        $checkStmt = $connect->prepare($checkSql);
        $checkStmt->bind_param("si", $materialCode, $materialId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if($result->num_rows > 0) {
            $valid['success'] = false;
            $valid['messages'] = "Material code already exists";
            $checkStmt->close();
            throw new Exception("Material code already exists");
        }
        $checkStmt->close();

        // Update raw material
        $sql = "UPDATE raw_materials 
                SET material_code = ?,
                    name = ?,
                    category = ?,
                    unit = ?,
                    min_stock_level = ?,
                    description = ?
                WHERE id = ?";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("ssssdsi", 
            $materialCode,
            $materialName,
            $materialCategory,
            $materialUnit,
            $minStockLevel,
            $materialDescription,
            $materialId
        );

        if($stmt->execute()) {
            $valid['success'] = true;
            $valid['messages'] = "Raw material successfully updated";
            
            // Commit transaction
            $connect->commit();
        } else {
            throw new Exception("Error updating raw material");
        }
        
        $stmt->close();

    } catch (Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        
        if(!isset($valid['messages'])) {
            $valid['success'] = false;
            $valid['messages'] = $e->getMessage();
        }
    }

    $connect->close();

    echo json_encode($valid);
} 