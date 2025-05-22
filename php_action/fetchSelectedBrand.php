<?php 	

require_once 'core.php';

if($_POST) {
    $brandId = $_POST['brandId'];
    
    $sql = "SELECT brand_id, name, description, status FROM brands WHERE brand_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $brandId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $response = array(
            'brand_id' => $row['brand_id'],
            'brand_name' => $row['name'],
            'brand_description' => $row['description'],
            'brand_active' => $row['status']
        );
    }

    $stmt->close();
    $connect->close();

    echo json_encode($response);
}