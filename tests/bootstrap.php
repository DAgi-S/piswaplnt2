<?php
/**
 * Test Bootstrap File
 * Initializes the testing environment
 */

// Define that we are running tests
if (!defined('PHPUNIT_RUNNING')) {
    define('PHPUNIT_RUNNING', true);
}

// Suppress notices during testing
error_reporting(E_ALL & ~E_NOTICE);

// Define base path constant
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__DIR__));
}

// Include Composer's autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Store headers for testing
$GLOBALS['test_headers'] = [];
$GLOBALS['test_response_code'] = 200;
$GLOBALS['output_started'] = false;

// Mock header functions for testing
if (!function_exists('header')) {
    function header($string, $replace = true, $http_response_code = null) {
        if ($GLOBALS['output_started']) {
            return true;
        }

        if (preg_match('/^HTTP\/[\d.]+\s+(\d+)/', $string, $matches)) {
            $GLOBALS['test_response_code'] = (int)$matches[1];
            return true;
        }

        $parts = explode(':', $string, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            
            if ($replace || !isset($GLOBALS['test_headers'][$name])) {
                $GLOBALS['test_headers'][$name] = $value;
            }
        }
        return true;
    }
}

if (!function_exists('header_remove')) {
    function header_remove($name = null) {
        if ($GLOBALS['output_started']) {
            return true;
        }

        if ($name === null) {
            $GLOBALS['test_headers'] = [];
        } else {
            unset($GLOBALS['test_headers'][$name]);
        }
        return true;
    }
}

if (!function_exists('headers_sent')) {
    function headers_sent(&$file = null, &$line = null) {
        return $GLOBALS['output_started'];
    }
}

if (!function_exists('headers_list')) {
    function headers_list() {
        if ($GLOBALS['output_started']) {
            return [];
        }

        $headers = [];
        foreach ($GLOBALS['test_headers'] as $name => $value) {
            $headers[] = "$name: $value";
        }
        return $headers;
    }
}

if (!function_exists('http_response_code')) {
    function http_response_code($response_code = null) {
        if ($response_code !== null && !$GLOBALS['output_started']) {
            $GLOBALS['test_response_code'] = $response_code;
        }
        return $GLOBALS['test_response_code'];
    }
}

// Mock setcookie function
if (!function_exists('setcookie')) {
    function setcookie($name, $value = "", $options = []) {
        if ($GLOBALS['output_started']) {
            return true;
        }

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
            unset($_COOKIE[$name]);
        } else {
            $_COOKIE[$name] = $value;
        }
        
        return true;
    }
}

// Include JWT Handler and Security Config
require_once dirname(__DIR__) . '/includes/jwt/JWTHandler.php';
require_once dirname(__DIR__) . '/config/security.php';

// Define test constants if not already defined
if (!defined('JWT_SECRET_KEY')) {
    define('JWT_SECRET_KEY', 'test_secret_key');
}
if (!defined('JWT_REFRESH_KEY')) {
    define('JWT_REFRESH_KEY', 'test_refresh_key');
}
if (!defined('JWT_ACCESS_TOKEN_EXPIRY')) {
    define('JWT_ACCESS_TOKEN_EXPIRY', 900); // 15 minutes
}
if (!defined('JWT_REFRESH_TOKEN_EXPIRY')) {
    define('JWT_REFRESH_TOKEN_EXPIRY', 604800); // 7 days
}
if (!defined('JWT_ALGORITHM')) {
    define('JWT_ALGORITHM', 'HS256');
}
if (!defined('JWT_ISSUER')) {
    define('JWT_ISSUER', 'test_issuer');
}
if (!defined('JWT_AUDIENCE')) {
    define('JWT_AUDIENCE', 'test_audience');
}

// Define rate limit constants if not already defined
if (!defined('RATE_LIMIT_ATTEMPTS')) {
    define('RATE_LIMIT_ATTEMPTS', [
        'test' => 2,
        'api' => 60,
        'login' => 5
    ]);
}
if (!defined('RATE_LIMIT_WINDOWS')) {
    define('RATE_LIMIT_WINDOWS', [
        'test' => 60,
        'api' => 60,
        'login' => 300
    ]);
}

// Set up environment variables from phpunit.xml
foreach ($_ENV as $key => $value) {
    putenv("$key=$value");
}

// Mock Redis if extension is not available
if (!extension_loaded('redis')) {
    class Redis {
        private $data = [];
        private $auth = false;
        private $connected = false;
        
        public function connect($host, $port) {
            $this->connected = true;
            return true;
        }
        
        public function auth($password) {
            if (!$this->connected) return false;
            $this->auth = true;
            return true;
        }
        
        public function ping() {
            return $this->connected ? '+PONG' : false;
        }
        
        public function set($key, $value, $expiry = null) {
            if (!$this->connected) return false;
            $this->data[$key] = [
                'value' => $value,
                'expiry' => $expiry ? time() + $expiry : null
            ];
            return true;
        }
        
        public function setex($key, $expiry, $value) {
            return $this->set($key, $value, $expiry);
        }
        
        public function get($key) {
            if (!$this->connected) return false;
            if (isset($this->data[$key])) {
                if ($this->data[$key]['expiry'] === null || $this->data[$key]['expiry'] > time()) {
                    return $this->data[$key]['value'];
                }
                unset($this->data[$key]);
            }
            return false;
        }
        
        public function del($key) {
            if (!$this->connected) return false;
            unset($this->data[$key]);
            return true;
        }
        
        public function exists($key) {
            if (!$this->connected) return false;
            return isset($this->data[$key]);
        }
        
        public function incr($key) {
            if (!$this->connected) return false;
            if (!isset($this->data[$key])) {
                $this->data[$key] = ['value' => 1];
                return 1;
            }
            return ++$this->data[$key]['value'];
        }
        
        public function ttl($key) {
            if (!$this->connected || !isset($this->data[$key]) || !isset($this->data[$key]['expiry'])) {
                return -1;
            }
            $ttl = $this->data[$key]['expiry'] - time();
            return $ttl > 0 ? $ttl : -2;
        }
        
        public function expire($key, $seconds) {
            if (!$this->connected || !isset($this->data[$key])) {
                return false;
            }
            $this->data[$key]['expiry'] = time() + $seconds;
            return true;
        }
    }
}

// Mock functions and classes for testing
class_alias('TestDatabase', 'Database');
class_alias('TestRedis', 'Redis');

// Initialize test data
$GLOBALS['testUser'] = [
    'id' => 1,
    'email' => 'test@example.com',
    'password' => password_hash('password123', PASSWORD_DEFAULT),
    'role' => 'admin',
    'status' => 'active'
];

// Set up test database if using real database
if (getenv('USE_REAL_DB')) {
    try {
        $pdo = new PDO(
            sprintf(
                "mysql:host=%s;dbname=%s;charset=utf8mb4",
                getenv('DB_HOST'),
                getenv('DB_NAME')
            ),
            getenv('DB_USER'),
            getenv('DB_PASS'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        // Load schema
        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        $pdo->exec($schema);

        // Load triggers
        $triggers = file_get_contents(__DIR__ . '/database/triggers.sql');
        $pdo->exec($triggers);
    } catch (PDOException $e) {
        echo "Database connection failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Set up test environment
putenv('APP_ENV=testing');
putenv('DB_HOST=localhost');
putenv('DB_NAME=pistocklntmarch_test');
putenv('DB_USER=root');
putenv('DB_PASS=');

// Mock database connection for testing
class TestDatabase {
    private static $instance = null;
    private $mockData = [];

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this;
    }

    public function setMockData($data) {
        $this->mockData = $data;
    }

    public function prepare($query) {
        return new TestDatabaseStatement($this->mockData);
    }
}

class TestDatabaseStatement {
    private $mockData;
    private $params = [];

    public function __construct($mockData) {
        $this->mockData = $mockData;
    }

    public function bind_param($types, ...$params) {
        $this->params = $params;
    }

    public function execute() {
        return true;
    }

    public function get_result() {
        return new TestDatabaseResult($this->mockData);
    }
}

class TestDatabaseResult {
    private $mockData;
    private $position = 0;

    public function __construct($mockData) {
        $this->mockData = $mockData;
    }

    public function fetch_assoc() {
        if ($this->position >= count($this->mockData)) {
            return null;
        }
        return $this->mockData[$this->position++];
    }

    public function num_rows() {
        return count($this->mockData);
    }
} 