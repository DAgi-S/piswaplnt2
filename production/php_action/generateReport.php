<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set proper content type for JSON response
header('Content-Type: application/json; charset=utf-8');

require_once 'core.php';
require_once 'classes/SimpleReportManager.php';
require_once 'classes/ErrorReporter.php';

// Initialize error reporter
$errorReporter = new ErrorReporter();

try {
    // Check if user is logged in
    if (!isset($_SESSION['userId'])) {
        echo json_encode([
            'status' => false,
            'message' => 'Unauthorized access'
        ]);
        exit();
    }

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'status' => false,
            'message' => 'Invalid request method'
        ]);
        exit();
    }

    // Get and validate parameters
    $reportType = isset($_POST['report_type']) ? $_POST['report_type'] : '';
    $format = isset($_POST['format']) ? $_POST['format'] : 'html';
    $filters = isset($_POST['filters']) ? json_decode($_POST['filters'], true) : [];

    if (empty($reportType)) {
        throw new Exception('Report type is required');
    }

    // Initialize report manager
    $reportManager = new SimpleReportManager();

    // Generate report
    $result = $reportManager->generateReport($reportType, $filters);

    // Handle different output formats
    if ($result['status']) {
        switch ($format) {
            case 'html':
                // Return JSON response for HTML format
                echo json_encode($result);
                break;

            case 'pdf':
                // Set headers for PDF download
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $reportType . '_report.pdf"');
                echo $result['data'];
                break;

            case 'excel':
                // Set headers for Excel download
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="' . $reportType . '_report.xlsx"');
                echo $result['data'];
                break;

            default:
                throw new Exception('Unsupported format: ' . $format);
        }
    } else {
        echo json_encode($result);
    }
} catch (Exception $e) {
    error_log("Error in generateReport.php: " . $e->getMessage());
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
}