<?php
require_once __DIR__ . '/../php_action/core.php';
require_once __DIR__ . '/../php_action/classes/ReportManager.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $db = new Database();
    $reportManager = new ReportManager();

    // Get all scheduled reports that need to be generated
    $sql = "SELECT * FROM report_schedules 
            WHERE status = 'active' 
            AND next_run <= NOW()";
    
    $schedules = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($schedules as $schedule) {
        try {
            // Generate report
            $result = $reportManager->generateReport(
                $schedule['report_type'],
                json_decode($schedule['filters'], true),
                $schedule['format']
            );

            if ($result['status']) {
                // Get report file path
                $reportPath = $result['data']['file_path'];

                // Send email with report
                $mail = new PHPMailer(true);
                
                // Server settings
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USERNAME;
                $mail->Password = SMTP_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;

                // Recipients
                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $recipients = explode(',', $schedule['email_recipients']);
                foreach ($recipients as $recipient) {
                    $mail->addAddress(trim($recipient));
                }

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Scheduled Report: ' . ucwords(str_replace('_', ' ', $schedule['report_type']));
                $mail->Body = 'Please find attached the scheduled report for ' . date('Y-m-d') . '.';

                // Attachment
                if (file_exists($reportPath)) {
                    $mail->addAttachment($reportPath);
                }

                $mail->send();

                // Update schedule
                $nextRun = calculateNextRun($schedule['frequency']);
                $sql = "UPDATE report_schedules 
                        SET last_run = NOW(),
                            next_run = ?,
                            error_message = NULL 
                        WHERE id = ?";
                $db->query($sql, [$nextRun, $schedule['id']]);

                // Log success
                error_log(sprintf(
                    "[%s] Successfully generated and sent report %s (ID: %d)",
                    date('Y-m-d H:i:s'),
                    $schedule['report_type'],
                    $schedule['id']
                ));
            } else {
                throw new Exception($result['message']);
            }
        } catch (Exception $e) {
            // Update schedule with error
            $sql = "UPDATE report_schedules 
                    SET error_message = ?,
                        status = 'error'
                    WHERE id = ?";
            $db->query($sql, [$e->getMessage(), $schedule['id']]);

            // Log error
            error_log(sprintf(
                "[%s] Error generating report %s (ID: %d): %s",
                date('Y-m-d H:i:s'),
                $schedule['report_type'],
                $schedule['id'],
                $e->getMessage()
            ));
        }
    }
} catch (Exception $e) {
    error_log(sprintf(
        "[%s] Critical error in scheduled reports: %s",
        date('Y-m-d H:i:s'),
        $e->getMessage()
    ));
}

/**
 * Calculate next run time based on frequency
 * @param string $frequency Schedule frequency
 * @return string Next run datetime
 */
function calculateNextRun($frequency) {
    $now = new DateTime();
    
    switch ($frequency) {
        case 'daily':
            $now->modify('+1 day');
            $now->setTime(0, 0, 0);
            break;
        case 'weekly':
            $now->modify('next monday');
            $now->setTime(0, 0, 0);
            break;
        case 'monthly':
            $now->modify('first day of next month');
            $now->setTime(0, 0, 0);
            break;
    }

    return $now->format('Y-m-d H:i:s');
} 