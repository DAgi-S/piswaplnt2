<?php
class ErrorHandler {
    private $logFile;
    private $maxLogSize = 10485760; // 10MB
    private $logRotateCount = 5;

    public function __construct() {
        $this->logFile = dirname(__FILE__) . '/../../logs/error.log';
        $this->initializeLogSystem();
    }

    private function initializeLogSystem() {
        try {
            // Create logs directory if it doesn't exist
            $logsDir = dirname($this->logFile);
            if (!file_exists($logsDir)) {
                if (!@mkdir($logsDir, 0755, true)) {
                    throw new Exception("Failed to create logs directory: " . $logsDir);
                }
            }

            // Check directory permissions
            if (!is_writable($logsDir)) {
                throw new Exception("Logs directory is not writable: " . $logsDir);
            }

            // Create log file if it doesn't exist
            if (!file_exists($this->logFile)) {
                if (!@touch($this->logFile)) {
                    throw new Exception("Failed to create log file: " . $this->logFile);
                }
                chmod($this->logFile, 0644);
            }

            // Check log file permissions
            if (!is_writable($this->logFile)) {
                throw new Exception("Log file is not writable: " . $this->logFile);
            }

            // Rotate logs if necessary
            $this->rotateLogIfNeeded();
        } catch (Exception $e) {
            // If we can't write to the log file, write to system error log
            error_log("Error initializing log system: " . $e->getMessage());
        }
    }

    private function rotateLogIfNeeded() {
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxLogSize) {
            for ($i = $this->logRotateCount; $i > 0; $i--) {
                $oldFile = $this->logFile . '.' . $i;
                $newFile = $this->logFile . '.' . ($i + 1);
                if (file_exists($oldFile)) {
                    rename($oldFile, $newFile);
                }
            }
            rename($this->logFile, $this->logFile . '.1');
            touch($this->logFile);
            chmod($this->logFile, 0644);
        }
    }

    public function handleError($error) {
        try {
            $timestamp = date('Y-m-d H:i:s');
            $errorMessage = '';

            if ($error instanceof Exception) {
                $errorMessage = sprintf(
                    "[%s] %s in %s on line %d\nStack trace:\n%s\n",
                    $timestamp,
                    $error->getMessage(),
                    $error->getFile(),
                    $error->getLine(),
                    $error->getTraceAsString()
                );
            } else {
                $errorMessage = sprintf(
                    "[%s] %s\n",
                    $timestamp,
                    $error
                );
            }

            // Try to write to log file
            if (@file_put_contents($this->logFile, $errorMessage, FILE_APPEND | LOCK_EX) === false) {
                // If writing to log file fails, try to write to system error log
                error_log("Failed to write to log file. Original error: " . $errorMessage);
            }

            // If in development environment, you might want to echo the error
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                echo $errorMessage;
            }
        } catch (Exception $e) {
            // Last resort: write to system error log
            error_log("Critical error in error handler: " . $e->getMessage());
            error_log("Original error: " . print_r($error, true));
        }
    }

    public function getLogFile() {
        return $this->logFile;
    }

    public function clearLog() {
        try {
            if (file_exists($this->logFile)) {
                if (!@unlink($this->logFile)) {
                    throw new Exception("Failed to delete log file: " . $this->logFile);
                }
                touch($this->logFile);
                chmod($this->logFile, 0644);
            }
        } catch (Exception $e) {
            error_log("Failed to clear log: " . $e->getMessage());
            throw $e;
        }
    }

    public function getLastErrors($lines = 100) {
        try {
            if (!file_exists($this->logFile)) {
                return [];
            }

            $file = new SplFileObject($this->logFile, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();

            $errors = [];
            $start = max(0, $totalLines - $lines);

            $file->seek($start);
            while (!$file->eof()) {
                $errors[] = $file->fgets();
            }

            return $errors;
        } catch (Exception $e) {
            error_log("Failed to read log file: " . $e->getMessage());
            return [];
        }
    }
} 