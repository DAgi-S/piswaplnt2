<?php
require_once 'core.php';
require_once 'classes/ConfigurationManager.php';

// Check permissions
if (!isset($_SESSION['userId']) || !isset($_SESSION['role_id'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

class ExtendedValidator {
    private $errors = [];
    private $testResults = [];

    public function validate($data) {
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'ip_address':
                    $this->validateIpAddress($value);
                    break;
                case 'url':
                    $this->validateUrl($value);
                    break;
                case 'path':
                    $this->validatePath($value);
                    break;
                case 'json':
                    $this->validateJson($value);
                    break;
                case 'datetime':
                    $this->validateDateTime($value);
                    break;
            }
        }

        return [
            'success' => empty($this->errors),
            'errors' => $this->errors,
            'test_results' => $this->testResults
        ];
    }

    private function validateIpAddress($value) {
        if (empty($value)) {
            $this->errors[] = 'IP address is required';
            return;
        }

        if (filter_var($value, FILTER_VALIDATE_IP) === false) {
            $this->errors[] = 'Invalid IP address format';
        } else {
            $this->testResults[] = 'IP address validation successful';
        }
    }

    private function validateUrl($value) {
        if (empty($value)) {
            $this->errors[] = 'URL is required';
            return;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            $this->errors[] = 'Invalid URL format';
        } else {
            // Additional URL checks
            $parsed = parse_url($value);
            if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
                $this->errors[] = 'URL must use http or https protocol';
            } else {
                $this->testResults[] = 'URL validation successful';
            }
        }
    }

    private function validatePath($value) {
        if (empty($value)) {
            $this->errors[] = 'Path is required';
            return;
        }

        // Check for directory traversal attempts
        if (strpos($value, '..') !== false || strpos($value, '//') !== false) {
            $this->errors[] = 'Invalid path: directory traversal detected';
            return;
        }

        // Check for unsafe characters
        if (preg_match('/[<>:"|?*]/', $value)) {
            $this->errors[] = 'Invalid path: contains unsafe characters';
            return;
        }

        // Check if path exists and is writable
        if (!file_exists($value)) {
            if (!@mkdir($value, 0750, true)) {
                $this->errors[] = 'Path does not exist and cannot be created';
                return;
            }
            $this->testResults[] = 'Path created successfully';
        }

        if (!is_writable($value)) {
            $this->errors[] = 'Path is not writable';
        } else {
            $this->testResults[] = 'Path validation successful';
        }
    }

    private function validateJson($value) {
        if (empty($value)) {
            $this->errors[] = 'JSON data is required';
            return;
        }

        // Try to decode JSON
        $decoded = json_decode($value);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errors[] = 'Invalid JSON format: ' . json_last_error_msg();
            return;
        }

        // Validate JSON structure
        if (!is_array($decoded) && !is_object($decoded)) {
            $this->errors[] = 'JSON must be an array or object';
            return;
        }

        $this->testResults[] = 'JSON validation successful';
    }

    private function validateDateTime($value) {
        if (empty($value)) {
            $this->errors[] = 'Date/time is required';
            return;
        }

        // Try different date formats
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d',
            'H:i:s',
            'Y-m-d\TH:i:sP', // ISO 8601
            'D, d M Y H:i:s O' // RFC 2822
        ];

        $valid = false;
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            $this->errors[] = 'Invalid date/time format';
        } else {
            $this->testResults[] = 'Date/time validation successful';
        }
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = new ExtendedValidator();
    $result = $validator->validate($_POST);
    
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 