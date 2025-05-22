<?php
require_once 'core.php';
require_once 'db_connect.php';

if(isset($_POST['id'])) {
    $id = $_POST['id'];
    $sql = "SELECT * FROM digitalswap WHERE id = ? LIMIT 1";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $response = array();
    if($data) {
        $response['success'] = true;
        $response['data'] = $data;
        if($data['image']) {
            $response['data']['imageUrl'] = 'uploads/digitalswap/'.$data['image'];
        }
    } else {
        $response['success'] = false;
        $response['messages'] = "Record not found";
    }
    
    $stmt->close();
    $connect->close();
    
    echo json_encode($response);
}