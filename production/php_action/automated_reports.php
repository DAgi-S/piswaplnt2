<?php
require_once 'core.php';
require_once 'classes/ProductionAnalytics.php';

class AutomatedReports {
    private $db;
    private $analytics;
    private $emailConfig;

    public function __construct($db) {
        $this->db = $db;
        $this->analytics = new Production\ProductionAnalytics($db);
        
        // Email configuration
        $this->emailConfig = [
            'from' => 'production-reports@company.com',
            'smtp_host' => 'smtp.company.com',
            'smtp_port' => 587,
            'smtp_secure' => 'tls'
        ];
    }

    public function generateScheduledReports() {
        try {
            // Get all scheduled reports
            $sql = "SELECT 
                        sr.*,
                        GROUP_CONCAT(ru.email) as recipient_emails
                    FROM scheduled_reports sr
                    LEFT JOIN report_users ru ON sr.id = ru.report_id
                    WHERE sr.is_active = 1
                    AND (
                        (sr.frequency = 'daily') OR
                        (sr.frequency = 'weekly' AND DAYOFWEEK(NOW()) = sr.schedule_day) OR
                        (sr.frequency = 'monthly' AND DAYOFMONTH(NOW()) = sr.schedule_day)
                    )
                    GROUP BY sr.id";

            $result = $this->db->query($sql);
            
            while ($schedule = $result->fetch_assoc()) {
                $this->processScheduledReport($schedule);
            }

            // Log successful execution
            $this->logReportGeneration('success', 'Scheduled reports generated successfully');

        } catch (\Exception $e) {
            // Log error
            $this->logReportGeneration('error', 'Error generating scheduled reports: ' . $e->getMessage());
            throw $e;
        }
    }

    private function processScheduledReport($schedule) {
        // Calculate date range based on frequency
        $dateRange = $this->calculateDateRange($schedule['frequency']);
        
        // Generate report
        $report = $this->analytics->generateAnalyticsReport(
            $dateRange['start_date'],
            $dateRange['end_date']
        );

        // Generate report files
        $files = $this->generateReportFiles($report, $schedule);

        // Send email with report
        $recipients = explode(',', $schedule['recipient_emails']);
        $this->sendReportEmail($recipients, $files, $schedule);

        // Update last run time
        $this->updateLastRunTime($schedule['id']);

        // Clean up temporary files
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    private function calculateDateRange($frequency) {
        $endDate = date('Y-m-d H:i:s');
        
        switch ($frequency) {
            case 'daily':
                $startDate = date('Y-m-d H:i:s', strtotime('-1 day'));
                break;
            case 'weekly':
                $startDate = date('Y-m-d H:i:s', strtotime('-1 week'));
                break;
            case 'monthly':
                $startDate = date('Y-m-d H:i:s', strtotime('-1 month'));
                break;
            default:
                $startDate = date('Y-m-d H:i:s', strtotime('-1 day'));
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
    }

    private function generateReportFiles($report, $schedule) {
        $files = [];
        $timestamp = date('Y-m-d_His');
        
        // Generate CSV report
        if ($schedule['include_csv']) {
            $csvFile = sys_get_temp_dir() . "/production_analytics_{$timestamp}.csv";
            $this->generateCSVReport($report, $csvFile);
            $files[] = $csvFile;
        }

        // Generate PDF report
        if ($schedule['include_pdf']) {
            $pdfFile = sys_get_temp_dir() . "/production_analytics_{$timestamp}.pdf";
            $this->generatePDFReport($report, $pdfFile);
            $files[] = $pdfFile;
        }

        return $files;
    }

    private function generateCSVReport($report, $filename) {
        $output = fopen($filename, 'w');
        fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

        // Write sections
        $this->writeEfficiencyMetrics($output, $report);
        $this->writeResourcePerformance($output, $report);
        $this->writeQualityMetrics($output, $report);
        $this->writeProductionTrends($output, $report);
        $this->writeBottleneckAnalysis($output, $report);

        fclose($output);
    }

    private function generatePDFReport($report, $filename) {
        // Implementation for PDF generation
        // This would typically use a PDF library like TCPDF or FPDF
        // For now, we'll create a simple HTML to PDF conversion
        $html = $this->generateReportHTML($report);
        
        // Use wkhtmltopdf or similar tool to convert HTML to PDF
        $command = "wkhtmltopdf --quiet --margin-top 20 --margin-bottom 20 - " . escapeshellarg($filename);
        $descriptorspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];
        
        $process = proc_open($command, $descriptorspec, $pipes);
        
        if (is_resource($process)) {
            fwrite($pipes[0], $html);
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }
    }

    private function sendReportEmail($recipients, $files, $schedule) {
        $boundary = md5(time());
        
        // Headers
        $headers = [
            'From: ' . $this->emailConfig['from'],
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary=' . $boundary
        ];

        // Email body
        $body = $this->generateEmailBody($schedule);
        
        // Add attachments
        foreach ($files as $file) {
            $body .= $this->attachFile($file, $boundary);
        }
        
        // Close boundary
        $body .= "--{$boundary}--";

        // Send email to each recipient
        foreach ($recipients as $recipient) {
            mail(
                trim($recipient),
                'Production Analytics Report - ' . date('Y-m-d'),
                $body,
                implode("\r\n", $headers)
            );
        }
    }

    private function generateEmailBody($schedule) {
        $boundary = md5(time());
        
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        
        $body .= "<html><body>";
        $body .= "<h2>Production Analytics Report</h2>";
        $body .= "<p>Please find attached the {$schedule['frequency']} production analytics report.</p>";
        $body .= "<p>Report period: " . $this->getReportPeriodDescription($schedule['frequency']) . "</p>";
        $body .= "<p>Generated on: " . date('Y-m-d H:i:s') . "</p>";
        $body .= "</body></html>\r\n\r\n";
        
        return $body;
    }

    private function attachFile($filepath, $boundary) {
        $content = file_get_contents($filepath);
        $filename = basename($filepath);
        
        $attachment = "--{$boundary}\r\n";
        $attachment .= "Content-Type: application/octet-stream; name=\"{$filename}\"\r\n";
        $attachment .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n";
        $attachment .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $attachment .= chunk_split(base64_encode($content)) . "\r\n";
        
        return $attachment;
    }

    private function updateLastRunTime($scheduleId) {
        $sql = "UPDATE scheduled_reports 
                SET last_run = NOW() 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $scheduleId);
        $stmt->execute();
    }

    private function logReportGeneration($status, $message) {
        $sql = "INSERT INTO report_generation_logs 
                (status, message, created_at) 
                VALUES (?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $status, $message);
        $stmt->execute();
    }

    private function getReportPeriodDescription($frequency) {
        switch ($frequency) {
            case 'daily':
                return 'Last 24 hours';
            case 'weekly':
                return 'Last 7 days';
            case 'monthly':
                return 'Last 30 days';
            default:
                return 'Custom period';
        }
    }

    // Helper methods for CSV generation
    private function writeEfficiencyMetrics($output, $report) {
        fputcsv($output, ['Efficiency Metrics']);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Orders', $report['efficiency_metrics']['efficiency_metrics']['total_orders']]);
        fputcsv($output, ['On-Time Completion Rate', number_format($report['efficiency_metrics']['efficiency_metrics']['on_time_completion_rate'], 1) . '%']);
        fputcsv($output, ['Completion Rate', number_format($report['efficiency_metrics']['efficiency_metrics']['completion_rate'], 1) . '%']);
        fputcsv($output, []);
    }

    private function writeResourcePerformance($output, $report) {
        fputcsv($output, ['Resource Performance']);
        fputcsv($output, ['Workstation', 'Total Assignments', 'Efficiency Rating', 'Utilization Rate']);
        foreach ($report['resource_performance'] as $resource) {
            fputcsv($output, [
                $resource['workstation_name'],
                $resource['metrics']['total_assignments'],
                number_format($resource['metrics']['efficiency_rating'], 1) . '%',
                number_format($resource['metrics']['utilization_rate'], 1) . '%'
            ]);
        }
        fputcsv($output, []);
    }

    private function writeQualityMetrics($output, $report) {
        fputcsv($output, ['Quality Metrics']);
        fputcsv($output, ['Product', 'Total Orders', 'Defect Rate', 'Quality Score']);
        foreach ($report['quality_metrics'] as $quality) {
            fputcsv($output, [
                $quality['product_name'],
                $quality['metrics']['total_orders'],
                number_format($quality['metrics']['avg_defect_rate'], 2) . '%',
                number_format($quality['metrics']['avg_quality_score'], 1)
            ]);
        }
        fputcsv($output, []);
    }

    private function writeProductionTrends($output, $report) {
        fputcsv($output, ['Production Trends']);
        fputcsv($output, ['Date', 'Completed Orders', 'Total Units', 'Completion Rate']);
        foreach ($report['production_trends'] as $trend) {
            fputcsv($output, [
                $trend['date'],
                $trend['metrics']['completed_orders'],
                $trend['metrics']['total_units'],
                number_format($trend['metrics']['completion_rate'], 1) . '%'
            ]);
        }
        fputcsv($output, []);
    }

    private function writeBottleneckAnalysis($output, $report) {
        fputcsv($output, ['Bottleneck Analysis']);
        fputcsv($output, ['Workstation', 'Total Orders', 'Delayed Orders', 'Utilization Rate']);
        foreach ($report['bottleneck_analysis'] as $bottleneck) {
            fputcsv($output, [
                $bottleneck['workstation_name'],
                $bottleneck['metrics']['total_orders'],
                $bottleneck['metrics']['delayed_orders'],
                number_format($bottleneck['metrics']['utilization_rate'], 1) . '%'
            ]);
        }
    }
} 