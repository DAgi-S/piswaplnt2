<?php 	

require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {	

	$brandName = mysqli_real_escape_string($connect, $_POST['brandName']);
	$brandStatus = $_POST['brandStatus']; 

	$sql = "INSERT INTO brands (name, description, status) VALUES (?, NULL, ?)";
	$stmt = $connect->prepare($sql);
	$stmt->bind_param("si", $brandName, $brandStatus);

	if($stmt->execute()) {
		$valid['success'] = true;
		$valid['messages'] = "Brand successfully added";	
	} else {
		$valid['success'] = false;
		$valid['messages'] = "Error while adding the brand: " . $connect->error;
	}

	$stmt->close();
	$connect->close();

	echo json_encode($valid);
 
} // /if $_POST