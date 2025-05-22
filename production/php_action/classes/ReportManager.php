<?php
require_once dirname(__FILE__) . '/Database.php';
require_once dirname(__FILE__) . '/ErrorHandler.php';
require_once dirname(__FILE__) . '/DataFormatter.php';

class ReportManager {
    private $db;
    private $errorHandler;
    private $formatter;
    private $allowedFormats = ['pdf', 'excel', 'csv', 'html'];
    private $reportTypes = [
        'inventory' => 'Inventory Report',
        'stock_movement' => 'Stock Movement Report',
        'production' => 'Production Report',
        'material_usage' => 'Material Usage Report',
        'quality' => 'Quality Report',
        'cost_analysis' => 'Cost Analysis Report'
    ];
    private $validReportTypes = ['inventory', 'stock_movement', 'production'];

    public function __construct() {
        $this->db = new Database();
        $this->errorHandler = new ErrorHandler();
        $this->formatter = DataFormatter::getInstance();
    }

    private function formatReportData($data, $type) {
        foreach ($data as &$row) {
            // Format dates
            if (isset($row['date'])) {
                $row['date'] = $this->formatter->formatDate($row['date']);
            }
            if (isset($row['created_at'])) {
                $row['created_at'] = $this->formatter->formatDateTime($row['created_at']);
            }
            if (isset($row['updated_at'])) {
                $row['updated_at'] = $this->formatter->formatDateTime($row['updated_at']);
            }

            // Format numbers based on report type
            switch ($type) {
                case 'inventory':
                    if (isset($row['quantity'])) {
                        $row['quantity'] = $this->formatter->formatNumber($row['quantity']);
                    }
                    if (isset($row['unit_cost'])) {
                        $row['unit_cost'] = $this->formatter->formatCurrency($row['unit_cost']);
                    }
                    if (isset($row['total_value'])) {
                        $row['total_value'] = $this->formatter->formatCurrency($row['total_value']);
                    }
                    break;

                case 'production':
                    if (isset($row['efficiency'])) {
                        $row['efficiency'] = $this->formatter->formatPercentage($row['efficiency']);
                    }
                    if (isset($row['production_cost'])) {
                        $row['production_cost'] = $this->formatter->formatCurrency($row['production_cost']);
                    }
                    break;

                case 'quality':
                    if (isset($row['defect_rate'])) {
                        $row['defect_rate'] = $this->formatter->formatPercentage($row['defect_rate']);
                    }
                    if (isset($row['pass_rate'])) {
                        $row['pass_rate'] = $this->formatter->formatPercentage($row['pass_rate']);
                    }
                    break;

                case 'cost_analysis':
                    $currencyFields = ['material_cost', 'labor_cost', 'overhead_cost', 'total_cost'];
                    foreach ($currencyFields as $field) {
                        if (isset($row[$field])) {
                            $row[$field] = $this->formatter->formatCurrency($row[$field]);
                        }
                    }
                    break;
            }
        }
        return $data;
    }

    /**
     * Generate report based on type, format and filters
     */
    public function generateReport($type, $format, $filters) {
        try {
            // Validate report type
            if (!array_key_exists($type, $this->reportTypes)) {
                throw new Exception("Invalid report type: {$type}");
            }

            // Validate format
            if (!in_array($format, $this->allowedFormats)) {
                throw new Exception("Invalid format: {$format}");
            }

            // Validate and sanitize filters
            $filters = $this->validateFilters($filters);

            // Get report data
            $data = $this->getReportData($type, $filters);
            
            if (empty($data)) {
                return [
                    'status' => true,
                    'message' => 'No data found for the selected criteria',
                    'data' => $this->generateEmptyReport($format)
                ];
            }

            // Format data according to report type
            $formattedData = $this->formatReportData($data, $type);

            // Generate report in requested format
            $result = $this->generateFormattedReport($formattedData, $type, $format);

            return [
                'status' => true,
                'message' => 'Report generated successfully',
                'data' => $result
            ];

        } catch (Exception $e) {
            $this->errorHandler->handleError($e);
            return [
                'status' => false,
                'message' => "Failed to generate report: " . $e->getMessage()
            ];
        }
    }

    private function generateFormattedReport($data, $type, $format) {
        switch ($format) {
            case 'html':
                return $this->generateHtmlReport($data, $type);
            case 'pdf':
                return $this->generatePdfReport($data, $type);
            case 'excel':
                return $this->generateExcelReport($data, $type);
            case 'csv':
                return $this->generateCsvReport($data, $type);
            default:
                throw new Exception("Unsupported format: {$format}");
        }
    }

    /**
     * Get report data based on type and filters
     */
    private function getReportData($type, $filters) {
        $query = $this->buildReportQuery($type, $filters);
        try {
            $stmt = $this->db->connect()->prepare($query['sql']);
            $stmt->execute($query['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getReportData: " . $e->getMessage());
            throw new Exception("Failed to fetch report data");
        }
    }

    /**
     * Build SQL query based on report type and filters
     */
    private function buildReportQuery($type, $filters) {
        switch ($type) {
            case 'inventory':
                return $this->buildInventoryReportQuery($filters);
            case 'stock_movement':
                return $this->buildStockMovementReportQuery($filters);
            case 'production':
                return $this->buildProductionReportQuery($filters);
            default:
                throw new Exception("Invalid report type");
        }
    }

    /**
     * Build inventory report query
     */
    private function buildInventoryReportQuery($filters) {
        $sql = "SELECT 
                    p.product_id,
                    p.product_code,
                    p.name as product_name,
                    p.cost,
                    p.selling_price,
                    p.current_stock as quantity,
                    p.min_stock_level,
                    b.name as brand_name,
                    c.name as category_name
                FROM products p
                LEFT JOIN brands b ON p.brand_id = b.brand_id
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(p.created_at) >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(p.created_at) <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND p.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY p.name ASC";

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Build stock movement report query
     */
    private function buildStockMovementReportQuery($filters) {
        $sql = "SELECT 
                    sm.movement_id,
                    p.product_code,
                    p.name as product_name,
                    sm.quantity,
                    sm.movement_type,
                    sm.reference_type,
                    sm.reference_id,
                    iml.source_type,
                    iml.destination_type,
                    iml.created_at
                FROM stock_movements sm
                LEFT JOIN products p ON sm.product_id = p.product_id
                LEFT JOIN inventory_movement_log iml ON sm.movement_id = iml.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(iml.created_at) >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(iml.created_at) <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        if (!empty($filters['product_id'])) {
            $sql .= " AND sm.product_id = :product_id";
            $params[':product_id'] = $filters['product_id'];
        }

        if (!empty($filters['movement_type'])) {
            $sql .= " AND sm.movement_type = :movement_type";
            $params[':movement_type'] = $filters['movement_type'];
        }

        $sql .= " ORDER BY iml.created_at DESC";

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Build production report query
     */
    private function buildProductionReportQuery($filters) {
        $sql = "SELECT 
                    po.id as production_id,
                    po.order_number,
                    pp.product_code,
                    pp.name as product_name,
                    po.target_quantity,
                    po.completed_quantity,
                    po.status,
                    qc.quantity_checked,
                    qc.quantity_passed,
                    qc.quantity_failed,
                    (SELECT SUM(pb.quantity_required * rm.cost_per_unit)
                     FROM product_bom pb
                     JOIN raw_materials rm ON rm.id = pb.material_id
                     WHERE pb.product_id = pp.id) as total_material_cost
                FROM production_orders po
                LEFT JOIN production_products pp ON po.product_id = pp.id
                LEFT JOIN quality_control qc ON qc.production_order_id = po.id
                WHERE 1=1";

        $params = [];

        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(po.created_at) >= :start_date";
            $params[':start_date'] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(po.created_at) <= :end_date";
            $params[':end_date'] = $filters['end_date'];
        }

        if (!empty($filters['product_id'])) {
            $sql .= " AND po.product_id = :product_id";
            $params[':product_id'] = $filters['product_id'];
        }

        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $sql .= " AND po.status = :status";
            $params[':status'] = $filters['status'];
        }

        $sql .= " ORDER BY po.created_at DESC";

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * Generate HTML report
     */
    private function generateHtmlReport($data, $type) {
        $html = '<table class="table table-bordered table-striped">';
        
        // Add headers
        $html .= '<thead><tr>';
        foreach (array_keys($data[0]) as $header) {
            $header = ucwords(str_replace('_', ' ', $header));
            $html .= "<th>{$header}</th>";
        }
        $html .= '</tr></thead>';
        
        // Add data rows
        $html .= '<tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $value) {
                $html .= "<td>" . htmlspecialchars($value ?? '') . "</td>";
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        
        return $html;
    }

    /**
     * Generate PDF report
     */
    private function generatePdfReport($data, $type) {
        require_once 'tcpdf/tcpdf.php';
        
        // Create PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Stock Management System');
        $pdf->SetTitle(ucfirst($type) . ' Report');
        
        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Add a page
        $pdf->AddPage();
        
        // Add content
        $html = $this->generateHtmlReport($data, $type);
        $pdf->writeHTML($html, true, false, true, false, '');
        
        return $pdf->Output('', 'S');
    }

    /**
     * Generate Excel report
     */
    private function generateExcelReport($data, $type) {
        require_once 'PHPExcel/PHPExcel.php';
        
        // Create new PHPExcel object
        $excel = new PHPExcel();
        
        // Set document properties
        $excel->getProperties()
            ->setCreator("Stock Management System")
            ->setLastModifiedBy("Stock Management System")
            ->setTitle(ucfirst($type) . " Report")
            ->setSubject(ucfirst($type) . " Report")
            ->setDescription(ucfirst($type) . " Report generated on " . date('Y-m-d H:i:s'));
        
        // Add headers
        $col = 0;
        foreach (array_keys($data[0]) as $header) {
            $header = ucwords(str_replace('_', ' ', $header));
            $excel->getActiveSheet()->setCellValueByColumnAndRow($col, 1, $header);
            $col++;
        }
        
        // Add data
        $row = 2;
        foreach ($data as $rowData) {
            $col = 0;
            foreach ($rowData as $value) {
                $excel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }
            $row++;
        }
        
        // Save to string
        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    /**
     * Generate CSV report
     */
    private function generateCsvReport($data, $type) {
        $output = fopen('php://temp', 'r+');
        
        // Add headers
        fputcsv($output, array_keys($data[0]));
        
        // Add data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Generate empty report
     */
    private function generateEmptyReport($format) {
        switch ($format) {
            case 'html':
                return '<div class="alert alert-info">No data found for the selected criteria.</div>';
            case 'pdf':
                require_once 'tcpdf/tcpdf.php';
                $pdf = new TCPDF();
                $pdf->AddPage();
                $pdf->writeHTML('<h1>No Data Found</h1><p>No data found for the selected criteria.</p>');
                return $pdf->Output('', 'S');
            case 'excel':
                require_once 'PHPExcel/PHPExcel.php';
                $excel = new PHPExcel();
                $excel->getActiveSheet()->setCellValue('A1', 'No data found for the selected criteria.');
                $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
                ob_start();
                $writer->save('php://output');
                return ob_get_clean();
            case 'csv':
                return "No data found for the selected criteria.\n";
            default:
                throw new Exception("Unsupported format for empty report: {$format}");
        }
    }

    /**
     * Schedule a report for automated generation
     * @param array $schedule Schedule configuration
     * @return array Response with status and schedule details
     */
    public function scheduleReport($schedule) {
        try {
            // Validate schedule parameters
            $this->validateSchedule($schedule);

            // Store schedule in database
            $sql = "INSERT INTO report_schedules 
                    (report_type, frequency, filters, format, email_recipients, last_run, next_run, status) 
                    VALUES (?, ?, ?, ?, ?, NULL, ?, 'active')";
            
            $nextRun = $this->calculateNextRun($schedule['frequency']);
            
            $params = [
                $schedule['report_type'],
                $schedule['frequency'],
                json_encode($schedule['filters']),
                $schedule['format'],
                json_encode($schedule['email_recipients']),
                $nextRun
            ];

            $this->db->query($sql, $params);

            return [
                'status' => true,
                'message' => 'Report scheduled successfully',
                'next_run' => $nextRun
            ];
        } catch (Exception $e) {
            $this->errorHandler->handleError($e);
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate schedule parameters
     * @param array $schedule Schedule configuration
     * @throws Exception if validation fails
     */
    private function validateSchedule($schedule) {
        $requiredFields = ['report_type', 'frequency', 'format', 'email_recipients'];
        foreach ($requiredFields as $field) {
            if (empty($schedule[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        if (!array_key_exists($schedule['report_type'], $this->reportTypes)) {
            throw new Exception("Invalid report type: {$schedule['report_type']}");
        }

        if (!in_array($schedule['format'], $this->allowedFormats)) {
            throw new Exception("Invalid format: {$schedule['format']}");
        }

        $allowedFrequencies = ['daily', 'weekly', 'monthly'];
        if (!in_array($schedule['frequency'], $allowedFrequencies)) {
            throw new Exception("Invalid frequency: {$schedule['frequency']}");
        }
    }

    /**
     * Calculate next run time based on frequency
     * @param string $frequency Schedule frequency
     * @return string Next run datetime
     */
    private function calculateNextRun($frequency) {
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

    /**
     * Validate and sanitize report filters
     */
    private function validateFilters($filters) {
        if (!is_array($filters)) {
            return [];
        }

        $validatedFilters = [];

        // Validate dates
        if (!empty($filters['start_date'])) {
            $startDate = date('Y-m-d', strtotime($filters['start_date']));
            if ($startDate) {
                $validatedFilters['start_date'] = $startDate;
            }
        }

        if (!empty($filters['end_date'])) {
            $endDate = date('Y-m-d', strtotime($filters['end_date']));
            if ($endDate) {
                $validatedFilters['end_date'] = $endDate;
            }
        }

        // Validate numeric values
        $numericFields = ['quantity', 'price', 'threshold'];
        foreach ($numericFields as $field) {
            if (isset($filters[$field])) {
                if (is_numeric($filters[$field])) {
                    $validatedFilters[$field] = floatval($filters[$field]);
                }
            }
        }

        // Validate and sanitize string values
        $stringFields = ['status', 'category', 'brand', 'product_code'];
        foreach ($stringFields as $field) {
            if (isset($filters[$field])) {
                // Remove special characters and HTML
                $sanitized = strip_tags($filters[$field]);
                $sanitized = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $sanitized);
                if (!empty($sanitized)) {
                    $validatedFilters[$field] = $sanitized;
                }
            }
        }

        // Validate arrays (like product_ids, category_ids)
        $arrayFields = ['product_ids', 'category_ids', 'warehouse_ids'];
        foreach ($arrayFields as $field) {
            if (isset($filters[$field]) && is_array($filters[$field])) {
                $validatedFilters[$field] = array_filter($filters[$field], function($value) {
                    return is_numeric($value) && $value > 0;
                });
            }
        }

        // Validate JSON strings
        if (isset($filters['custom_filters']) && is_string($filters['custom_filters'])) {
            try {
                $decoded = json_decode($filters['custom_filters'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $validatedFilters['custom_filters'] = json_encode($decoded);
                }
            } catch (Exception $e) {
                error_log("JSON validation error: " . $e->getMessage());
            }
        }

        return $validatedFilters;
    }
} 