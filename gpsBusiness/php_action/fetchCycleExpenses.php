<?php
session_start();

// Include database connection
require_once '../../php_action/db_connect.php';
require_once '../../php_action/core.php';

// Set headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Initialize response array
$response = array(
    'success' => false,
    'messages' => array(),
    'data' => array()
);

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        http_response_code(401);
        throw new Exception("Unauthorized access");
    }

    // Check if cycle_id is provided
    if (!isset($_POST['cycle_id']) || empty($_POST['cycle_id'])) {
        http_response_code(400);
        throw new Exception("Cycle ID is required");
    }

    $cycleId = intval($_POST['cycle_id']);

    // Fetch expenses for the cycle
    $sql = "SELECT * FROM gps_business_expenses 
            WHERE business_cycle_id = ? 
            ORDER BY expense_date DESC";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $connect->error);
    }

    $stmt->bind_param("i", $cycleId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Format expense type with proper fallback
        $expenseType = !empty($row['expense_type']) ? 
            ucfirst(str_replace('_', ' ', $row['expense_type'])) : 
            'Other';

        $response['data'][] = array(
            'id' => $row['id'],
            'date' => $row['expense_date'],
            'description' => $row['description'],
            'amount' => number_format($row['amount_etb'], 2),
            'type' => $expenseType,
            'payment_method' => ucfirst(str_replace('_', ' ', $row['payment_method'])),
            'reference_number' => $row['reference_number'] ?: '-',
            'action' => '<button type="button" class="btn btn-info btn-sm" onclick="viewExpense('.$row['id'].')"><i class="fas fa-eye"></i> View</button>'
        );
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['success'] = false;
    $response['messages'][] = $e->getMessage();

} finally {
    // Close statement if it exists
    if (isset($stmt)) {
        $stmt->close();
    }

    // Close connection
    if (isset($connect) && $connect->ping()) {
        $connect->close();
    }
}

echo json_encode($response);
exit; 