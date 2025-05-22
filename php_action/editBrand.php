<?php 	

require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {	

	$brandId = $_POST['brandId'];
	$brandName = mysqli_real_escape_string($connect, $_POST['editBrandName']);
	$brandStatus = $_POST['editBrandStatus'];

	$sql = "UPDATE brands SET name = ?, status = ? WHERE brand_id = ?";
	$stmt = $connect->prepare($sql);
	$stmt->bind_param("sii", $brandName, $brandStatus, $brandId);

	if($stmt->execute()) {
		$valid['success'] = true;
		$valid['messages'] = "Brand successfully updated";
	} else {
		$valid['success'] = false;
		$valid['messages'] = "Error while updating brand: " . $connect->error;
	}

	$stmt->close();
	$connect->close();

	echo json_encode($valid);
 
} // /if $_POST