<?php
require_once '../../php_action/core.php';

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array(
        'success' => false,
        'message' => 'Invalid request method'
    ));
    exit();
}

// Validate payment ID
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Payment ID is required'
    ));
    exit();
}

try {
    // Sanitize input
    $payment_id = intval($_POST['id']);

    // Check if payment exists
    $check_sql = "SELECT id FROM gps_payments WHERE id = ?";
    $check_stmt = $connect->prepare($check_sql);
    $check_stmt->bind_param('i', $payment_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        echo json_encode(array(
            'success' => false,
            'message' => 'Payment not found'
        ));
        exit();
    }

    // Delete payment record
    $sql = "DELETE FROM gps_payments WHERE id = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $payment_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response = array(
                'success' => true,
                'message' => 'Payment deleted successfully'
            );
        } else {
            $response = array(
                'success' => false,
                'message' => 'Failed to delete payment'
            );
        }
    } else {
        throw new Exception($stmt->error);
    }

} catch (Exception $e) {
    $response = array(
        'success' => false,
        'message' => 'Error deleting payment: ' . $e->getMessage()
    );
    http_response_code(500);
}

// Close the database connection
$connect->close();

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response); 