<?php
/**
 * Security Audit Report Generator
 * Generates a comprehensive security audit report in HTML format
 */

require_once __DIR__ . '/../vendor/autoload.php';

class SecurityReportGenerator {
    private $reportFile;
    private $findings = [];
    private $recommendations = [];
    private $criticalIssues = 0;
    private $warnings = 0;

    public function __construct() {
        $this->reportFile = __DIR__ . '/../reports/security/audit-' . date('Y-m-d-His') . '.html';
    }

    public function run() {
        echo "Generating Security Audit Report...\n\n";

        $this->checkJWTImplementation()
             ->checkSecurityHeaders()
             ->checkAuthenticationFlow()
             ->checkWebSocketSecurity()
             ->checkDataProtection()
             ->generateReport();

        echo "\nReport generated: {$this->reportFile}\n";
        echo "Critical Issues: {$this->criticalIssues}\n";
        echo "Warnings: {$this->warnings}\n";
    }

    private function checkJWTImplementation() {
        echo "Checking JWT Implementation...\n";

        // Check JWT configuration
        if (!defined('JWT_SECRET_KEY') || empty(JWT_SECRET_KEY)) {
            $this->addCriticalFinding(
                'JWT Configuration',
                'JWT secret key is not properly configured',
                'Configure JWT_SECRET_KEY with a strong, unique key'
            );
        }

        // Check token expiration
        if (defined('JWT_ACCESS_TOKEN_EXPIRY') && JWT_ACCESS_TOKEN_EXPIRY > 3600) {
            $this->addWarning(
                'JWT Configuration',
                'Access token expiry time is longer than recommended',
                'Reduce JWT_ACCESS_TOKEN_EXPIRY to 1 hour or less'
            );
        }

        return $this;
    }

    private function checkSecurityHeaders() {
        echo "Checking Security Headers...\n";

        $requiredHeaders = [
            'Strict-Transport-Security',
            'X-Frame-Options',
            'X-Content-Type-Options',
            'X-XSS-Protection',
            'Content-Security-Policy'
        ];

        foreach ($requiredHeaders as $header) {
            $this->addRecommendation(
                'Security Headers',
                "Ensure {$header} header is properly configured",
                "Add appropriate value for {$header} header in all responses"
            );
        }

        return $this;
    }

    private function checkAuthenticationFlow() {
        echo "Checking Authentication Flow...\n";

        // Check CSRF protection
        if (!defined('JWT_CSRF_ENABLED') || !JWT_CSRF_ENABLED) {
            $this->addCriticalFinding(
                'Authentication Security',
                'CSRF protection is disabled',
                'Enable CSRF protection by setting JWT_CSRF_ENABLED to true'
            );
        }

        // Check rate limiting
        if (!defined('JWT_RATE_LIMIT_ATTEMPTS') || JWT_RATE_LIMIT_ATTEMPTS > 10) {
            $this->addWarning(
                'Authentication Security',
                'Rate limiting threshold is too high',
                'Reduce JWT_RATE_LIMIT_ATTEMPTS to 5-10 attempts'
            );
        }

        return $this;
    }

    private function checkWebSocketSecurity() {
        echo "Checking WebSocket Security...\n";

        // Check WSS
        if (!defined('WS_SECURE') || !WS_SECURE) {
            $this->addCriticalFinding(
                'WebSocket Security',
                'WebSocket connections are not secure',
                'Enable WSS by setting WS_SECURE to true'
            );
        }

        return $this;
    }

    private function checkDataProtection() {
        echo "Checking Data Protection...\n";

        // Check cookie security
        if (!defined('JWT_REFRESH_COOKIE_SECURE') || !JWT_REFRESH_COOKIE_SECURE) {
            $this->addCriticalFinding(
                'Data Protection',
                'Refresh token cookies are not secure',
                'Enable secure cookies by setting JWT_REFRESH_COOKIE_SECURE to true'
            );
        }

        if (!defined('JWT_REFRESH_COOKIE_HTTPONLY') || !JWT_REFRESH_COOKIE_HTTPONLY) {
            $this->addCriticalFinding(
                'Data Protection',
                'Refresh token cookies are not HTTP-only',
                'Enable HTTP-only cookies by setting JWT_REFRESH_COOKIE_HTTPONLY to true'
            );
        }

        return $this;
    }

    private function addCriticalFinding($category, $issue, $recommendation) {
        $this->findings[] = [
            'category' => $category,
            'severity' => 'critical',
            'issue' => $issue,
            'recommendation' => $recommendation
        ];
        $this->criticalIssues++;
    }

    private function addWarning($category, $issue, $recommendation) {
        $this->findings[] = [
            'category' => $category,
            'severity' => 'warning',
            'issue' => $issue,
            'recommendation' => $recommendation
        ];
        $this->warnings++;
    }

    private function addRecommendation($category, $title, $details) {
        $this->recommendations[] = [
            'category' => $category,
            'title' => $title,
            'details' => $details
        ];
    }

    private function generateReport() {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Audit Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        h1 { color: #2c3e50; }
        .critical { color: #e74c3c; }
        .warning { color: #f39c12; }
        .recommendation { color: #27ae60; }
        .finding { margin: 20px 0; padding: 15px; border-radius: 5px; }
        .critical-finding { background-color: #fadbd8; }
        .warning-finding { background-color: #fef9e7; }
        .recommendation-item { background-color: #eafaf1; }
    </style>
</head>
<body>
    <h1>Security Audit Report</h1>
    <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
    
    <h2>Summary</h2>
    <p>Critical Issues: <span class="critical">' . $this->criticalIssues . '</span></p>
    <p>Warnings: <span class="warning">' . $this->warnings . '</span></p>
    
    <h2>Findings</h2>';

        foreach ($this->findings as $finding) {
            $class = $finding['severity'] === 'critical' ? 'critical-finding' : 'warning-finding';
            $html .= '
    <div class="finding ' . $class . '">
        <h3>' . htmlspecialchars($finding['category']) . ' - <span class="' . $finding['severity'] . '">' . ucfirst($finding['severity']) . '</span></h3>
        <p><strong>Issue:</strong> ' . htmlspecialchars($finding['issue']) . '</p>
        <p><strong>Recommendation:</strong> ' . htmlspecialchars($finding['recommendation']) . '</p>
    </div>';
        }

        $html .= '
    <h2>Recommendations</h2>';

        foreach ($this->recommendations as $recommendation) {
            $html .= '
    <div class="finding recommendation-item">
        <h3>' . htmlspecialchars($recommendation['category']) . '</h3>
        <p><strong>' . htmlspecialchars($recommendation['title']) . '</strong></p>
        <p>' . htmlspecialchars($recommendation['details']) . '</p>
    </div>';
        }

        $html .= '
</body>
</html>';

        file_put_contents($this->reportFile, $html);
    }
}

// Run the report generator
$generator = new SecurityReportGenerator();
$generator->run(); 