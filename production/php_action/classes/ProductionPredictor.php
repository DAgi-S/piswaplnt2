<?php
namespace Production;

class ProductionPredictor {
    private $db;
    private $modelPath;

    public function __construct($db) {
        $this->db = $db;
        $this->modelPath = dirname(__FILE__) . '/../../ml_models/';
    }

    /**
     * Predict production time for a given order
     * @param array $orderData Order details
     * @return array Prediction results
     */
    public function predictProductionTime($orderData) {
        try {
            // Get historical production data for training
            $historicalData = $this->getHistoricalProductionData($orderData['product_id']);
            
            // Prepare features for prediction
            $features = $this->prepareFeatures($orderData, $historicalData);
            
            // Load or train the model
            $model = $this->loadOrTrainModel('production_time', $historicalData);
            
            // Make prediction
            $prediction = $this->predict($model, $features);
            
            // Calculate confidence interval
            $confidence = $this->calculateConfidenceInterval($prediction, $historicalData);
            
            return [
                'predicted_time' => $prediction['production_time'],
                'confidence_interval' => $confidence,
                'factors' => $prediction['contributing_factors']
            ];

        } catch (\Exception $e) {
            error_log("Error in production time prediction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Predict resource utilization
     * @param array $resourceData Resource details
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Prediction results
     */
    public function predictResourceUtilization($resourceData, $startDate, $endDate) {
        try {
            // Get historical utilization data
            $historicalData = $this->getHistoricalUtilizationData(
                $resourceData['workstation_id'],
                $startDate,
                $endDate
            );
            
            // Prepare features
            $features = $this->prepareUtilizationFeatures($resourceData, $historicalData);
            
            // Load or train the model
            $model = $this->loadOrTrainModel('resource_utilization', $historicalData);
            
            // Make prediction
            $prediction = $this->predict($model, $features);
            
            return [
                'predicted_utilization' => $prediction['utilization_rate'],
                'peak_periods' => $prediction['peak_periods'],
                'bottleneck_probability' => $prediction['bottleneck_probability']
            ];

        } catch (\Exception $e) {
            error_log("Error in resource utilization prediction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Predict optimal schedule
     * @param array $orders List of production orders
     * @return array Optimized schedule
     */
    public function predictOptimalSchedule($orders) {
        try {
            // Get historical scheduling data
            $historicalData = $this->getHistoricalSchedulingData();
            
            // Prepare features for each order
            $scheduleFeatures = [];
            foreach ($orders as $order) {
                $scheduleFeatures[] = $this->prepareScheduleFeatures($order, $historicalData);
            }
            
            // Load or train the model
            $model = $this->loadOrTrainModel('schedule_optimization', $historicalData);
            
            // Generate schedule predictions
            $predictions = [];
            foreach ($scheduleFeatures as $features) {
                $predictions[] = $this->predict($model, $features);
            }
            
            // Optimize schedule based on predictions
            $optimizedSchedule = $this->optimizeSchedule($predictions, $orders);
            
            return [
                'schedule' => $optimizedSchedule,
                'efficiency_gain' => $this->calculateEfficiencyGain($optimizedSchedule, $orders),
                'risk_factors' => $this->identifyRiskFactors($optimizedSchedule)
            ];

        } catch (\Exception $e) {
            error_log("Error in schedule optimization: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get historical production data
     * @param int $productId Product ID
     * @return array Historical data
     */
    private function getHistoricalProductionData($productId) {
        $sql = "SELECT 
                    po.*,
                    p.complexity_factor,
                    p.setup_time,
                    wa.start_time,
                    wa.end_time,
                    w.efficiency_rating,
                    o.skill_level
                FROM production_orders po
                JOIN products p ON po.product_id = p.id
                JOIN workstation_assignments wa ON po.id = wa.order_id
                JOIN workstations w ON wa.workstation_id = w.id
                LEFT JOIN operator_assignments oa ON wa.workstation_id = oa.workstation_id
                LEFT JOIN operators o ON oa.operator_id = o.id
                WHERE po.product_id = ?
                AND po.status = 'completed'
                ORDER BY po.completion_date DESC
                LIMIT 1000";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get historical utilization data
     * @param int $workstationId Workstation ID
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Historical data
     */
    private function getHistoricalUtilizationData($workstationId, $startDate, $endDate) {
        $sql = "SELECT 
                    wa.*,
                    po.target_quantity,
                    po.completed_quantity,
                    po.completion_date,
                    p.complexity_factor,
                    p.setup_time
                FROM workstation_assignments wa
                JOIN production_orders po ON wa.order_id = po.id
                JOIN products p ON po.product_id = p.id
                WHERE wa.workstation_id = ?
                AND wa.start_time >= DATE_SUB(?, INTERVAL 6 MONTH)
                AND wa.end_time <= ?
                ORDER BY wa.start_time DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iss', $workstationId, $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get historical scheduling data
     * @return array Historical data
     */
    private function getHistoricalSchedulingData() {
        $sql = "SELECT 
                    po.*,
                    p.complexity_factor,
                    p.setup_time,
                    wa.workstation_id,
                    wa.start_time,
                    wa.end_time,
                    w.efficiency_rating
                FROM production_orders po
                JOIN products p ON po.product_id = p.id
                JOIN workstation_assignments wa ON po.id = wa.order_id
                JOIN workstations w ON wa.workstation_id = w.id
                WHERE po.status = 'completed'
                AND po.completion_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                ORDER BY po.completion_date DESC";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Prepare features for production time prediction
     * @param array $orderData Order data
     * @param array $historicalData Historical data
     * @return array Prepared features
     */
    private function prepareFeatures($orderData, $historicalData) {
        return [
            'quantity' => $orderData['target_quantity'],
            'complexity' => $orderData['complexity_factor'],
            'setup_time' => $orderData['setup_time'],
            'operator_skill' => $this->calculateAverageOperatorSkill($orderData['workstation_id']),
            'workstation_efficiency' => $this->getWorkstationEfficiency($orderData['workstation_id']),
            'historical_performance' => $this->analyzeHistoricalPerformance($historicalData)
        ];
    }

    /**
     * Prepare features for utilization prediction
     * @param array $resourceData Resource data
     * @param array $historicalData Historical data
     * @return array Prepared features
     */
    private function prepareUtilizationFeatures($resourceData, $historicalData) {
        return [
            'workstation_efficiency' => $resourceData['efficiency_rating'],
            'maintenance_schedule' => $this->getMaintenanceSchedule($resourceData['workstation_id']),
            'operator_availability' => $this->getOperatorAvailability($resourceData['workstation_id']),
            'historical_utilization' => $this->analyzeHistoricalUtilization($historicalData),
            'seasonal_factors' => $this->analyzeSeasonalPatterns($historicalData)
        ];
    }

    /**
     * Prepare features for schedule optimization
     * @param array $order Order data
     * @param array $historicalData Historical data
     * @return array Prepared features
     */
    private function prepareScheduleFeatures($order, $historicalData) {
        return [
            'priority' => $order['priority'],
            'due_date' => strtotime($order['due_date']),
            'quantity' => $order['target_quantity'],
            'complexity' => $order['complexity_factor'],
            'setup_time' => $order['setup_time'],
            'resource_requirements' => $this->getResourceRequirements($order['product_id']),
            'historical_patterns' => $this->analyzeHistoricalPatterns($historicalData)
        ];
    }

    /**
     * Load or train a machine learning model
     * @param string $modelType Type of model to load/train
     * @param array $trainingData Training data
     * @return object Trained model
     */
    private function loadOrTrainModel($modelType, $trainingData) {
        $modelFile = $this->modelPath . $modelType . '_model.pkl';
        
        if (file_exists($modelFile) && $this->isModelFresh($modelFile)) {
            return $this->loadModel($modelFile);
        }
        
        return $this->trainModel($modelType, $trainingData);
    }

    /**
     * Make a prediction using the model
     * @param object $model Trained model
     * @param array $features Input features
     * @return array Prediction results
     */
    private function predict($model, $features) {
        // This is a placeholder for actual ML prediction
        // In a real implementation, this would use a Python ML service
        // For now, we'll use statistical calculations
        
        return $this->calculateStatisticalPrediction($features);
    }

    /**
     * Calculate statistical prediction
     * @param array $features Input features
     * @return array Prediction results
     */
    private function calculateStatisticalPrediction($features) {
        // Implement statistical calculations based on features
        // This is a simplified version until ML integration is implemented
        
        $prediction = [];
        
        if (isset($features['quantity']) && isset($features['complexity'])) {
            // Production time prediction
            $baseTime = $features['quantity'] * ($features['setup_time'] / 60);
            $complexityFactor = 1 + ($features['complexity'] / 10);
            $prediction['production_time'] = $baseTime * $complexityFactor;
            
        } elseif (isset($features['workstation_efficiency'])) {
            // Resource utilization prediction
            $prediction['utilization_rate'] = $features['workstation_efficiency'] * 0.8;
            $prediction['peak_periods'] = $this->identifyPeakPeriods($features);
            $prediction['bottleneck_probability'] = $this->calculateBottleneckProbability($features);
        }
        
        return $prediction;
    }

    /**
     * Calculate confidence interval for predictions
     * @param array $prediction Prediction results
     * @param array $historicalData Historical data
     * @return array Confidence interval
     */
    private function calculateConfidenceInterval($prediction, $historicalData) {
        // Calculate standard deviation from historical data
        $stdDev = $this->calculateStandardDeviation($historicalData);
        
        // Use 95% confidence interval (1.96 for normal distribution)
        $margin = 1.96 * $stdDev;
        
        return [
            'lower_bound' => $prediction['production_time'] - $margin,
            'upper_bound' => $prediction['production_time'] + $margin,
            'confidence_level' => 0.95
        ];
    }

    /**
     * Calculate standard deviation from historical data
     * @param array $historicalData Historical data
     * @return float Standard deviation
     */
    private function calculateStandardDeviation($historicalData) {
        if (empty($historicalData)) {
            return 0;
        }

        $values = array_map(function($record) {
            return (strtotime($record['end_time']) - strtotime($record['start_time'])) / 3600;
        }, $historicalData);

        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(function($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);

        return sqrt(array_sum($squaredDiffs) / count($values));
    }

    /**
     * Check if a model file is fresh (less than 1 day old)
     * @param string $modelFile Path to model file
     * @return bool Whether the model is fresh
     */
    private function isModelFresh($modelFile) {
        return (time() - filemtime($modelFile)) < 86400;
    }

    /**
     * Calculate efficiency gain from schedule optimization
     * @param array $optimizedSchedule Optimized schedule
     * @param array $originalOrders Original orders
     * @return array Efficiency metrics
     */
    private function calculateEfficiencyGain($optimizedSchedule, $originalOrders) {
        // Calculate various efficiency metrics
        $setupTimeReduction = $this->calculateSetupTimeReduction($optimizedSchedule, $originalOrders);
        $resourceUtilization = $this->calculateResourceUtilization($optimizedSchedule);
        $deliveryPerformance = $this->calculateDeliveryPerformance($optimizedSchedule, $originalOrders);
        
        return [
            'setup_time_reduction' => $setupTimeReduction,
            'resource_utilization_improvement' => $resourceUtilization,
            'delivery_performance_improvement' => $deliveryPerformance
        ];
    }

    /**
     * Identify risk factors in the optimized schedule
     * @param array $schedule Optimized schedule
     * @return array Risk factors
     */
    private function identifyRiskFactors($schedule) {
        $risks = [];
        
        // Check for tight schedules
        if ($this->hasTightSchedules($schedule)) {
            $risks[] = [
                'type' => 'tight_schedule',
                'severity' => 'high',
                'description' => 'Some orders have minimal buffer time'
            ];
        }
        
        // Check for resource overutilization
        $overutilizedResources = $this->checkResourceUtilization($schedule);
        if (!empty($overutilizedResources)) {
            $risks[] = [
                'type' => 'resource_overutilization',
                'severity' => 'medium',
                'affected_resources' => $overutilizedResources
            ];
        }
        
        // Check for potential bottlenecks
        $bottlenecks = $this->identifyPotentialBottlenecks($schedule);
        if (!empty($bottlenecks)) {
            $risks[] = [
                'type' => 'potential_bottlenecks',
                'severity' => 'high',
                'bottlenecks' => $bottlenecks
            ];
        }
        
        return $risks;
    }
} 