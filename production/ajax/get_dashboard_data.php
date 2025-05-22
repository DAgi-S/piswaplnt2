<?php
require_once '../php_action/core.php';
require_once '../includes/auth_check.php';

// Check if user has permission to view dashboard
$userId = getCurrentUserId();

// First get user's role_id
$roleQuery = "SELECT role_id FROM users WHERE user_id = ?";
$stmt = $connect->prepare($roleQuery);
$stmt->bind_param('i', $userId);
$stmt->execute();
$roleResult = $stmt->get_result();
$roleRow = $roleResult->fetch_assoc();
$roleId = $roleRow['role_id'];

// Then check permissions for this role
$sql = "SELECT COUNT(*) as count FROM role_permissions rp 
        INNER JOIN permissions p ON rp.permission_id = p.permission_id 
        WHERE rp.role_id = ? AND p.permission_name = 'view_expense'";
$stmt = $connect->prepare($sql);
$stmt->bind_param('i', $roleId);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    echo json_encode(['error' => 'Access denied. You do not have permission to view expenses.']);
    exit();
}

try {
    $response = [
        'totalExpenses' => 0,
        'pendingExpenses' => 0,
        'budgetUtilization' => 0,
        'averageDaily' => 0,
        'charts' => [
            'trend' => null,
            'distribution' => null
        ]
    ];

    // Get total expenses
    $sql = "SELECT COALESCE(SUM(amount), 0) as total 
            FROM expense_entries 
            WHERE is_deleted = 0";
    $result = $connect->query($sql);
    $row = $result->fetch_assoc();
    $response['totalExpenses'] = number_format($row['total'], 2);

    // Get pending expenses count
    $sql = "SELECT COUNT(*) as count 
            FROM expense_entries 
            WHERE status = 'pending' AND is_deleted = 0";
    $result = $connect->query($sql);
    $row = $result->fetch_assoc();
    $response['pendingExpenses'] = $row['count'];

    // Calculate budget utilization
    $sql = "SELECT 
            (SELECT COALESCE(SUM(amount), 0) 
             FROM expense_entries 
             WHERE MONTH(expense_date) = MONTH(CURRENT_DATE) 
             AND YEAR(expense_date) = YEAR(CURRENT_DATE)
             AND is_deleted = 0) as monthly_expense,
            (SELECT COALESCE(SUM(budget_limit), 0) 
             FROM expense_categories 
             WHERE deleted = 0 AND is_active = 1) as total_budget";
    $result = $connect->query($sql);
    $row = $result->fetch_assoc();
    $budgetUtilization = $row['total_budget'] > 0 
        ? ($row['monthly_expense'] / $row['total_budget']) * 100 
        : 0;
    $response['budgetUtilization'] = number_format($budgetUtilization, 1) . '%';

    // Calculate average daily expense for current month
    $sql = "SELECT AVG(daily_total) as avg_daily FROM (
            SELECT DATE(expense_date) as date, SUM(amount) as daily_total
            FROM expense_entries
            WHERE MONTH(expense_date) = MONTH(CURRENT_DATE)
            AND YEAR(expense_date) = YEAR(CURRENT_DATE)
            AND is_deleted = 0
            GROUP BY DATE(expense_date)
        ) as daily_expenses";
    $result = $connect->query($sql);
    $row = $result->fetch_assoc();
    $response['averageDaily'] = number_format($row['avg_daily'] ?? 0, 2);

    // Get monthly trend data (last 6 months)
    $sql = "SELECT 
            DATE_FORMAT(expense_date, '%Y-%m') as month,
            SUM(amount) as total
            FROM expense_entries
            WHERE expense_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
            AND is_deleted = 0
            GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
            ORDER BY month ASC";
    $result = $connect->query($sql);
    
    $labels = [];
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = date('M Y', strtotime($row['month'] . '-01'));
        $data[] = floatval($row['total']);
    }

    $response['charts']['trend'] = [
        'labels' => $labels,
        'datasets' => [[
            'label' => 'Monthly Expenses',
            'data' => $data,
            'borderColor' => '#3498db',
            'fill' => false
        ]]
    ];

    // Get category distribution
    $sql = "SELECT 
            c.name,
            COALESCE(SUM(e.amount), 0) as total
            FROM expense_categories c
            LEFT JOIN expense_entries e ON c.category_id = e.category_id 
            AND e.is_deleted = 0
            AND MONTH(e.expense_date) = MONTH(CURRENT_DATE)
            AND YEAR(e.expense_date) = YEAR(CURRENT_DATE)
            WHERE c.deleted = 0 AND c.is_active = 1
            GROUP BY c.category_id, c.name
            HAVING total > 0
            ORDER BY total DESC";
    $result = $connect->query($sql);
    
    $categoryLabels = [];
    $categoryData = [];
    $backgroundColors = [
        '#2ecc71', '#3498db', '#9b59b6', '#f1c40f', 
        '#e67e22', '#e74c3c', '#1abc9c', '#34495e'
    ];
    $colorIndex = 0;
    
    while ($row = $result->fetch_assoc()) {
        $categoryLabels[] = $row['name'];
        $categoryData[] = floatval($row['total']);
        $colorIndex = ($colorIndex + 1) % count($backgroundColors);
    }

    $response['charts']['distribution'] = [
        'labels' => $categoryLabels,
        'datasets' => [[
            'data' => $categoryData,
            'backgroundColor' => array_slice($backgroundColors, 0, count($categoryLabels))
        ]]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?> 