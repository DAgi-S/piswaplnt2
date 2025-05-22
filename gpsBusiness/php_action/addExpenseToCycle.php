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
    'messages' => array()
);

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        http_response_code(401);
        throw new Exception("Unauthorized access");
    }

    // Validate required fields
    $required_fields = array(
        'cycle_id', 'expense_date', 'description', 
        'amount_etb', 'expense_type', 'payment_method'
    );

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            http_response_code(400);
            throw new Exception("Please fill in all required fields");
        }
    }

    // Sanitize and validate inputs
    $cycleId = intval($_POST['cycle_id']);
    $expenseDate = $_POST['expense_date'];
    $description = $connect->real_escape_string($_POST['description']);
    $amountEtb = floatval($_POST['amount_etb']);
    $expenseType = $connect->real_escape_string($_POST['expense_type']);
    $paymentMethod = $connect->real_escape_string($_POST['payment_method']);
    $referenceNumber = isset($_POST['reference_number']) ? $connect->real_escape_string($_POST['reference_number']) : null;
    $notes = isset($_POST['notes']) ? $connect->real_escape_string($_POST['notes']) : null;

    // Check if cycle exists and is active
    $cycleSql = "SELECT status FROM gps_business_cycles WHERE id = ?";
    $cycleStmt = $connect->prepare($cycleSql);
    $cycleStmt->bind_param("i", $cycleId);
    $cycleStmt->execute();
    $cycleResult = $cycleStmt->get_result();
    $cycle = $cycleResult->fetch_assoc();

    if (!$cycle) {
        http_response_code(404);
        throw new Exception("Business cycle not found");
    }

    if ($cycle['status'] !== 'active') {
        http_response_code(400);
        throw new Exception("Cannot add expenses to a completed or cancelled cycle");
    }

    // Start transaction
    $connect->begin_transaction();

    // Insert expense
    $sql = "INSERT INTO gps_business_expenses (
        business_cycle_id,
        expense_date,
        description,
        amount_etb,
        expense_type,
        payment_method,
        reference_number,
        notes,
        created_at,
        created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Error preparing query: " . $connect->error);
    }

    $stmt->bind_param("issdssssi", 
        $cycleId,
        $expenseDate,
        $description,
        $amountEtb,
        $expenseType,
        $paymentMethod,
        $referenceNumber,
        $notes,
        $_SESSION['userId']
    );

    if (!$stmt->execute()) {
        throw new Exception("Error adding expense: " . $stmt->error);
    }

    $expenseId = $connect->insert_id;

    // Add to expense history
    $historySql = "INSERT INTO gps_business_expense_history (
        expense_id,
        action,
        old_value,
        new_value,
        notes,
        created_at,
        created_by
    ) VALUES (?, 'create', NULL, ?, 'Expense created', NOW(), ?)";

    $historyStmt = $connect->prepare($historySql);
    $newValue = json_encode(array(
        'amount_etb' => $amountEtb,
        'description' => $description,
        'expense_type' => $expenseType
    ));
    $historyStmt->bind_param("isi", $expenseId, $newValue, $_SESSION['userId']);
    
    if (!$historyStmt->execute()) {
        throw new Exception("Error recording expense history: " . $historyStmt->error);
    }

    // Commit transaction
    $connect->commit();

    $response['success'] = true;
    $response['messages'][] = "Expense added successfully";

} catch (Exception $e) {
    // Rollback transaction if active
    if ($connect && $connect->ping()) {
        $connect->rollback();
    }

    $response['success'] = false;
    $response['messages'][] = $e->getMessage();

} finally {
    // Close statements if they exist
    if (isset($cycleStmt)) {
        $cycleStmt->close();
    }
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($historyStmt)) {
        $historyStmt->close();
    }

    // Close connection
    if (isset($connect) && $connect->ping()) {
        $connect->close();
    }
}

echo json_encode($response);
exit; 