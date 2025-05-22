<?php
class ErrorReporter {
    private $logFile;
    private $errorTypes = [
        'database' => 'Database Error',
        'validation' => 'Validation Error',
        'auth' => 'Authentication Error',
        'file' => 'File Operation Error',
        'json' => 'JSON Processing Error',
        'report' => 'Report Generation Error'
    ];

    public function __construct() {
        $this->logFile = dirname(__FILE__) . '/../../logs/error.log';
        $this->ensureLogDirectory();
    }

    private function ensureLogDirectory() {
        $logDir = dirname($this->logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }

    public function logError($type, $message, $context = []) {
        $errorType = isset($this->errorTypes[$type]) ? $this->errorTypes[$type] : 'General Error';
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : 'No additional context';
        
        $logMessage = sprintf(
            "[%s] %s: %s\nContext: %s\n",
            $timestamp,
            $errorType,
            $message,
            $contextStr
        );

        error_log($logMessage, 3, $this->logFile);
        return $this->formatErrorResponse($type, $message);
    }

    public function formatErrorResponse($type, $message) {
        $response = [
            'status' => false,
            'error' => [
                'type' => $type,
                'message' => $message
            ]
        ];

        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            $response['error']['debug_backtrace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        return $response;
    }

    public function getLastError() {
        if (!file_exists($this->logFile)) {
            return null;
        }
        
        $lines = file($this->logFile);
        return $lines ? end($lines) : null;
    }

    public function clearLog() {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function getLogContents($limit = 100) {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $lines = file($this->logFile);
        return array_slice($lines, -$limit);
    }
} 