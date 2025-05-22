<?php
require_once '../includes/core.php';
require_once '../includes/db_connect.php';

// Add error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging
error_log('Export started');
error_log('Session guest_id: ' . ($_SESSION['guest_id'] ?? 'not set'));
error_log('Session active_guest_account: ' . ($_SESSION['active_guest_account'] ?? 'not set'));

// Check if PhpSpreadsheet is installed
if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    // If not installed, try to include it from vendor directory
    if (file_exists('../vendor/autoload.php')) {
        require_once '../vendor/autoload.php';
    } else {
        die("PhpSpreadsheet is not installed. Please run 'composer require phpoffice/phpspreadsheet' in the project root.");
    }
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Check if user is logged in and has access
if (!isset($_SESSION['guest_id'])) {
    http_response_code(401);
    die('Unauthorized access');
}

try {
    // Debug logging
    error_log('Attempting to fetch guest user data');
    
    // Get current user data first
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        throw new Exception('Unable to retrieve user data. Please try logging in again.');
    }
    
    // Get guest user's data with proper joins
    $sql = "SELECT DISTINCT g.*, a.account_platform, a.Currency 
            FROM guest_users g 
            INNER JOIN guest_account_links gal ON g.id = gal.guest_id
            INNER JOIN accounts a ON gal.account_id = a.id 
            WHERE g.id = ? AND a.id = ? AND a.status = 1";
    
    error_log('SQL Query: ' . $sql);
    error_log('Parameters - guest_id: ' . $currentUser['id'] . ', active_account: ' . $_SESSION['active_guest_account']);
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ii", $currentUser['id'], $_SESSION['active_guest_account']);
    $stmt->execute();
    $result = $stmt->get_result();
    $guestData = $result->fetch_assoc();

    error_log('Query result: ' . ($guestData ? 'Data found' : 'No data found'));
    if ($guestData) {
        error_log('Guest data: ' . print_r($guestData, true));
    }

    if (!$guestData) {
        throw new Exception('User data not found. Please ensure you are logged in and have selected an account.');
    }

    // Build query conditions
    $conditions = array();
    $params = array();
    $types = "";

    // Use the active account from session
    if (isset($_SESSION['active_guest_account'])) {
        $conditions[] = "t.account_id = ?";
        $params[] = $_SESSION['active_guest_account'];
        $types .= "i";
    } else {
        throw new Exception('No active account selected. Please select an account first.');
    }

    if (!empty($_GET['startDate'])) {
        $conditions[] = "DATE(t.transaction_date) >= ?";
        $params[] = $_GET['startDate'];
        $types .= "s";
    }

    if (!empty($_GET['endDate'])) {
        $conditions[] = "DATE(t.transaction_date) <= ?";
        $params[] = $_GET['endDate'];
        $types .= "s";
    }

    if (!empty($_GET['type'])) {
        $conditions[] = "t.type = ?";
        $params[] = $_GET['type'];
        $types .= "s";
    }

    // Get transactions
    $sql = "SELECT t.* 
            FROM digitalswap t 
            WHERE " . implode(" AND ", $conditions) . "
            ORDER BY t.transaction_date ASC";

    $stmt = $connect->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Create new spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $sheet->setCellValue('A1', 'Transaction History');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

    $sheet->setCellValue('A3', 'Account Platform:');
    $sheet->setCellValue('B3', $guestData['account_platform']);
    $sheet->setCellValue('A4', 'Currency:');
    $sheet->setCellValue('B4', $guestData['Currency']);
    $sheet->setCellValue('A5', 'Generated Date:');
    $sheet->setCellValue('B5', date('Y-m-d H:i:s'));

    // Set column headers
    $sheet->setCellValue('A7', 'Date');
    $sheet->setCellValue('B7', 'ID');
    $sheet->setCellValue('C7', 'Name');
    $sheet->setCellValue('D7', 'Type');
    $sheet->setCellValue('E7', 'Platform');
    $sheet->setCellValue('F7', 'Amount');
    $sheet->setCellValue('G7', 'Status');
    $sheet->setCellValue('H7', 'Comment');

    $sheet->getStyle('A7:H7')->getFont()->setBold(true);

    // Add data
    $row = 8;
    while ($transaction = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $row, date('Y-m-d H:i', strtotime($transaction['transaction_date'])));
        $sheet->setCellValue('B' . $row, $transaction['id']);
        $sheet->setCellValue('C' . $row, $transaction['name']);
        $sheet->setCellValue('D' . $row, ucfirst($transaction['type']));
        $sheet->setCellValue('E' . $row, $transaction['platform']);
        $sheet->setCellValue('F' . $row, number_format($transaction['amount'], 2));
        
        $status = 'N/A';
        if ($transaction['status'] == 1 || $transaction['status'] === 'Completed') {
            $status = 'Completed';
        } elseif ($transaction['status'] == 0 || $transaction['status'] === 'Pending') {
            $status = 'Pending';
        } elseif ($transaction['status'] == 2 || $transaction['status'] === 'Failed') {
            $status = 'Failed';
        }
        $sheet->setCellValue('G' . $row, $status);
        $sheet->setCellValue('H' . $row, $transaction['comment']);
        
        $row++;
    }

    // Auto size columns
    foreach(range('A','H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Clean output buffer
    if (ob_get_length()) ob_end_clean();

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="transaction_history_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    // Save file
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();

} catch (Exception $e) {
    // Log error
    error_log('Export error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    die('Error exporting transactions: ' . $e->getMessage());
}