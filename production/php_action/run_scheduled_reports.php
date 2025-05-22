<?php
/**
 * Automated Report Generation Script
 * This script is designed to be run by a system scheduler (cron job)
 * Recommended schedule: Every hour
 * 
 * Example cron entry:
 * 0 * * * * /usr/bin/php /path/to/production/php_action/run_scheduled_reports.php
 */

// Set unlimited execution time for large reports
set_time_limit(0);

// Load required files
require_once 'core.php';
require_once 'automated_reports.php';

try {
    // Initialize automated reports
    $reports = new AutomatedReports($connect);

    // Check if there are any reports scheduled for the current hour
    $currentHour = date('H:00:00');
    
    $sql = "SELECT COUNT(*) as scheduled_count 
            FROM scheduled_reports 
            WHERE is_active = 1 
            AND schedule_time = ?
            AND (
                (frequency = 'daily')
                OR (frequency = 'weekly' AND DAYOFWEEK(NOW()) = schedule_day)
                OR (frequency = 'monthly' AND DAYOFMONTH(NOW()) = schedule_day)
            )";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param('s', $currentHour);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result['scheduled_count'] > 0) {
        // Generate scheduled reports
        $reports->generateScheduledReports();
        
        // Log successful execution
        error_log(sprintf(
            "[%s] Successfully generated %d scheduled reports\n",
            date('Y-m-d H:i:s'),
            $result['scheduled_count']
        ));
    }

} catch (Exception $e) {
    // Log error
    error_log(sprintf(
        "[%s] Error generating scheduled reports: %s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage()
    ));
    
    // Send error notification to system administrator
    mail(
        'admin@company.com',
        'Error: Automated Reports Generation Failed',
        "Error generating scheduled reports:\n\n" . $e->getMessage(),
        'From: production-system@company.com'
    );
}

// Close database connection
$connect->close(); 