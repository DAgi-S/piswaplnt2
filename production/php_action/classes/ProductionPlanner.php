<?php
namespace Production;

class ProductionPlanner {
    private $db;
    private $resourceManager;
    private $scheduler;
    private $predictor;

    public function __construct($db) {
        $this->db = $db;
        $this->resourceManager = new ResourceManager($db);
        $this->scheduler = new ProductionScheduler($db);
        $this->predictor = new ProductionPredictor($db);
    }

    public function createOptimizedPlan($orderId) {
        try {
            $this->db->begin_transaction();

            // Get order details
            $sql = "SELECT 
                        po.*,
                        p.name as product_name,
                        p.complexity_factor,
                        p.setup_time,
                        p.processing_time_per_unit,
                        p.required_skills
                    FROM production_orders po
                    JOIN products p ON po.product_id = p.id
                    WHERE po.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$order) {
                throw new \Exception("Order #$orderId not found");
            }

            // Get ML predictions for production time
            $timePrediction = $this->predictor->predictProductionTime($order);

            // Adjust order timing based on ML predictions
            $order['predicted_time'] = $timePrediction['predicted_time'];
            $order['confidence_interval'] = $timePrediction['confidence_interval'];

            // Create production schedule with ML insights
            $schedule = $this->scheduler->createSchedule($orderId, null, $timePrediction);

            // Get resource utilization predictions
            $utilizationPrediction = $this->predictor->predictResourceUtilization(
                ['workstation_id' => $schedule['workstation_id']],
                $schedule['start_time'],
                $schedule['end_time']
            );

            // Generate detailed production plan
            $plan = $this->generateProductionPlan($order, $schedule, $utilizationPrediction);

            // Store predictions for analysis
            $this->storePredictions($orderId, $timePrediction, $utilizationPrediction);

            // Update order with plan details
            $sql = "UPDATE production_orders SET
                        production_plan = ?,
                        estimated_completion_time = ?,
                        predicted_duration = ?,
                        confidence_lower = ?,
                        confidence_upper = ?,
                        last_updated = NOW()
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $planJson = json_encode($plan);
            $predictedDuration = $timePrediction['predicted_time'];
            $confidenceLower = $timePrediction['confidence_interval']['lower_bound'];
            $confidenceUpper = $timePrediction['confidence_interval']['upper_bound'];
            
            $stmt->bind_param('ssdddi', 
                $planJson,
                $schedule['end_time'],
                $predictedDuration,
                $confidenceLower,
                $confidenceUpper,
                $orderId
            );
            $stmt->execute();

            $this->db->commit();

            return [
                'order_id' => $orderId,
                'product_name' => $order['product_name'],
                'schedule' => $schedule,
                'plan' => $plan,
                'predictions' => [
                    'time' => $timePrediction,
                    'utilization' => $utilizationPrediction
                ]
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function generateProductionPlan($order, $schedule, $utilizationPrediction) {
        // Calculate time estimates using ML predictions
        $setupTime = $order['setup_time'];
        $predictedTime = $order['predicted_time'];
        $confidenceInterval = $order['confidence_interval'];

        // Generate milestones with ML insights
        $startTime = strtotime($schedule['start_time']);
        $milestones = [
            [
                'phase' => 'Setup',
                'start_time' => date('Y-m-d H:i:s', $startTime),
                'end_time' => date('Y-m-d H:i:s', $startTime + ($setupTime * 60)),
                'duration_minutes' => $setupTime,
                'bottleneck_probability' => $utilizationPrediction['bottleneck_probability']
            ],
            [
                'phase' => 'Production',
                'start_time' => date('Y-m-d H:i:s', $startTime + ($setupTime * 60)),
                'end_time' => $schedule['end_time'],
                'duration_minutes' => $predictedTime - $setupTime,
                'units_per_hour' => round(60 / ($predictedTime / $order['target_quantity'])),
                'confidence_interval' => [
                    'lower' => $confidenceInterval['lower_bound'],
                    'upper' => $confidenceInterval['upper_bound']
                ]
            ]
        ];

        // Calculate quality control checkpoints with ML optimization
        $qcIntervals = $this->calculateOptimalQCIntervals($order, $predictedTime);
        $qcPoints = [];
        $qcInterval = $predictedTime / $qcIntervals;
        
        for ($i = 1; $i <= $qcIntervals; $i++) {
            $qcTime = $startTime + ($setupTime * 60) + ($qcInterval * $i * 60);
            $qcPoints[] = [
                'checkpoint' => $i,
                'time' => date('Y-m-d H:i:s', $qcTime),
                'expected_quantity' => round(($order['target_quantity'] / $qcIntervals) * $i),
                'risk_level' => $this->calculateQCRiskLevel($i, $qcIntervals, $utilizationPrediction)
            ];
        }

        // Generate resource requirements with ML insights
        $resourceRequirements = [
            'workstation' => [
                'id' => $schedule['workstation_id'],
                'name' => $schedule['workstation_name'],
                'predicted_utilization' => $utilizationPrediction['predicted_utilization'],
                'peak_periods' => $utilizationPrediction['peak_periods']
            ],
            'materials' => $this->calculateMaterialRequirements($order['product_id'], $order['target_quantity']),
            'operators' => $this->getRequiredOperators($schedule['workstation_id'])
        ];

        return [
            'order_details' => [
                'id' => $order['id'],
                'product_name' => $order['product_name'],
                'quantity' => $order['target_quantity'],
                'priority' => $order['priority'],
                'due_date' => $order['due_date']
            ],
            'timeline' => [
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'predicted_duration' => $predictedTime,
                'confidence_interval' => $confidenceInterval,
                'milestones' => $milestones,
                'qc_checkpoints' => $qcPoints
            ],
            'resources' => $resourceRequirements,
            'efficiency_metrics' => [
                'complexity_factor' => $order['complexity_factor'],
                'setup_efficiency' => $this->calculateSetupEfficiency($order['setup_time'], $order['complexity_factor']),
                'predicted_efficiency' => $this->calculatePredictedEfficiency($predictedTime, $order['target_quantity']),
                'bottleneck_probability' => $utilizationPrediction['bottleneck_probability']
            ]
        ];
    }

    private function calculateOptimalQCIntervals($order, $predictedTime) {
        // Base interval on quantity and complexity
        $baseIntervals = max(1, floor($order['target_quantity'] / 100));
        
        // Adjust based on predicted time and complexity
        $timeAdjustment = max(1, floor($predictedTime / 120)); // One checkpoint every 2 hours
        $complexityAdjustment = max(1, ceil($order['complexity_factor'] / 2));
        
        return max($baseIntervals, $timeAdjustment * $complexityAdjustment);
    }

    private function calculateQCRiskLevel($checkpointNum, $totalCheckpoints, $utilizationPrediction) {
        // Early checkpoints have higher risk due to setup and initial production
        $timingRisk = ($totalCheckpoints - $checkpointNum + 1) / $totalCheckpoints;
        
        // Consider bottleneck probability
        $bottleneckRisk = $utilizationPrediction['bottleneck_probability'];
        
        // Calculate weighted risk score
        $riskScore = ($timingRisk * 0.4) + ($bottleneckRisk * 0.6);
        
        // Categorize risk level
        if ($riskScore >= 0.7) return 'high';
        if ($riskScore >= 0.4) return 'medium';
        return 'low';
    }

    private function calculatePredictedEfficiency($predictedTime, $targetQuantity) {
        // Calculate units per hour
        $unitsPerHour = ($targetQuantity / $predictedTime) * 60;
        
        // Calculate efficiency percentage (based on theoretical maximum)
        $theoreticalMax = 100; // Units per hour
        return min(100, ($unitsPerHour / $theoreticalMax) * 100);
    }

    private function storePredictions($orderId, $timePrediction, $utilizationPrediction) {
        // Store production time prediction
        $sql = "INSERT INTO production_predictions (
                    order_id,
                    predicted_time,
                    confidence_lower,
                    confidence_upper,
                    prediction_date
                ) VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iddd',
            $orderId,
            $timePrediction['predicted_time'],
            $timePrediction['confidence_interval']['lower_bound'],
            $timePrediction['confidence_interval']['upper_bound']
        );
        $stmt->execute();

        // Store resource utilization prediction
        $sql = "INSERT INTO resource_predictions (
                    workstation_id,
                    predicted_utilization,
                    prediction_start,
                    prediction_end,
                    peak_periods,
                    bottleneck_probability
                ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $peakPeriodsJson = json_encode($utilizationPrediction['peak_periods']);
        $stmt->bind_param('idsssd',
            $utilizationPrediction['workstation_id'],
            $utilizationPrediction['predicted_utilization'],
            $utilizationPrediction['start_time'],
            $utilizationPrediction['end_time'],
            $peakPeriodsJson,
            $utilizationPrediction['bottleneck_probability']
        );
        $stmt->execute();
    }

    public function optimizeProductionSchedule($startDate = null, $endDate = null) {
        // Get schedule metrics before optimization
        $beforeMetrics = $this->scheduler->getScheduleMetrics($startDate, $endDate);

        // Get ML predictions for optimal schedule
        $orders = $this->getUnscheduledOrders($startDate, $endDate);
        $schedulePrediction = $this->predictor->predictOptimalSchedule($orders);

        // Apply optimization based on ML insights
        $optimizedOrders = $this->scheduler->optimizeSchedule(
            $startDate,
            $endDate,
            $schedulePrediction
        );

        // Get metrics after optimization
        $afterMetrics = $this->scheduler->getScheduleMetrics($startDate, $endDate);

        // Store schedule optimization results
        $this->storeScheduleOptimization(
            $schedulePrediction,
            $beforeMetrics,
            $afterMetrics
        );

        return [
            'optimization_results' => [
                'orders_scheduled' => count($optimizedOrders),
                'before_metrics' => $beforeMetrics,
                'after_metrics' => $afterMetrics,
                'improvement' => [
                    'utilization' => $afterMetrics['avg_utilization'] - $beforeMetrics['avg_utilization'],
                    'delayed_orders' => $beforeMetrics['delayed_orders'] - $afterMetrics['delayed_orders']
                ],
                'ml_insights' => [
                    'efficiency_gain' => $schedulePrediction['efficiency_gain'],
                    'risk_factors' => $schedulePrediction['risk_factors']
                ]
            ],
            'scheduled_orders' => $optimizedOrders
        ];
    }

    private function getUnscheduledOrders($startDate, $endDate) {
        $sql = "SELECT 
                    po.*,
                    p.complexity_factor,
                    p.setup_time,
                    p.processing_time_per_unit
                FROM production_orders po
                JOIN products p ON po.product_id = p.id
                WHERE po.status = 'pending'
                AND (po.due_date BETWEEN ? AND ? OR po.due_date IS NULL)
                ORDER BY po.priority DESC, po.due_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function storeScheduleOptimization($prediction, $beforeMetrics, $afterMetrics) {
        $sql = "INSERT INTO schedule_predictions (
                    schedule_id,
                    optimization_data,
                    efficiency_gain,
                    risk_factors,
                    is_applied
                ) VALUES (?, ?, ?, ?, TRUE)";
        
        $scheduleId = uniqid('sch_', true);
        $optimizationData = json_encode([
            'before_metrics' => $beforeMetrics,
            'after_metrics' => $afterMetrics
        ]);
        $efficiencyGain = json_encode($prediction['efficiency_gain']);
        $riskFactors = json_encode($prediction['risk_factors']);
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssss',
            $scheduleId,
            $optimizationData,
            $efficiencyGain,
            $riskFactors
        );
        $stmt->execute();
    }

    private function calculateMaterialRequirements($productId, $quantity) {
        $sql = "SELECT 
                    m.id,
                    m.name,
                    m.unit,
                    bm.quantity as quantity_per_unit,
                    m.stock_quantity as available_quantity
                FROM bill_of_materials bm
                JOIN materials m ON bm.material_id = m.id
                WHERE bm.product_id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $result = $stmt->get_result();

        $materials = [];
        while ($row = $result->fetch_assoc()) {
            $requiredQuantity = $row['quantity_per_unit'] * $quantity;
            $materials[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'required_quantity' => $requiredQuantity,
                'available_quantity' => $row['available_quantity'],
                'unit' => $row['unit'],
                'status' => $requiredQuantity <= $row['available_quantity'] ? 'sufficient' : 'insufficient'
            ];
        }

        return $materials;
    }

    private function getRequiredOperators($workstationId) {
        $sql = "SELECT 
                    o.id,
                    o.name,
                    o.skill_level,
                    GROUP_CONCAT(s.skill_name) as skills
                FROM operators o
                JOIN operator_skills s ON o.id = s.operator_id
                JOIN operator_assignments oa ON o.id = oa.operator_id
                WHERE oa.workstation_id = ?
                GROUP BY o.id";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $workstationId);
        $stmt->execute();
        $result = $stmt->get_result();

        $operators = [];
        while ($row = $result->fetch_assoc()) {
            $operators[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'skill_level' => $row['skill_level'],
                'skills' => explode(',', $row['skills'])
            ];
        }

        return $operators;
    }

    private function calculateSetupEfficiency($setupTime, $complexityFactor) {
        // Base efficiency calculation
        $baseEfficiency = 100 - (($setupTime / 60) * 5); // 5% penalty per hour of setup time
        
        // Adjust for complexity
        $complexityAdjustment = (1 - ($complexityFactor / 10)) * 20; // Up to 20% adjustment based on complexity
        
        $efficiency = $baseEfficiency + $complexityAdjustment;
        
        // Ensure efficiency is between 0 and 100
        return max(0, min(100, $efficiency));
    }

    public function getProductionAnalytics($startDate = null, $endDate = null) {
        if (!$startDate) {
            $startDate = date('Y-m-d H:i:s', strtotime('-1 month'));
        }
        if (!$endDate) {
            $endDate = date('Y-m-d H:i:s');
        }

        // Get overall production metrics
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    AVG(CASE WHEN actual_completion_time <= scheduled_end THEN 1 ELSE 0 END) * 100 as on_time_completion_rate,
                    AVG(CASE WHEN actual_quantity >= target_quantity THEN 1 ELSE 0 END) * 100 as target_achievement_rate,
                    AVG((actual_quantity / target_quantity) * 100) as avg_completion_percentage
                FROM production_orders
                WHERE actual_completion_time BETWEEN ? AND ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $overallMetrics = $stmt->get_result()->fetch_assoc();

        // Get workstation performance
        $sql = "SELECT 
                    w.id,
                    w.name,
                    COUNT(DISTINCT wa.order_id) as orders_processed,
                    AVG(w.efficiency_factor) * 100 as efficiency_rating,
                    SUM(TIMESTAMPDIFF(MINUTE, wa.start_time, wa.end_time)) as total_runtime_minutes
                FROM workstations w
                LEFT JOIN workstation_assignments wa ON w.id = wa.workstation_id
                WHERE wa.start_time BETWEEN ? AND ?
                GROUP BY w.id";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $workstationPerformance = [];
        while ($row = $result->fetch_assoc()) {
            $workstationPerformance[] = $row;
        }

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'overall_metrics' => $overallMetrics,
            'workstation_performance' => $workstationPerformance,
            'resource_utilization' => $this->calculateResourceUtilization($startDate, $endDate)
        ];
    }

    private function calculateResourceUtilization($startDate, $endDate) {
        $workstations = $this->resourceManager->getAvailableResources($startDate, $endDate);
        
        $utilization = [];
        foreach ($workstations as $workstation) {
            $metrics = $this->resourceManager->calculateResourceUtilization(
                $workstation['id'],
                $startDate,
                $endDate
            );
            
            $utilization[] = [
                'workstation_id' => $workstation['id'],
                'workstation_name' => $workstation['name'],
                'utilization_percentage' => $metrics['utilization_percentage'],
                'total_orders' => $metrics['total_orders'],
                'available_capacity' => $metrics['available_capacity']
            ];
        }
        
        return $utilization;
    }
} 