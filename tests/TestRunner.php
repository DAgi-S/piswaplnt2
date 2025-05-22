<?php
/**
 * Custom Test Runner
 * Handles output buffering and endpoint execution
 */

namespace Tests;

class TestRunner {
    private static $instance = null;
    private $outputBuffer = '';
    private $headers = [];
    private $cookies = [];
    private $responseCode = 200;

    private function __construct() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function runEndpoint($path) {
        // Clear previous state
        $this->outputBuffer = '';
        $this->headers = [];
        $this->cookies = [];
        $this->responseCode = 200;

        // Override header functions
        $this->overrideHeaderFunctions();

        // Capture output
        ob_start();
        
        try {
            // Include the endpoint file
            include __DIR__ . '/../' . $path;
            $this->outputBuffer = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return $this->outputBuffer;
    }

    private function overrideHeaderFunctions() {
        global $testRunner;
        $testRunner = $this;

        if (!function_exists('header')) {
            function header($string, $replace = true, $http_response_code = null) {
                global $testRunner;
                return $testRunner->handleHeader($string, $replace, $http_response_code);
            }
        }

        if (!function_exists('header_remove')) {
            function header_remove($name = null) {
                global $testRunner;
                return $testRunner->handleHeaderRemove($name);
            }
        }

        if (!function_exists('headers_sent')) {
            function headers_sent(&$file = null, &$line = null) {
                return false;
            }
        }

        if (!function_exists('headers_list')) {
            function headers_list() {
                global $testRunner;
                return $testRunner->getHeadersList();
            }
        }

        if (!function_exists('http_response_code')) {
            function http_response_code($response_code = null) {
                global $testRunner;
                return $testRunner->handleResponseCode($response_code);
            }
        }

        if (!function_exists('setcookie')) {
            function setcookie($name, $value = "", $options = []) {
                global $testRunner;
                return $testRunner->handleSetCookie($name, $value, $options);
            }
        }
    }

    public function handleHeader($string, $replace = true, $http_response_code = null) {
        if (preg_match('/^HTTP\/[\d.]+\s+(\d+)/', $string, $matches)) {
            $this->responseCode = (int)$matches[1];
            return true;
        }

        $parts = explode(':', $string, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            
            if ($replace || !isset($this->headers[$name])) {
                $this->headers[$name] = $value;
            }
        }
        return true;
    }

    public function handleHeaderRemove($name = null) {
        if ($name === null) {
            $this->headers = [];
        } else {
            unset($this->headers[$name]);
        }
        return true;
    }

    public function getHeadersList() {
        $headers = [];
        foreach ($this->headers as $name => $value) {
            $headers[] = "$name: $value";
        }
        return $headers;
    }

    public function handleResponseCode($response_code = null) {
        if ($response_code !== null) {
            $this->responseCode = $response_code;
        }
        return $this->responseCode;
    }

    public function handleSetCookie($name, $value = "", $options = []) {
        if (!is_array($options)) {
            $options = [
                'expires' => $options,
                'path' => func_get_args()[3] ?? '',
                'domain' => func_get_args()[4] ?? '',
                'secure' => func_get_args()[5] ?? false,
                'httponly' => func_get_args()[6] ?? false
            ];
        }

        if ($value === '') {
            unset($this->cookies[$name]);
            unset($_COOKIE[$name]);
        } else {
            $this->cookies[$name] = array_merge([
                'value' => $value,
                'expires' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ], $options);
            $_COOKIE[$name] = $value;
        }
        
        return true;
    }

    public function getHeader($name) {
        return $this->headers[$name] ?? null;
    }

    public function getCookie($name) {
        return $this->cookies[$name] ?? null;
    }

    public function getResponseCode() {
        return $this->responseCode;
    }

    public function getOutputBuffer() {
        return $this->outputBuffer;
    }
} 