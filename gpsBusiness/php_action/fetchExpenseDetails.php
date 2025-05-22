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
    'data' => null
);

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        http_response_code(401);
        throw new Exception("Unauthorized access");
    }

    // Check if expense_id is provided
    if (!isset($_POST['expense_id']) || empty($_POST['expense_id'])) {
        http_response_code(400);
        throw new Exception("Expense ID is required");
    }

    $expenseId = intval($_POST['expense_id']);

    // Fetch expense details
    $sql = "SELECT e.*, 
            CONCAT(u.firstname, ' ', u.lastname) as created_by_name
            FROM gps_business_expenses e
            LEFT JOIN users u ON e.created_by = u.user_id
            WHERE e.id = ?";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $connect->error);
    }

    $stmt->bind_param("i", $expenseId);
    $stmt->execute();
    $result = $stmt->get_result();
    $expense = $result->fetch_assoc();

    if (!$expense) {
        http_response_code(404);
        throw new Exception("Expense not found");
    }

    // Format the data
    $response['data'] = array(
        'expense_date' => $expense['expense_date'],
        'description' => $expense['description'],
        'amount_etb' => number_format($expense['amount_etb'], 2),
        'expense_type' => ucfirst(str_replace('_', ' ', $expense['expense_type'])),
        'payment_method' => ucfirst(str_replace('_', ' ', $expense['payment_method'])),
        'reference_number' => $expense['reference_number'] ?: '-',
        'notes' => $expense['notes'] ?: '-',
        'created_at' => $expense['created_at'],
        'created_by' => $expense['created_by_name']
    );

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