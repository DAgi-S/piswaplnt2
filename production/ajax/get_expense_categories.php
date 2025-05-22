<?php
require_once '../php_action/core.php';
require_once '../includes/auth_check.php';

// Check if user has permission to view expenses
$userId = getCurrentUserId();
$userRole = $_SESSION['userRole'];
$sql = "SELECT COUNT(*) as count FROM role_permissions rp 
        INNER JOIN permissions p ON rp.permission_id = p.permission_id 
        WHERE rp.role_id = ? AND p.permission_name = 'view_expense'";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $userRole);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    echo json_encode(['error' => 'Access denied']);
    exit();
}

try {
    // Fetch active expense categories
    $sql = "SELECT category_id, name, description, budget_limit 
            FROM expense_categories 
            WHERE deleted = 0 AND is_active = 1 
            ORDER BY name ASC";
    
    $result = $connect->query($sql);
    $categories = [];
    
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'id' => $row['category_id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'budget_limit' => number_format($row['budget_limit'], 2)
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 