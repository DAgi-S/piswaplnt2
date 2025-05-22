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
        WHERE rp.role_id = ? AND p.permission_name = 'edit_expense'";
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
    if (!isset($_GET['expense_id'])) {
        throw new Exception('Expense ID is required');
    }

    $expenseId = intval($_GET['expense_id']);
    
    $sql = "SELECT e.*, c.name as category_name 
            FROM expense_entries e
            LEFT JOIN expense_categories c ON e.category_id = c.category_id
            WHERE e.expense_id = ? AND e.is_deleted = 0";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $expenseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Expense not found');
    }
    
    $expense = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'expense' => $expense
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 