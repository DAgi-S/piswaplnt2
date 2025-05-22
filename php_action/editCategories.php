<?php 	

require_once 'core.php';

$valid['success'] = array('success' => false, 'messages' => array());

if($_POST) {	

	$categoryName = mysqli_real_escape_string($connect, $_POST['editCategoriesName']);
	$categoryStatus = $_POST['editCategoriesStatus']; 
	$categoryId = $_POST['editCategoriesId'];

	$sql = "UPDATE categories SET name = ?, status = ? WHERE category_id = ?";
	$stmt = $connect->prepare($sql);
	$stmt->bind_param("sii", $categoryName, $categoryStatus, $categoryId);

	if($stmt->execute()) {
		$valid['success'] = true;
		$valid['messages'] = "Category successfully updated";	
	} else {
		$valid['success'] = false;
		$valid['messages'] = "Error while updating the category: " . $connect->error;
	}
	 
	$stmt->close();
	$connect->close();

	echo json_encode($valid);
 
} // /if $_POST