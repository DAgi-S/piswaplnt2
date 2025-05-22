<?php
require_once 'core.php';

if(isset($_POST['productId'])) {
    $productId = $_POST['productId'];
    
    $sql = "SELECT price, quantity FROM products WHERE product_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()) {
        $response = array(
            'price' => $row['price'],
            'quantity' => $row['quantity']
        );
        echo json_encode($response);
    }
}
?> 