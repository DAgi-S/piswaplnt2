<?php
require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {
    $saleId = $_POST['saleId'];
    $sale_date = $_POST['editSaleDate'];
    $buyer_name = $_POST['editBuyerName'];
    $buyer_phone = $_POST['editBuyerPhone'];
    $quantity = $_POST['editQuantity'];
    $installed_by = $_POST['editInstalledBy'];
    $price = $_POST['editPrice'];
    $comment = $_POST['editComment'];
    
    $sql = "UPDATE gps_sales SET 
        sale_date = ?,
        buyer_name = ?,
        buyer_phone = ?,
        quantity = ?,
        installed_by = ?,
        price = ?,
        comment = ?
        WHERE id = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param(
        'sssisssi',
        $sale_date,
        $buyer_name,
        $buyer_phone,
        $quantity,
        $installed_by,
        $price,
        $comment,
        $saleId
    );
    
    if($stmt->execute()) {
        $valid['success'] = true;
        $valid['messages'] = "Sale updated successfully";
    } else {
        $valid['success'] = false;
        $valid['messages'] = "Error while updating the sale";
    }
    
    $stmt->close();
    $connect->close();
    
    echo json_encode($valid);
} 