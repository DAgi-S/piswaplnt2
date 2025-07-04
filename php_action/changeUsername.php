<?php 

require_once 'core.php';

$valid = array('success' => false, 'messages' => '');

if ($_POST) {
	$username = $_POST['username'] ?? '';
	$userId = $_POST['user_id'] ?? '';

	if (!$username || !$userId) {
		$valid['success'] = false;
		$valid['messages'] = 'Missing username or user ID.';
	} else {
		$stmt = $connect->prepare("UPDATE users SET username = ? WHERE user_id = ?");
		$stmt->bind_param("si", $username, $userId);
		if ($stmt->execute()) {
			$valid['success'] = true;
			$valid['messages'] = "Successfully updated username.";
		} else {
			$valid['success'] = false;
			$valid['messages'] = "Error while updating username.";
		}
		$stmt->close();
	}
}
$connect->close();
echo json_encode($valid);

?>