<?php
namespace Tests;

class TestResponse {
    private $body;
    private $headers;
    private $cookies;
    private $statusCode;
    
    public function __construct($body, $headers, $cookies) {
        $this->body = $body;
        $this->headers = $headers;
        $this->cookies = $cookies;
        $this->statusCode = $this->parseStatusCode($headers);
    }
    
    public function getStatusCode() {
        return $this->statusCode;
    }
    
    public function getBody() {
        return $this->body;
    }
    
    public function getJson() {
        return json_decode($this->body, true);
    }
    
    public function getHeader($name) {
        $name = strtolower($name);
        foreach ($this->headers as $header) {
            if (stripos($header, $name . ':') === 0) {
                return [trim(substr($header, strlen($name) + 1))];
            }
        }
        return null;
    }
    
    public function getCookies() {
        return $this->cookies;
    }
    
    private function parseStatusCode($headers) {
        if (empty($headers)) return 200;
        
        $firstHeader = $headers[0];
        if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $firstHeader, $matches)) {
            return (int) $matches[1];
        }
        
        return 200;
    }
} 