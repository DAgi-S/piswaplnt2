<?php
namespace Tests;

class TestClient {
    private $baseUrl = 'http://localhost/pistocklntmarch';
    private $cookies = [];
    
    public function get($path, $headers = []) {
        return $this->request('GET', $path, null, $headers);
    }
    
    public function post($path, $data = [], $headers = [], $cookies = []) {
        return $this->request('POST', $path, $data, $headers, $cookies);
    }
    
    public function put($path, $data = [], $headers = []) {
        return $this->request('PUT', $path, $data, $headers);
    }
    
    public function delete($path, $headers = []) {
        return $this->request('DELETE', $path, null, $headers);
    }
    
    public function options($path, $data = [], $headers = []) {
        return $this->request('OPTIONS', $path, $data, $headers);
    }
    
    private function request($method, $path, $data = null, $headers = [], $cookies = []) {
        $context = [
            'http' => [
                'method' => $method,
                'header' => $this->buildHeaders($headers, $cookies),
                'ignore_errors' => true
            ]
        ];
        
        if ($data !== null) {
            $context['http']['content'] = http_build_query($data);
            if (!isset($headers['Content-Type'])) {
                $context['http']['header'] .= "Content-Type: application/x-www-form-urlencoded\r\n";
            }
        }
        
        $url = $this->baseUrl . $path;
        $response = file_get_contents($url, false, stream_context_create($context));
        
        return new TestResponse(
            $response,
            $http_response_header ?? [],
            $this->parseCookies($http_response_header ?? [])
        );
    }
    
    private function buildHeaders($headers, $cookies) {
        $headerString = '';
        foreach ($headers as $name => $value) {
            $headerString .= "$name: $value\r\n";
        }
        
        // Add cookies to headers
        $cookieString = '';
        foreach ($cookies as $name => $value) {
            $cookieString .= "$name=$value; ";
        }
        if ($cookieString) {
            $headerString .= "Cookie: " . rtrim($cookieString, '; ') . "\r\n";
        }
        
        return $headerString;
    }
    
    private function parseCookies($headers) {
        $cookies = [];
        foreach ($headers as $header) {
            if (strpos($header, 'Set-Cookie:') === 0) {
                $cookie = $this->parseCookie(substr($header, 12));
                if ($cookie) {
                    $cookies[$cookie['name']] = $cookie;
                }
            }
        }
        return $cookies;
    }
    
    private function parseCookie($cookieString) {
        $parts = explode(';', $cookieString);
        if (empty($parts)) return null;
        
        $nameValue = explode('=', trim($parts[0]));
        if (count($nameValue) !== 2) return null;
        
        $cookie = [
            'name' => $nameValue[0],
            'value' => $nameValue[1],
            'secure' => false,
            'httponly' => false,
            'samesite' => null
        ];
        
        for ($i = 1; $i < count($parts); $i++) {
            $part = trim($parts[$i]);
            if (strcasecmp($part, 'secure') === 0) {
                $cookie['secure'] = true;
            } elseif (strcasecmp($part, 'httponly') === 0) {
                $cookie['httponly'] = true;
            } elseif (stripos($part, 'samesite=') === 0) {
                $cookie['samesite'] = substr($part, 9);
            }
        }
        
        return $cookie;
    }
} 