<?php
namespace Production;

class ProductionScheduler {
    private $db;
    private $resourceManager;

    public function __construct($db) {
        $this->db = $db;
        $this->resourceManager = new ResourceManager($db);
    }

    public function createSchedule($orderId, $startDate = null) {
        try {
            // Get order details
            $sql = "SELECT 
                        po.*,
                        p.complexity_factor,
                        p.setup_time,
                        p.processing_time_per_unit
                    FROM production_orders po
                    JOIN products p ON po.product_id = p.id
                    WHERE po.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $orderId);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();

            if (!$startDate) {
                $startDate = date('Y-m-d H:i:s');
            }

            // Calculate end date based on quantity and processing time
            $totalProcessingTime = ($order['target_quantity'] * $order['processing_time_per_unit']) + $order['setup_time'];
            $endDate = date('Y-m-d H:i:s', strtotime($startDate) + ($totalProcessingTime * 60));

            // Find optimal workstation
            $workstation = $this->resourceManager->findOptimalWorkstation(
                $order['product_id'],
                $order['target_quantity'],
                $startDate,
                $endDate
            );

            if (!$workstation) {
                throw new \Exception("No suitable workstation found for order #$orderId");
            }

            // Check for schedule conflicts
            $conflicts = $this->checkScheduleConflicts($workstation['id'], $startDate, $endDate);
            if ($conflicts) {
                // Try to resolve conflicts by adjusting start time
                $newStartDate = $this->findNextAvailableSlot($workstation['id'], $startDate, $totalProcessingTime);
                if (!$newStartDate) {
                    throw new \Exception("Unable to resolve scheduling conflicts for order #$orderId");
                }
                $startDate = $newStartDate;
                $endDate = date('Y-m-d H:i:s', strtotime($startDate) + ($totalProcessingTime * 60));
            }

            // Create workstation assignment
            $this->db->begin_transaction();

            $sql = "INSERT INTO workstation_assignments (
                        workstation_id, 
                        order_id, 
                        start_time, 
                        end_time, 
                        status
                    ) VALUES (?, ?, ?, ?, 'scheduled')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('iiss', 
                $workstation['id'],
                $orderId,
                $startDate,
                $endDate
            );
            $stmt->execute();

            // Update order status
            $sql = "UPDATE production_orders 
                    SET status = 'scheduled', 
                        scheduled_start = ?,
                        scheduled_end = ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssi', 
                $startDate,
                $endDate,
                $orderId
            );
            $stmt->execute();

            $this->db->commit();

            return [
                'order_id' => $orderId,
                'workstation_id' => $workstation['id'],
                'workstation_name' => $workstation['name'],
                'start_time' => $startDate,
                'end_time' => $endDate,
                'total_processing_time' => $totalProcessingTime
            ];

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function checkScheduleConflicts($workstationId, $startDate, $endDate) {
        $sql = "SELECT COUNT(*) as conflict_count
                FROM workstation_assignments
                WHERE workstation_id = ?
                AND status != 'completed'
                AND (
                    (start_time BETWEEN ? AND ?) OR
                    (end_time BETWEEN ? AND ?) OR
                    (start_time <= ? AND end_time >= ?)
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issssss', 
            $workstationId,
            $startDate, $endDate,
            $startDate, $endDate,
            $startDate, $endDate
        );
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['conflict_count'] > 0;
    }

    private function findNextAvailableSlot($workstationId, $startDate, $requiredMinutes) {
        $sql = "SELECT end_time
                FROM workstation_assignments
                WHERE workstation_id = ?
                AND status != 'completed'
                AND end_time >= ?
                ORDER BY end_time";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $workstationId, $startDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $currentTime = strtotime($startDate);
        while ($row = $result->fetch_assoc()) {
            $slotEnd = strtotime($row['end_time']);
            $nextStart = strtotime($row['end_time']) + 300; // 5-minute buffer

            if (($nextStart - $currentTime) / 60 >= $requiredMinutes) {
                return date('Y-m-d H:i:s', $currentTime);
            }
            $currentTime = $nextStart;
        }

        return date('Y-m-d H:i:s', $currentTime);
    }

    public function optimizeSchedule($startDate = null, $endDate = null) {
        if (!$startDate) {
            $startDate = date('Y-m-d H:i:s');
        }
        if (!$endDate) {
            $endDate = date('Y-m-d H:i:s', strtotime('+1 week'));
        }

        // Get all unscheduled orders
        $sql = "SELECT id, priority, due_date
                FROM production_orders
                WHERE status = 'pending'
                ORDER BY priority DESC, due_date ASC";

        $result = $this->db->query($sql);
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            try {
                $schedule = $this->createSchedule($row['id'], $startDate);
                $orders[] = $schedule;
            } catch (\Exception $e) {
                // Log scheduling failure
                error_log("Failed to schedule order #{$row['id']}: " . $e->getMessage());
            }
        }

        return $orders;
    }

    public function getScheduleMetrics($startDate = null, $endDate = null) {
        if (!$startDate) {
            $startDate = date('Y-m-d H:i:s');
        }
        if (!$endDate) {
            $endDate = date('Y-m-d H:i:s', strtotime('+1 week'));
        }

        $sql = "SELECT 
                    COUNT(DISTINCT wa.order_id) as total_orders,
                    COUNT(DISTINCT wa.workstation_id) as total_workstations,
                    AVG(w.efficiency_factor) as avg_efficiency,
                    SUM(CASE WHEN po.due_date < wa.end_time THEN 1 ELSE 0 END) as delayed_orders
                FROM workstation_assignments wa
                JOIN workstations w ON wa.workstation_id = w.id
                JOIN production_orders po ON wa.order_id = po.id
                WHERE wa.start_time BETWEEN ? AND ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $metrics = $stmt->get_result()->fetch_assoc();

        // Calculate workstation utilization
        $workstations = $this->resourceManager->getAvailableResources($startDate, $endDate);
        $totalUtilization = 0;
        foreach ($workstations as $workstation) {
            $utilization = $this->resourceManager->calculateResourceUtilization(
                $workstation['id'],
                $startDate,
                $endDate
            );
            $totalUtilization += $utilization['utilization_percentage'];
        }

        $metrics['avg_utilization'] = $totalUtilization / count($workstations);
        
        return $metrics;
    }
} 