<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'core.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    http_response_code(401);
    echo json_encode(array(
        'success' => false,
        'messages' => array('Session expired. Please log in again.')
    ));
    exit();
}

$response = array(
    'success' => false,
    'messages' => array()
);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    try {
        $id = (int)$_POST['id'];

        // Begin transaction
        $connect->begin_transaction();

        try {
            // First get the image file path if exists
            $sql = "SELECT payment_image FROM gps_payment_followup WHERE id = ?";
            $stmt = $connect->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing select statement: " . $connect->error);
            }

            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                throw new Exception("Error executing select query: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $image_path = $row['payment_image'] ?? null;
            $stmt->close();

            // Delete the record
            $sql = "DELETE FROM gps_payment_followup WHERE id = ?";
            $stmt = $connect->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing delete statement: " . $connect->error);
            }

            $stmt->bind_param('i', $id);
            if (!$stmt->execute()) {
                throw new Exception("Error deleting payment follow-up: " . $stmt->error);
            }

            if ($stmt->affected_rows === 0) {
                throw new Exception("Record not found or already deleted.");
            }

            $stmt->close();

            // If deletion was successful and we have an image, delete it
            if ($image_path && file_exists('../' . $image_path)) {
                if (!unlink('../' . $image_path)) {
                    // Log the error but don't throw exception as the record is already deleted
                    error_log("Warning: Could not delete file: " . $image_path);
                }
            }

            // Commit transaction
            $connect->commit();

            $response['success'] = true;
            $response['messages'][] = "Payment follow-up deleted successfully";

        } catch (Exception $e) {
            // Rollback transaction on error
            $connect->rollback();
            throw $e;
        }

    } catch (Exception $e) {
        $response['messages'][] = $e->getMessage();
        error_log("Error in deleteGpsPaymentFollowup.php: " . $e->getMessage());
    }
} else {
    $response['messages'][] = "Invalid request";
}

// Close the database connection
if (isset($connect)) {
    $connect->close();
}

// Set the content type to JSON
header('Content-Type: application/json');
echo json_encode($response); 