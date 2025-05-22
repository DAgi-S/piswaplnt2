<?php
require_once __DIR__ . '/../config/mail_config.php';

class AdminMailer {
    private $mailer;

    public function __construct() {
        // Create PHPMailer instance
        $this->mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $this->mailer->SMTPDebug = 0;
            $this->mailer->isSMTP();
            $this->mailer->Host = MAIL_SERVER;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = MAIL_USERNAME;
            $this->mailer->Password = MAIL_PASSWORD;
            $this->mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $this->mailer->Port = MAIL_PORT;
            
            // Default sender
            $this->mailer->setFrom(MAIL_DEFAULT_SENDER, 'Notification System');
            
        } catch (Exception $e) {
            error_log("Error initializing mailer: " . $e->getMessage());
        }
    }

    public function sendFailureNotification($failedItems) {
        try {
            // Reset recipients
            $this->mailer->clearAddresses();
            
            // Add admin recipients
            foreach (ADMIN_EMAIL_RECIPIENTS as $recipient) {
                $this->mailer->addAddress($recipient);
            }

            // Set email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Notification Queue Failures Alert';
            
            // Build email body
            $body = $this->buildFailureEmailBody($failedItems);
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);

            // Send email
            return $this->mailer->send();

        } catch (Exception $e) {
            error_log("Error sending failure notification: " . $e->getMessage());
            return false;
        }
    }

    private function buildFailureEmailBody($failedItems) {
        $body = '<h2>Notification Queue Failures Alert</h2>';
        $body .= '<p>The following notifications have failed to be delivered:</p>';
        
        $body .= '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
        $body .= '<tr>
                    <th>Queue ID</th>
                    <th>Type</th>
                    <th>User</th>
                    <th>Channel</th>
                    <th>Attempts</th>
                    <th>Error</th>
                </tr>';

        foreach ($failedItems as $item) {
            $body .= "<tr>
                        <td>{$item['queue_id']}</td>
                        <td>{$item['type']}</td>
                        <td>{$item['user_name']}</td>
                        <td>{$item['channel']}</td>
                        <td>{$item['attempts']}</td>
                        <td>{$item['error_message']}</td>
                    </tr>";
        }

        $body .= '</table>';
        
        $body .= '<p>Please check the notification queue monitor for more details.</p>';
        $body .= '<p>Time: ' . date('Y-m-d H:i:s') . '</p>';

        return $body;
    }

    public function sendDailySummary($stats) {
        try {
            // Reset recipients
            $this->mailer->clearAddresses();
            
            // Add admin recipients
            foreach (ADMIN_EMAIL_RECIPIENTS as $recipient) {
                $this->mailer->addAddress($recipient);
            }

            // Set email content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Daily Notification Queue Summary';
            
            // Build email body
            $body = $this->buildDailySummaryBody($stats);
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);

            // Send email
            return $this->mailer->send();

        } catch (Exception $e) {
            error_log("Error sending daily summary: " . $e->getMessage());
            return false;
        }
    }

    private function buildDailySummaryBody($stats) {
        $body = '<h2>Daily Notification Queue Summary</h2>';
        $body .= '<h3>Last 24 Hours Statistics</h3>';
        
        $body .= '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
        $body .= '<tr><td><strong>Total Notifications</strong></td><td>' . $stats['total'] . '</td></tr>';
        $body .= '<tr><td><strong>Completed</strong></td><td>' . $stats['completed'] . '</td></tr>';
        $body .= '<tr><td><strong>Failed</strong></td><td>' . $stats['failed'] . '</td></tr>';
        $body .= '<tr><td><strong>Success Rate</strong></td><td>' . 
                 ($stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100, 2) : 0) . '%</td></tr>';
        $body .= '<tr><td><strong>Average Attempts</strong></td><td>' . $stats['avg_attempts'] . '</td></tr>';
        $body .= '<tr><td><strong>Unique Users</strong></td><td>' . $stats['unique_users'] . '</td></tr>';
        $body .= '<tr><td><strong>Active Channels</strong></td><td>' . $stats['active_channels'] . '</td></tr>';
        $body .= '</table>';

        $body .= '<p>For detailed information, please check the notification queue monitor.</p>';
        $body .= '<p>Generated at: ' . date('Y-m-d H:i:s') . '</p>';

        return $body;
    }
} 