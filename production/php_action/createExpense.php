<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'core.php';
require_once 'db_connect.php';

// Set proper headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    if (empty($_POST['expenseDate']) || empty($_POST['expenseCategory']) || 
        empty($_POST['expenseAmount']) || empty($_POST['productionOrder'])) {
        throw new Exception('Please fill in all required fields');
    }

    // Start transaction
    $connect->begin_transaction();

    // Generate expense number (format: EXP-YYYYMMDD-XXX)
    $date = date('Ymd');
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(expense_number, '-', -1) AS UNSIGNED)) as max_num 
            FROM production_expenses 
            WHERE expense_number LIKE 'EXP-$date-%'";
    $result = $connect->query($sql);
    $row = $result->fetch_assoc();
    $next_num = str_pad(($row['max_num'] + 1), 3, '0', STR_PAD_LEFT);
    $expense_number = "EXP-$date-$next_num";

    // Insert expense record
    $sql = "INSERT INTO production_expenses (
                expense_number, 
                expense_date, 
                category_id, 
                amount, 
                production_order_id, 
                description, 
                status, 
                created_by, 
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'draft', ?, NOW())";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param(
        'ssidisi',
        $expense_number,
        $_POST['expenseDate'],
        $_POST['expenseCategory'],
        $_POST['expenseAmount'],
        $_POST['productionOrder'],
        $_POST['expenseDescription'],
        $_SESSION['userId']
    );

    if (!$stmt->execute()) {
        throw new Exception("Error creating expense: " . $stmt->error);
    }

    $expense_id = $connect->insert_id;

    // Handle file upload if present
    if (isset($_FILES['expenseAttachment']) && $_FILES['expenseAttachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['expenseAttachment'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_types = array('jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx');

        if (!in_array($file_ext, $allowed_types)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowed_types));
        }

        // Create upload directory if it doesn't exist
        $upload_dir = '../uploads/expenses/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $new_filename = $expense_number . '-' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;

        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Error uploading file');
        }

        // Insert attachment record
        $sql = "INSERT INTO production_expense_attachments (
                    expense_id, 
                    file_name, 
                    file_path, 
                    uploaded_by, 
                    uploaded_at
                ) VALUES (?, ?, ?, ?, NOW())";

        $stmt = $connect->prepare($sql);
        $stmt->bind_param(
            'issi',
            $expense_id,
            $file['name'],
            $new_filename,
            $_SESSION['userId']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error saving attachment: " . $stmt->error);
        }
    }

    // Commit transaction
    $connect->commit();

    echo json_encode(array(
        'success' => true,
        'messages' => 'Expense created successfully',
        'expense_id' => $expense_id,
        'expense_number' => $expense_number
    ));

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($connect) && $connect->ping()) {
        $connect->rollback();
    }

    error_log("Error in createExpense.php: " . $e->getMessage());
    echo json_encode(array(
        'success' => false,
        'messages' => $e->getMessage()
    ));

} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($connect)) {
        $connect->close();
    }
} 