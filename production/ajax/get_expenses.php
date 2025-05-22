<?php
require_once '../php_action/core.php';
require_once '../includes/auth_check.php';

// Check if user has permission to view expenses
if (!isset($_SESSION['userRole'])) {
    echo json_encode(['error' => 'User role not found']);
    exit();
}

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
    echo json_encode([
        'data' => [],
        'error' => 'Access denied - Insufficient permissions'
    ]);
    exit();
}

try {
    // Get filter parameters
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    $categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;

    // Build query
    $sql = "SELECT 
        e.expense_id,
        e.expense_date,
        c.name as category_name,
        e.description,
        e.amount,
        e.status,
        e.payment_method,
        e.attachment,
        u.username as created_by_name
    FROM expense_entries e
    LEFT JOIN expense_categories c ON e.category_id = c.category_id
    LEFT JOIN users u ON e.created_by = u.user_id
    WHERE e.is_deleted = 0";
    
    $params = [];
    $types = '';

    if ($startDate) {
        $sql .= " AND e.expense_date >= ?";
        $params[] = $startDate;
        $types .= 's';
    }
    if ($endDate) {
        $sql .= " AND e.expense_date <= ?";
        $params[] = $endDate;
        $types .= 's';
    }
    if ($categoryId) {
        $sql .= " AND e.category_id = ?";
        $params[] = $categoryId;
        $types .= 'i';
    }
    if ($status) {
        $sql .= " AND e.status = ?";
        $params[] = $status;
        $types .= 's';
    }

    $sql .= " ORDER BY e.expense_date DESC";

    // Prepare and execute the statement
    $stmt = $connect->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $actionButtons = getActionButtons($row['expense_id'], $row['status'], $userRole);
        
        // Format status with appropriate badge class
        $statusBadge = '';
        switch ($row['status']) {
            case 'pending':
                $statusBadge = '<span class="badge badge-warning">Pending</span>';
                break;
            case 'approved':
                $statusBadge = '<span class="badge badge-success">Approved</span>';
                break;
            case 'rejected':
                $statusBadge = '<span class="badge badge-danger">Rejected</span>';
                break;
            default:
                $statusBadge = '<span class="badge badge-secondary">' . ucfirst($row['status']) . '</span>';
        }

        $expenses[] = [
            'date' => date('Y-m-d', strtotime($row['expense_date'])),
            'category' => $row['category_name'],
            'description' => $row['description'],
            'amount' => number_format($row['amount'], 2),
            'payment_method' => ucfirst(str_replace('_', ' ', $row['payment_method'])),
            'status' => $statusBadge,
            'created_by' => $row['created_by_name'],
            'attachment' => $row['attachment'] ? "<a href='../uploads/expenses/{$row['attachment']}' target='_blank'><i class='fas fa-file-download'></i></a>" : '',
            'actions' => $actionButtons
        ];
    }

    echo json_encode([
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => count($expenses),
        'recordsFiltered' => count($expenses),
        'data' => $expenses
    ]);

} catch (Exception $e) {
    echo json_encode([
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $e->getMessage()
    ]);
}

function getActionButtons($expenseId, $status, $userRole) {
    $actions = '';
    
    // Check permissions based on role
    $canEdit = checkUserPermission($userRole, 'edit_expense');
    $canDelete = checkUserPermission($userRole, 'delete_expense');
    $canApprove = checkUserPermission($userRole, 'approve_expense');

    if ($canEdit) {
        $actions .= "<button class='btn btn-sm btn-primary edit-expense' data-id='{$expenseId}'><i class='fas fa-edit'></i></button> ";
    }
    if ($canDelete) {
        $actions .= "<button class='btn btn-sm btn-danger delete-expense' data-id='{$expenseId}'><i class='fas fa-trash'></i></button> ";
    }
    if ($canApprove && $status == 'pending') {
        $actions .= "<button class='btn btn-sm btn-success approve-expense' data-id='{$expenseId}'><i class='fas fa-check'></i></button> ";
        $actions .= "<button class='btn btn-sm btn-warning reject-expense' data-id='{$expenseId}'><i class='fas fa-times'></i></button>";
    }

    return $actions;
}

// Helper function to check user permission
function checkUserPermission($userRole, $permissionName) {
    global $connect;
    $sql = "SELECT COUNT(*) as count FROM role_permissions rp 
            INNER JOIN permissions p ON rp.permission_id = p.permission_id 
            WHERE rp.role_id = ? AND p.permission_name = ?";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('is', $userRole, $permissionName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] > 0;
}
?> 