<?php
require_once 'core.php';
require_once 'classes/ReportManager.php';

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

try {
    // Get and validate input parameters
    $schedule = [
        'report_type' => isset($_POST['report_type']) ? $_POST['report_type'] : '',
        'frequency' => isset($_POST['frequency']) ? $_POST['frequency'] : '',
        'format' => isset($_POST['format']) ? $_POST['format'] : '',
        'email_recipients' => isset($_POST['email_recipients']) ? $_POST['email_recipients'] : '',
        'filters' => isset($_POST['filters']) ? $_POST['filters'] : []
    ];

    // Validate required fields
    $requiredFields = ['report_type', 'frequency', 'format', 'email_recipients'];
    foreach ($requiredFields as $field) {
        if (empty($schedule[$field])) {
            throw new Exception("Field '{$field}' is required");
        }
    }

    // Validate email recipients
    $emails = array_map('trim', explode(',', $schedule['email_recipients']));
    foreach ($emails as $email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address: {$email}");
        }
    }

    // Convert filters from string to array if needed
    if (is_string($schedule['filters'])) {
        $schedule['filters'] = json_decode($schedule['filters'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid filters format');
        }
    }

    // Add user ID to schedule
    $schedule['created_by'] = $_SESSION['userId'];

    // Initialize report manager
    $reportManager = new ReportManager();

    // Schedule report
    $result = $reportManager->scheduleReport($schedule);

    if ($result['status']) {
        echo json_encode([
            'status' => true,
            'message' => 'Report scheduled successfully',
            'data' => [
                'next_run' => $result['next_run']
            ]
        ]);
    } else {
        throw new Exception($result['message']);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
} 