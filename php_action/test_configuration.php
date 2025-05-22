<?php
require_once 'core.php';
require_once 'classes/ConfigurationValidator.php';
require_once 'classes/ConfigurationManager.php';

// Check permissions
if (!isset($_SESSION['userId']) || !isset($_SESSION['role_id'])) {
    die(json_encode([
        'success' => false,
        'message' => 'Access denied. Please log in.'
    ]));
}

$validator = ConfigurationValidator::getInstance();
$config = ConfigurationManager::getInstance();

// Get the test type from request
$testType = $_POST['test_type'] ?? '';
$configData = $_POST['config'] ?? [];

$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

try {
    switch ($testType) {
        case 'email':
            $response['data'] = $validator->validateEmailConfig($configData);
            $response['success'] = true;
            break;

        case 'database':
            $response['data'] = $validator->validateDatabaseConfig($configData);
            $response['success'] = true;
            break;

        case 'backup':
            $response['data'] = $validator->validateBackupConfig($configData);
            $response['success'] = true;
            break;

        case 'test_email':
            // Test sending email
            if (empty($configData['test_email'])) {
                throw new Exception('Test email address is required');
            }

            $to = $configData['test_email'];
            $subject = 'Configuration Test Email';
            $message = 'This is a test email from your system configuration.';
            
            $headers = [
                'From' => $config->get('mail_from'),
                'Reply-To' => $config->get('mail_from'),
                'X-Mailer' => 'PHP/' . phpversion()
            ];

            if (mail($to, $subject, $message, $headers)) {
                $response['success'] = true;
                $response['message'] = 'Test email sent successfully';
            } else {
                throw new Exception('Failed to send test email');
            }
            break;

        case 'validate_path':
            if (empty($configData['path'])) {
                throw new Exception('Path is required');
            }
            $response['success'] = true;
            $response['data'] = [
                'valid' => $validator->validatePath($configData['path'])
            ];
            break;

        case 'validate_url':
            if (empty($configData['url'])) {
                throw new Exception('URL is required');
            }
            $response['success'] = true;
            $response['data'] = [
                'valid' => $validator->validateURL($configData['url'])
            ];
            break;

        case 'validate_ip':
            if (empty($configData['ip'])) {
                throw new Exception('IP address is required');
            }
            $response['success'] = true;
            $response['data'] = [
                'valid' => $validator->validateIP($configData['ip'])
            ];
            break;

        case 'validate_json':
            if (empty($configData['json'])) {
                throw new Exception('JSON string is required');
            }
            $requiredFields = $configData['required_fields'] ?? [];
            $response['success'] = true;
            $response['data'] = [
                'valid' => $validator->validateJSON($configData['json'], $requiredFields)
            ];
            break;

        case 'validate_datetime':
            if (empty($configData['datetime'])) {
                throw new Exception('Date/time string is required');
            }
            $format = $configData['format'] ?? 'Y-m-d H:i:s';
            $response['success'] = true;
            $response['data'] = [
                'valid' => $validator->validateDateTime($configData['datetime'], $format)
            ];
            break;

        default:
            throw new Exception('Invalid test type');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Send response
header('Content-Type: application/json');
echo json_encode($response); 