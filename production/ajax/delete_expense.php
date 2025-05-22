<?php
require_once '../php_action/core.php';
require_once '../includes/auth_check.php';

// Check if user has permission
if (!isset($_SESSION['userRole'])) {
    echo json_encode(['error' => 'User role not found']);
    exit();
}

$userRole = $_SESSION['userRole'];
$sql = "SELECT COUNT(*) as count FROM role_permissions rp 
        INNER JOIN permissions p ON rp.permission_id = p.permission_id 
        WHERE rp.role_id = ? AND p.permission_name = 'delete_expense'";
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
    if (!isset($_POST['expense_id'])) {
        throw new Exception('Expense ID is required');
    }

    $expenseId = intval($_POST['expense_id']);
    
    // Soft delete the expense
    $sql = "UPDATE expense_entries SET 
            is_deleted = 1,
            updated_at = NOW(),
            updated_by = ?
            WHERE expense_id = ? AND is_deleted = 0";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('ii', $_SESSION['userId'], $expenseId);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete expense: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Expense not found or already deleted');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Expense deleted successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 