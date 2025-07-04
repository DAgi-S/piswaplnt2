<?php
class BackupMailer {
    private $db;
    private $settings;

    public function __construct($db) {
        $this->db = $db;
        $this->settings = $this->loadSettings();
    }

    private function loadSettings() {
        $result = $this->db->query("SELECT * FROM system_backup_email_settings WHERE enabled = 1 ORDER BY id DESC LIMIT 1");
        return $result ? $result->fetch_assoc() : null;
    }

    public function send($subject, $body, $toOverride = null) {
        if (!$this->settings || !$this->settings['enabled']) return false;
        $to = $toOverride ?: $this->settings['to_emails'];
        $headers = "From: " . $this->settings['from_name'] . " <" . $this->settings['from_email'] . ">\r\n";
        $headers .= "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
        // For real SMTP, use PHPMailer or similar. Here, use mail() for simplicity.
        return mail($to, $subject, $body, $headers);
    }

    // For future: add PHPMailer/SMTP support
} 