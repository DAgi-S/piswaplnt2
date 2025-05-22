<?php
require_once 'Database.php';
require_once 'ErrorHandler.php';

class SimpleReportManager {
    private $db;
    private $errorHandler;
    private $reportTypes = [
        // Inventory Reports
        'low_stock' => 'Products Below Minimum Stock Level',
        'stock_value' => 'Current Stock Value Report',
        
        // Movement Reports
        'daily_movements' => 'Daily Stock Movements',
        'monthly_movements' => 'Monthly Stock Movements',
        
        // Production Reports
        'production_summary' => 'Production Orders Summary',
        'production_efficiency' => 'Production Efficiency Report',
        
        // Quality Reports
        'quality_summary' => 'Quality Control Summary',
        'defect_analysis' => 'Product Defect Analysis'
    ];

    public function __construct() {
        $this->db = new Database();
        $this->errorHandler = new ErrorHandler();
    }

    public function generateReport($type, $filters = []) {
        try {
            if (!array_key_exists($type, $this->reportTypes)) {
                throw new Exception("Invalid report type: " . $type);
            }

            $data = $this->getReportData($type, $filters);
            return [
                'status' => true,
                'title' => $this->reportTypes[$type],
                'data' => $data,
                'message' => 'Report generated successfully'
            ];
        } catch (Exception $e) {
            error_log("Report generation error: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Error generating report: ' . $e->getMessage()
            ];
        }
    }

    private function getReportData($type, $filters) {
        switch ($type) {
            case 'low_stock':
                return $this->getLowStockReport();
            case 'stock_value':
                return $this->getStockValueReport();
            case 'daily_movements':
                return $this->getDailyMovementsReport($filters);
            case 'monthly_movements':
                return $this->getMonthlyMovementsReport($filters);
            case 'production_summary':
                return $this->getProductionSummaryReport($filters);
            case 'production_efficiency':
                return $this->getProductionEfficiencyReport($filters);
            case 'quality_summary':
                return $this->getQualitySummaryReport($filters);
            case 'defect_analysis':
                return $this->getDefectAnalysisReport($filters);
            default:
                throw new Exception("Invalid report type");
        }
    }

    private function getLowStockReport() {
        $sql = "SELECT 
                    p.product_code,
                    p.name,
                    p.current_stock,
                    p.min_stock_level,
                    (p.min_stock_level - p.current_stock) as shortage
                FROM products p
                WHERE p.current_stock < p.min_stock_level
                ORDER BY shortage DESC";
        return $this->db->fetchAll($sql);
    }

    private function getStockValueReport() {
        $sql = "SELECT 
                    p.product_code,
                    p.name,
                    p.current_stock,
                    p.cost,
                    (p.current_stock * p.cost) as total_value
                FROM products p
                ORDER BY total_value DESC";
        return $this->db->fetchAll($sql);
    }

    private function getDailyMovementsReport($filters) {
        $sql = "SELECT 
                    DATE(iml.created_at) as date,
                    p.product_code,
                    p.name as product_name,
                    sm.movement_type,
                    SUM(sm.quantity) as total_quantity
                FROM stock_movements sm
                JOIN products p ON sm.product_id = p.product_id
                JOIN inventory_movement_log iml ON sm.movement_id = iml.id
                WHERE DATE(iml.created_at) = CURDATE()
                GROUP BY DATE(iml.created_at), p.product_id, sm.movement_type
                ORDER BY date DESC, product_name";
        return $this->db->fetchAll($sql);
    }

    private function getMonthlyMovementsReport($filters) {
        $sql = "SELECT 
                    DATE_FORMAT(iml.created_at, '%Y-%m') as month,
                    p.product_code,
                    p.name as product_name,
                    sm.movement_type,
                    SUM(sm.quantity) as total_quantity
                FROM stock_movements sm
                JOIN products p ON sm.product_id = p.product_id
                JOIN inventory_movement_log iml ON sm.movement_id = iml.id
                WHERE DATE(iml.created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
                GROUP BY DATE_FORMAT(iml.created_at, '%Y-%m'), p.product_id, sm.movement_type
                ORDER BY month DESC, product_name";
        return $this->db->fetchAll($sql);
    }

    private function getProductionSummaryReport($filters) {
        $sql = "SELECT 
                    po.order_number,
                    pp.name as product_name,
                    po.target_quantity,
                    po.completed_quantity,
                    po.status,
                    COALESCE(qc.quantity_passed, 0) as passed_qty,
                    COALESCE(qc.quantity_failed, 0) as failed_qty,
                    DATE(po.created_at) as production_date
                FROM production_orders po
                JOIN production_products pp ON po.product_id = pp.id
                LEFT JOIN quality_control qc ON qc.production_order_id = po.id
                WHERE po.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY po.created_at DESC";
        return $this->db->fetchAll($sql);
    }

    private function getProductionEfficiencyReport($filters) {
        $sql = "SELECT 
                    pp.name as product_name,
                    COUNT(po.id) as total_orders,
                    SUM(po.target_quantity) as total_target,
                    SUM(po.completed_quantity) as total_completed,
                    ROUND((SUM(po.completed_quantity) / SUM(po.target_quantity)) * 100, 2) as completion_rate,
                    COALESCE(SUM(qc.quantity_passed), 0) as total_passed,
                    COALESCE(SUM(qc.quantity_failed), 0) as total_failed,
                    ROUND((COALESCE(SUM(qc.quantity_passed), 0) / NULLIF(SUM(po.completed_quantity), 0)) * 100, 2) as quality_rate
                FROM production_orders po
                JOIN production_products pp ON po.product_id = pp.id
                LEFT JOIN quality_control qc ON qc.production_order_id = po.id
                WHERE po.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY pp.id, pp.name
                ORDER BY completion_rate DESC";
        return $this->db->fetchAll($sql);
    }

    private function getQualitySummaryReport($filters) {
        $sql = "SELECT 
                    pp.name as product_name,
                    COUNT(DISTINCT po.id) as total_batches,
                    SUM(qc.quantity_checked) as total_checked,
                    SUM(qc.quantity_passed) as total_passed,
                    SUM(qc.quantity_failed) as total_failed,
                    ROUND((SUM(qc.quantity_passed) / NULLIF(SUM(qc.quantity_checked), 0)) * 100, 2) as pass_rate
                FROM quality_control qc
                JOIN production_orders po ON qc.production_order_id = po.id
                JOIN production_products pp ON po.product_id = pp.id
                WHERE po.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY pp.id, pp.name
                ORDER BY pass_rate DESC";
        return $this->db->fetchAll($sql);
    }

    private function getDefectAnalysisReport($filters) {
        $sql = "SELECT 
                    pp.name as product_name,
                    po.order_number,
                    qc.quantity_failed,
                    (qc.quantity_failed / NULLIF(qc.quantity_checked, 0)) * 100 as defect_rate,
                    DATE(po.created_at) as production_date
                FROM quality_control qc
                JOIN production_orders po ON qc.production_order_id = po.id
                JOIN production_products pp ON po.product_id = pp.id
                WHERE qc.quantity_failed > 0
                AND po.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY defect_rate DESC";
        return $this->db->fetchAll($sql);
    }
} 