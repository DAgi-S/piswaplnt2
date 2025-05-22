<?php
require_once 'core.php';
require_once 'db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $startDate = isset($_POST['startDate']) ? $_POST['startDate'] : date('Y-m-01');
    $endDate = isset($_POST['endDate']) ? $_POST['endDate'] : date('Y-m-d');
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    $accountId = isset($_POST['accountId']) ? $_POST['accountId'] : '';

    // Get report data
    $sql = "SELECT d.*, a.account_owner, a.account_platform 
            FROM digitalswap d 
            LEFT JOIN accounts a ON d.account_id = a.id 
            WHERE DATE(d.transaction_date) BETWEEN ? AND ?";
    $params = [$startDate, $endDate];
    $types = "ss";

    if($type && $type !== 'All Types') {
        $sql .= " AND d.type = ?";
        $params[] = $type;
        $types .= "s";
    }

    if($accountId) {
        $sql .= " AND d.account_id = ?";
        $params[] = $accountId;
        $types .= "i";
    }

    $sql .= " ORDER BY d.transaction_date DESC";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $connect->error);
    }

    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    // Generate report HTML
    $html = '<div class="table-responsive">';
    $html .= '<table class="table table-bordered table-hover">';
    $html .= '<thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Platform</th>
                    <th>Account</th>
                    <th class="text-right">Amount</th>
                    <th>Comment</th>
                </tr>
            </thead>
            <tbody>';

    $totalDeposit = 0;
    $totalWithdraw = 0;

    while($row = $result->fetch_assoc()) {
        $amount = $row['amount'];
        if($row['type'] == 'deposit') {
            $totalDeposit += $amount;
        } else {
            $totalWithdraw += $amount;
        }

        $html .= sprintf(
            '<tr>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td>%s</td>
                <td class="text-right">%s</td>
                <td>%s</td>
            </tr>',
            date('Y-m-d', strtotime($row['transaction_date'])),
            htmlspecialchars($row['type']),
            htmlspecialchars($row['name']),
            htmlspecialchars($row['platform']),
            htmlspecialchars($row['account_owner']),
            number_format($amount, 2),
            htmlspecialchars($row['comment'])
        );
    }

    // Add totals
    $html .= sprintf('
        <tr class="info">
            <td colspan="5" class="text-right"><strong>Total Deposit</strong></td>
            <td class="text-right"><strong>%s</strong></td>
            <td></td>
        </tr>
        <tr class="info">
            <td colspan="5" class="text-right"><strong>Total Withdraw</strong></td>
            <td class="text-right"><strong>%s</strong></td>
            <td></td>
        </tr>
        <tr class="info">
            <td colspan="5" class="text-right"><strong>Net Balance</strong></td>
            <td class="text-right"><strong>%s</strong></td>
            <td></td>
        </tr>',
        number_format($totalDeposit, 2),
        number_format($totalWithdraw, 2),
        number_format($totalDeposit - $totalWithdraw, 2)
    );

    $html .= '</tbody></table></div>';

    // CSS styles for the report
    $styles = '
        .table { width: 100%; margin-bottom: 1rem; background-color: transparent; }
        .table th, .table td { padding: 0.75rem; vertical-align: top; border-top: 1px solid #dee2e6; }
        .table thead th { vertical-align: bottom; border-bottom: 2px solid #dee2e6; }
        .table tbody + tbody { border-top: 2px solid #dee2e6; }
        .table-bordered { border: 1px solid #dee2e6; }
        .table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
        .table-bordered thead th, .table-bordered thead td { border-bottom-width: 2px; }
        .table-hover tbody tr:hover { background-color: rgba(0, 0, 0, 0.075); }
        .text-right { text-align: right !important; }
        .info { background-color: #d9edf7; }
        .table-responsive { display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    ';

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'html' => $html,
        'styles' => $styles
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'messages' => "Error generating report: " . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($connect)) {
        $connect->close();
    }
}