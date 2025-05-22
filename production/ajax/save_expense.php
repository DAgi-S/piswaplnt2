<?php
require_once '../php_action/core.php';
require_once '../includes/auth_check.php';

// Check if user has permission to manage expenses
if (!isset($_SESSION['userRole'])) {
    echo json_encode(['error' => 'User role not found']);
    exit();
}

$userRole = $_SESSION['userRole'];
$sql = "SELECT COUNT(*) as count FROM role_permissions rp 
        INNER JOIN permissions p ON rp.permission_id = p.permission_id 
        WHERE rp.role_id = ? AND p.permission_name = 'create_expense'";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $userRole);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    echo json_encode(['error' => 'Access denied - Insufficient permissions']);
    exit();
}

try {
    // Validate required fields
    $requiredFields = ['expenseDate', 'expenseCategory', 'expenseAmount', 'expensePaymentMethod'];
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $missingFields[] = $field;
        }
    }
    if (!empty($missingFields)) {
        throw new Exception('Required fields missing: ' . implode(', ', $missingFields));
    }

    // Sanitize and validate input
    $expenseId = isset($_POST['expenseId']) ? intval($_POST['expenseId']) : null;
    $expenseDate = date('Y-m-d', strtotime($_POST['expenseDate']));
    $categoryId = intval($_POST['expenseCategory']);
    $description = isset($_POST['expenseDescription']) ? $_POST['expenseDescription'] : '';
    $amount = floatval($_POST['expenseAmount']);
    $paymentMethod = $_POST['expensePaymentMethod'];
    $status = 'pending'; // Default status for new expenses

    // Validate amount
    if ($amount <= 0) {
        throw new Exception('Amount must be greater than zero');
    }

    // Validate category exists
    $sql = "SELECT category_id FROM expense_categories WHERE category_id = ? AND deleted = 0 AND is_active = 1";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $categoryId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Invalid category selected');
    }

    // Handle file upload if present
    $attachment = null;
    if (isset($_FILES['expenseAttachment']) && $_FILES['expenseAttachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/expenses/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileInfo = pathinfo($_FILES['expenseAttachment']['name']);
        $extension = strtolower($fileInfo['extension']);
        
        // Validate file type
        $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
        }

        // Generate unique filename
        $filename = uniqid('expense_') . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['expenseAttachment']['tmp_name'], $targetPath)) {
            $attachment = $filename;
        } else {
            throw new Exception('Failed to upload file');
        }
    }

    if ($expenseId) {
        // Update existing expense
        $sql = "UPDATE expense_entries SET 
                expense_date = ?,
                category_id = ?,
                description = ?,
                amount = ?,
                payment_method = ?,
                updated_at = NOW(),
                updated_by = ?
                WHERE expense_id = ? AND is_deleted = 0";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('sisdsii', $expenseDate, $categoryId, $description, $amount, $paymentMethod, $_SESSION['userId'], $expenseId);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update expense: ' . $stmt->error);
        }
        
        if ($attachment) {
            $sql = "UPDATE expense_entries SET attachment = ? WHERE expense_id = ?";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param('si', $attachment, $expenseId);
            $stmt->execute();
        }
    } else {
        // Insert new expense
        $sql = "INSERT INTO expense_entries (
                expense_date, category_id, description, amount, 
                payment_method, status, attachment, created_by, 
                created_at, is_deleted
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('sisdsssi', 
            $expenseDate, $categoryId, $description, $amount,
            $paymentMethod, $status, $attachment, $_SESSION['userId']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create expense: ' . $stmt->error);
        }
        $expenseId = $connect->insert_id;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Expense ' . ($expenseId ? 'updated' : 'created') . ' successfully',
        'expense_id' => $expenseId
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 