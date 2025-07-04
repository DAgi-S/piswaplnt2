<?php 

require_once 'core.php';
require_once 'middleware.php';

$valid = array('success' => false, 'messages' => '');

if ($_POST) {
	$currentPassword = $_POST['password'] ?? '';
	$newPassword = $_POST['npassword'] ?? '';
	$userId = $_SESSION['userId'] ?? '';

	if (!$currentPassword || !$newPassword || !$userId) {
		$valid['success'] = false;
		$valid['messages'] = 'Missing required fields.';
	} else {
		// Get current user's password
		$sql = "SELECT password FROM users WHERE user_id = ?";
		$stmt = $connect->prepare($sql);
		$stmt->bind_param("i", $userId);
		$stmt->execute();
		$result = $stmt->get_result();
		$user = $result->fetch_assoc();

		if ($user && password_verify($currentPassword, $user['password'])) {
			$newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
			$updateSql = "UPDATE users SET password = ? WHERE user_id = ?";
			$updateStmt = $connect->prepare($updateSql);
			$updateStmt->bind_param("si", $newHashedPassword, $userId);
			if ($updateStmt->execute()) {
				$valid['success'] = true;
				$valid['messages'] = "Password changed successfully";
			} else {
				$valid['success'] = false;
				$valid['messages'] = "Error changing password";
			}
			$updateStmt->close();
		} else {
			$valid['success'] = false;
			$valid['messages'] = "Current password is incorrect";
		}
		$stmt->close();
	}
}
$connect->close();
echo json_encode($valid);

?>