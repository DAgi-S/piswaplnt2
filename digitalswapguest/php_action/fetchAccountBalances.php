<?php
session_start();
require_once '../includes/db_connect.php';
require_once 'utils.php';

header('Content-Type: application/json');

if (!isset($_SESSION['guest_id']) || !isset($_SESSION['active_guest_account'])) {
    echo json_encode(['error' => true, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // First get the active account details
    $activeAccountSql = "SELECT a.Currency, gu.balance, a.account_owner, a.account_platform
                        FROM guest_users gu
                        JOIN guest_account_links gal ON gu.guest_id = gal.guest_id
                        JOIN accounts a ON gal.account_id = a.id
                        WHERE gu.guest_id = ? 
                        AND gal.account_id = ?
                        AND a.satstus = 1
                        AND gu.status = 1";

    $stmt = $connect->prepare($activeAccountSql);
    $stmt->bind_param("si", $_SESSION['guest_id'], $_SESSION['active_guest_account']);
    $stmt->execute();
    $activeResult = $stmt->get_result();
    $activeAccount = $activeResult->fetch_assoc();
    $stmt->close();

    if (!$activeAccount) {
        throw new Exception("Active account not found");
    }

    // Get all linked accounts
    $sql = "SELECT a.id, a.account_owner, a.account_platform, a.Currency,
            (SELECT balance FROM guest_users WHERE guest_id = ? AND linked_account_id = a.id) as balance
            FROM accounts a
            JOIN guest_account_links gal ON a.id = gal.account_id
            WHERE gal.guest_id = ?
            AND a.satstus = 1";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ss", $_SESSION['guest_id'], $_SESSION['guest_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    $accounts = [];
    while ($row = $result->fetch_assoc()) {
        $balance = floatval($row['balance'] ?? 0);
        $accounts[] = [
            'account_owner' => htmlspecialchars($row['account_owner']),
            'account_platform' => htmlspecialchars($row['account_platform']),
            'currency' => htmlspecialchars($row['Currency']),
            'balance' => number_format($balance, 2, '.', ','),
            'formatted_balance' => $row['Currency'] . ' ' . number_format($balance, 2, '.', ',')
        ];
    }

    // Format active account balance
    $activeBalance = floatval($activeAccount['balance'] ?? 0);
    $formattedActiveBalance = $activeAccount['Currency'] . ' ' . number_format($activeBalance, 2, '.', ',');

    echo json_encode([
        'error' => false,
        'accounts' => $accounts,
        'totalBalance' => $formattedActiveBalance,
        'currency' => $activeAccount['Currency'],
        'activeAccount' => [
            'owner' => $activeAccount['account_owner'],
            'platform' => $activeAccount['account_platform'],
            'balance' => $formattedActiveBalance
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in fetchAccountBalances.php: " . $e->getMessage());
    echo json_encode([
        'error' => true,
        'message' => 'Failed to fetch account balances',
        'accounts' => [],
        'totalBalance' => '0.00'
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($connect)) {
        $connect->close();
    }
} 