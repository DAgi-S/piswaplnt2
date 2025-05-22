<?php
namespace Production;

class ProductionAnalytics {
    private $db;
    private $resourceManager;
    private $predictor;

    public function __construct($db) {
        $this->db = $db;
        $this->resourceManager = new ResourceManager($db);
        $this->predictor = new ProductionPredictor($db);
    }

    /**
     * Generate analytics report
     * @param string $startDate
     * @param string $endDate
     * @return array Analytics report
     */
    public function generateAnalyticsReport($startDate, $endDate) {
        return [
            'production_metrics' => $this->getProductionMetrics($startDate, $endDate),
            'resource_utilization' => $this->getResourceUtilization($startDate, $endDate),
            'quality_metrics' => $this->getQualityMetrics($startDate, $endDate),
            'efficiency_metrics' => $this->getEfficiencyMetrics($startDate, $endDate),
            'predictions' => $this->getProductionPredictions($startDate, $endDate)
        ];
    }

    /**
     * Get production metrics
     * @param string $startDate
     * @param string $endDate
     * @return array Production metrics
     */
    private function getProductionMetrics($startDate, $endDate) {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN actual_completion_date <= expected_completion_date THEN 1 ELSE 0 END) as on_time_orders,
                    AVG(CASE WHEN status = 'completed' 
                        THEN (completed_quantity / target_quantity) * 100 
                        ELSE NULL END) as completion_rate,
                    AVG(CASE WHEN status = 'completed' AND actual_completion_date IS NOT NULL
                        THEN TIMESTAMPDIFF(MINUTE, start_date, actual_completion_date) 
                        ELSE NULL END) as avg_production_time
                FROM production_orders
                WHERE created_at BETWEEN ? AND ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $metrics = $stmt->get_result()->fetch_assoc();

        // Calculate additional metrics
        $metrics['on_time_completion_rate'] = 
            ($metrics['completed_orders'] > 0) ? 
            ($metrics['on_time_orders'] / $metrics['completed_orders']) * 100 : 0;

        return $metrics;
    }

    /**
     * Get resource utilization metrics
     * @param string $startDate
     * @param string $endDate
     * @return array Resource utilization metrics
     */
    private function getResourceUtilization($startDate, $endDate) {
        $resources = $this->resourceManager->getAvailableResources();
        $utilization = [];

        foreach ($resources as $resource) {
            $utilization[] = $this->resourceManager->calculateResourceUtilization(
                $resource['id'],
                $startDate,
                $endDate
            );
        }

        return [
            'resources' => $utilization,
            'summary' => $this->calculateUtilizationSummary($utilization)
        ];
    }

    /**
     * Get quality metrics
     * @param string $startDate
     * @param string $endDate
     * @return array Quality metrics
     */
    private function getQualityMetrics($startDate, $endDate) {
        $sql = "SELECT 
                    COUNT(*) as total_inspections,
                    SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed_inspections,
                    SUM(quantity_checked) as total_quantity_checked,
                    SUM(quantity_passed) as total_quantity_passed,
                    SUM(quantity_failed) as total_quantity_failed,
                    COUNT(DISTINCT production_order_id) as orders_inspected
                FROM quality_control
                WHERE inspection_date BETWEEN ? AND ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $metrics = $stmt->get_result()->fetch_assoc();

        // Calculate pass rates
        $metrics['inspection_pass_rate'] = 
            ($metrics['total_inspections'] > 0) ? 
            ($metrics['passed_inspections'] / $metrics['total_inspections']) * 100 : 0;

        $metrics['quantity_pass_rate'] = 
            ($metrics['total_quantity_checked'] > 0) ? 
            ($metrics['total_quantity_passed'] / $metrics['total_quantity_checked']) * 100 : 0;

        // Get defect types distribution
        $sql = "SELECT 
                    defect_type,
                    COUNT(*) as occurrence_count,
                    SUM(quantity_failed) as total_failed_quantity
                FROM quality_control
                WHERE inspection_date BETWEEN ? AND ?
                    AND defect_type IS NOT NULL
                GROUP BY defect_type
                ORDER BY total_failed_quantity DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $metrics['defect_distribution'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return $metrics;
    }

    /**
     * Get efficiency metrics
     * @param string $startDate
     * @param string $endDate
     * @return array Efficiency metrics
     */
    private function getEfficiencyMetrics($startDate, $endDate) {
        $sql = "SELECT 
                    w.id,
                    w.name,
                    COUNT(DISTINCT po.id) as total_orders,
                    AVG(w.efficiency_rating) as efficiency_rating,
                    SUM(TIMESTAMPDIFF(MINUTE, wa.start_time, wa.end_time)) as total_runtime,
                    SUM(po.completed_quantity) as total_output
                FROM workstations w
                LEFT JOIN workstation_assignments wa ON w.id = wa.workstation_id
                LEFT JOIN production_orders po ON wa.order_id = po.id
                WHERE wa.start_time BETWEEN ? AND ?
                GROUP BY w.id";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get production predictions
     * @param string $startDate
     * @param string $endDate
     * @return array Production predictions
     */
    private function getProductionPredictions($startDate, $endDate) {
        // Get upcoming orders
        $sql = "SELECT 
                    po.*,
                    p.name as product_name,
                    p.complexity_factor,
                    p.setup_time
                FROM production_orders po
                JOIN products p ON po.product_id = p.id
                WHERE po.status = 'pending'
                AND po.scheduled_start BETWEEN ? AND ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Generate predictions for each order
        $predictions = [];
        foreach ($orders as $order) {
            $predictions[] = [
                'order_id' => $order['id'],
                'product_name' => $order['product_name'],
                'scheduled_start' => $order['scheduled_start'],
                'predictions' => $this->predictor->predictProductionTime($order)
            ];
        }

        return $predictions;
    }

    /**
     * Calculate utilization summary
     * @param array $utilization
     * @return array Summary metrics
     */
    private function calculateUtilizationSummary($utilization) {
        if (empty($utilization)) {
            return [
                'avg_utilization' => 0,
                'total_utilized_minutes' => 0,
                'total_available_minutes' => 0
            ];
        }

        $totalUtilizedMinutes = array_sum(array_column($utilization, 'utilized_minutes'));
        $totalAvailableMinutes = array_sum(array_column($utilization, 'total_available_minutes'));
        $avgUtilization = ($totalAvailableMinutes > 0) ? 
            ($totalUtilizedMinutes / $totalAvailableMinutes) * 100 : 0;

        return [
            'avg_utilization' => round($avgUtilization, 2),
            'total_utilized_minutes' => $totalUtilizedMinutes,
            'total_available_minutes' => $totalAvailableMinutes
        ];
    }
} 