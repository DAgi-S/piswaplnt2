<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_connect.php';

header('Content-Type: application/json');

try {
    // Get and validate parameters
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
    $account_id = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;

    // Log incoming request
    error_log("Fetching transactions with params: " . json_encode([
        'start_date' => $start_date,
        'end_date' => $end_date,
        'account_id' => $account_id
    ]));

    $sql = "SELECT 
            d.id,
            DATE_FORMAT(d.transaction_date, '%Y-%m-%d') as transaction_date,
            a.account_owner as account_name,
            d.platform,
            d.type,
            d.amount,
            CASE 
                WHEN d.status = 1 THEN 'Completed'
                WHEN d.status = 0 THEN 'Pending'
                ELSE 'Failed'
            END as status
            FROM digitalswap d
            LEFT JOIN accounts a ON d.account_id = a.id
            WHERE d.transaction_date BETWEEN ? AND ?";
    
    $params = [$start_date, $end_date];
    $types = "ss";
    
    if ($account_id > 0) {
        $sql .= " AND d.account_id = ?";
        $params[] = $account_id;
        $types .= "i";
    }
    
    $sql .= " ORDER BY d.transaction_date DESC";

    // Log the query
    error_log("Executing query: " . $sql);
    
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $connect->error);
    }

    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $data = [];
    
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'transaction_date' => $row['transaction_date'],
            'account' => htmlspecialchars($row['account_name'] ?? 'N/A'),
            'platform' => htmlspecialchars($row['platform']),
            'type' => '<span class="badge ' . 
                     ($row['type'] === 'deposit' ? 'bg-success' : 'bg-danger') . '">' . 
                     ucfirst($row['type']) . '</span>',
            'amount' => '<span class="text-' . 
                       ($row['type'] === 'deposit' ? 'success' : 'danger') . '">' . 
                       ($row['type'] === 'deposit' ? '+' : '-') . 
                       '$' . number_format(abs($row['amount']), 2) . '</span>',
            'status' => '<span class="badge ' . 
                       ($row['status'] === 'Completed' ? 'bg-success' : 
                        ($row['status'] === 'Pending' ? 'bg-warning' : 'bg-danger')) . 
                       '">' . $row['status'] . '</span>',
            'actions' => '<div class="btn-group btn-group-sm">
                            <button class="btn btn-info" onclick="viewTransaction('.$row['id'].')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-primary" onclick="editTransaction('.$row['id'].')">
                                <i class="fas fa-edit"></i>
                            </button>
                         </div>'
        ];
    }

    // Log the response size
    error_log("Sending response with " . count($data) . " records");

    echo json_encode([
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => count($data),
        'recordsFiltered' => count($data),
        'data' => $data
    ]);

} catch (Exception $e) {
    error_log("Transaction fetch error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error fetching transactions: ' . $e->getMessage(),
        'data' => []
    ]);
} 