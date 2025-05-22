<?php
require_once 'core.php';
require_once 'classes/RawMaterialManager.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $productionOrderId = $_POST['productionOrderId'];
    $materialId = $_POST['materialId'];
    $quantity = mysqli_real_escape_string($connect, $_POST['consumeQuantity']);
    $notes = mysqli_real_escape_string($connect, $_POST['consumeNotes']);
    $userId = $_SESSION['userId'];

    try {
        // Initialize RawMaterialManager
        $rawMaterialManager = new RawMaterialManager($connect);
        
        // Consume the material
        $result = $rawMaterialManager->consumeMaterial(
            $productionOrderId,
            $materialId,
            $quantity,
            $notes,
            $userId
        );
        
        $valid['success'] = $result['success'];
        $valid['messages'] = $result['message'];
        
        if($result['success']) {
            $valid['consumed_quantity'] = $result['consumed_quantity'];
            $valid['status'] = $result['status'];
        }
        
    } catch (Exception $e) {
        $valid['success'] = false;
        $valid['messages'] = $e->getMessage();
    }
}

$connect->close();
echo json_encode($valid); 