<?php 	

require_once 'core.php';

if(isset($_POST['categoriesId'])) {
    $categoryId = $_POST['categoriesId'];
    
    $sql = "SELECT category_id, name, status FROM categories WHERE category_id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) { 
        $row = $result->fetch_assoc();
        $response = array(
            'categories_id' => $row['category_id'],
            'categories_name' => $row['name'],
            'categories_active' => $row['status']
        );
        echo json_encode($response);
    }
    
    $stmt->close();
}

$connect->close();