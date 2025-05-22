<?php 	

require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {	

	$categoriesName = mysqli_real_escape_string($connect, $_POST['categoriesName']);
	$categoriesStatus = $_POST['categoriesStatus']; 

	$sql = "INSERT INTO categories (name, description, status) VALUES (?, NULL, ?)";
	$stmt = $connect->prepare($sql);
	$stmt->bind_param("si", $categoriesName, $categoriesStatus);

	if($stmt->execute()) {
		$valid['success'] = true;
		$valid['messages'] = "Category successfully added";	
	} else {
		$valid['success'] = false;
		$valid['messages'] = "Error while adding the category: " . $connect->error;
	}

	$stmt->close();
	$connect->close();

	echo json_encode($valid);
 
} // /if $_POST