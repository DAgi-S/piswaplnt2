<?php
require_once 'core.php';
require_once 'db_connect.php';

if ($_POST) {
    $response = array();
    
    // Validate input
    $userId = $_SESSION['userId'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $fullName = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);

    // Validate username
    if (strlen($username) < 3) {
        $response['success'] = false;
        $response['message'] = 'Username must be at least 3 characters long';
        echo json_encode($response);
        exit();
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['success'] = false;
        $response['message'] = 'Invalid email format';
        echo json_encode($response);
        exit();
    }

    // Check if username exists for other users
    $sql = "SELECT * FROM users WHERE username = ? AND user_id != ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("si", $username, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['success'] = false;
        $response['message'] = 'Username already exists';
        echo json_encode($response);
        exit();
    }

    // Check if email exists for other users
    $sql = "SELECT * FROM users WHERE email = ? AND user_id != ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $response['success'] = false;
        $response['message'] = 'Email already exists';
        echo json_encode($response);
        exit();
    }

    // Handle profile image upload
    $profileImage = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_image'];
        $allowedTypes = ['image/jpeg', 'image/png'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            $response['success'] = false;
            $response['message'] = 'Only JPG and PNG files are allowed';
            echo json_encode($response);
            exit();
        }

        // Validate file size
        if ($file['size'] > $maxSize) {
            $response['success'] = false;
            $response['message'] = 'File size must be less than 2MB';
            echo json_encode($response);
            exit();
        }

        // Create upload directory if it doesn't exist
        $uploadDir = '../uploads/profile_images/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $userId . '_' . uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $profileImage = 'uploads/profile_images/' . $filename;

            // Delete old profile image if exists
            $sql = "SELECT profile_image FROM users WHERE user_id = ?";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $oldImage = $result->fetch_assoc()['profile_image'];

            if ($oldImage && $oldImage !== 'assets/images/default-user.png' && file_exists('../' . $oldImage)) {
                unlink('../' . $oldImage);
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Error uploading profile image';
            echo json_encode($response);
            exit();
        }
    }

    // Start transaction
    $connect->begin_transaction();

    try {
        // Update user information
        if ($profileImage) {
            $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, profile_image = ? WHERE user_id = ?";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("sssssi", $username, $email, $fullName, $phone, $profileImage, $userId);
        } else {
            $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, phone = ? WHERE user_id = ?";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("ssssi", $username, $email, $fullName, $phone, $userId);
        }

        if (!$stmt->execute()) {
            throw new Exception('Error updating profile: ' . $connect->error);
        }

        // Update session variables
        $_SESSION['userName'] = $username;
        if ($profileImage) {
            $_SESSION['profileImage'] = $profileImage;
        }

        // Log the action
        $logSql = "INSERT INTO audit_log (user_id, activity_type, description, ip_address) VALUES (?, 'update_profile', ?, ?)";
        $logStmt = $connect->prepare($logSql);
        $description = "Updated profile information";
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $logStmt->bind_param("iss", $userId, $description, $ipAddress);
        
        if (!$logStmt->execute()) {
            throw new Exception('Error logging action: ' . $connect->error);
        }

        // Commit transaction
        $connect->commit();

        $response['success'] = true;
        $response['message'] = 'Profile updated successfully';
        if ($profileImage) {
            $response['profileImage'] = $profileImage;
        }

    } catch (Exception $e) {
        // Rollback transaction on error
        $connect->rollback();
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    $stmt->close();
    $connect->close();

    echo json_encode($response);
} 