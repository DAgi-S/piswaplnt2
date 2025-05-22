<?php
namespace Production;

class ResourceManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Get available resources for a given time period
     * @param string $startDate
     * @param string $endDate
     * @return array Available resources
     */
    public function getAvailableResources($startDate = null, $endDate = null) {
        $sql = "SELECT 
                    w.*,
                    COUNT(DISTINCT po.id) as total_orders,
                    AVG(CASE 
                        WHEN po.status = 'completed' AND po.actual_completion_date <= po.expected_completion_date 
                        THEN 1 ELSE 0 
                    END) * 100 as efficiency_rating
                FROM workstations w
                LEFT JOIN operation_workstations ow ON w.id = ow.workstation_id
                LEFT JOIN product_routing pr ON ow.operation_id = pr.operation_id
                LEFT JOIN production_orders po ON pr.product_id = po.product_id
                    AND (? IS NULL OR po.start_date >= ?)
                    AND (? IS NULL OR po.expected_completion_date <= ?)
                WHERE w.status = 'active'
                GROUP BY w.id";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssss', $startDate, $startDate, $endDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Calculate resource utilization
     * @param int $resourceId
     * @param string $startDate
     * @param string $endDate
     * @return array Utilization metrics
     */
    public function calculateResourceUtilization($resourceId, $startDate, $endDate) {
        // Get total available time
        $totalMinutes = $this->calculateTotalAvailableMinutes($startDate, $endDate);
        
        // Get utilized time from production orders
        $sql = "SELECT 
                    SUM(TIMESTAMPDIFF(MINUTE, po.start_date, 
                        COALESCE(po.actual_completion_date, po.expected_completion_date))) as used_minutes,
                    COUNT(DISTINCT po.id) as total_orders,
                    SUM(CASE WHEN po.actual_completion_date > po.expected_completion_date THEN 1 ELSE 0 END) as delayed_orders
                FROM workstations w
                JOIN operation_workstations ow ON w.id = ow.workstation_id
                JOIN product_routing pr ON ow.operation_id = pr.operation_id
                JOIN production_orders po ON pr.product_id = po.product_id
                WHERE w.id = ?
                AND po.start_date >= ?
                AND COALESCE(po.actual_completion_date, po.expected_completion_date) <= ?
                AND po.status != 'cancelled'";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iss', $resourceId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        // Calculate metrics
        $usedMinutes = $result['used_minutes'] ?? 0;
        $utilizationRate = ($totalMinutes > 0) ? ($usedMinutes / $totalMinutes) * 100 : 0;

        return [
            'resource_id' => $resourceId,
            'total_available_minutes' => $totalMinutes,
            'utilized_minutes' => $usedMinutes,
            'utilization_rate' => round($utilizationRate, 2),
            'total_orders' => $result['total_orders'] ?? 0,
            'delayed_orders' => $result['delayed_orders'] ?? 0,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ];
    }

    /**
     * Get resource capacity
     * @param int $resourceId
     * @return array Resource capacity details
     */
    public function getResourceCapacity($resourceId) {
        $sql = "SELECT 
                    w.*,
                    COUNT(DISTINCT ow.operation_id) as total_operations,
                    GROUP_CONCAT(DISTINCT o.name) as operations_list
                FROM workstations w
                LEFT JOIN operation_workstations ow ON w.id = ow.workstation_id
                LEFT JOIN operations o ON ow.operation_id = o.id
                WHERE w.id = ?
                GROUP BY w.id";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $resourceId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Check resource availability
     * @param int $resourceId
     * @param string $startDate
     * @param string $endDate
     * @return bool Whether the resource is available
     */
    public function checkResourceAvailability($resourceId, $startDate, $endDate) {
        $sql = "SELECT COUNT(*) as conflict_count
                FROM workstations w
                JOIN operation_workstations ow ON w.id = ow.workstation_id
                JOIN product_routing pr ON ow.operation_id = pr.operation_id
                JOIN production_orders po ON pr.product_id = po.product_id
                WHERE w.id = ?
                AND po.status IN ('confirmed', 'in_progress')
                AND (
                    (po.start_date BETWEEN ? AND ?) OR
                    (po.expected_completion_date BETWEEN ? AND ?) OR
                    (po.start_date <= ? AND po.expected_completion_date >= ?)
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issssss', 
            $resourceId, 
            $startDate, $endDate,
            $startDate, $endDate,
            $startDate, $endDate
        );
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['conflict_count'] == 0;
    }

    /**
     * Calculate maintenance windows
     * @param int $resourceId
     * @param string $startDate
     * @param string $endDate
     * @return array Maintenance windows
     */
    public function calculateMaintenanceWindows($resourceId, $startDate, $endDate) {
        $sql = "SELECT 
                    m.*,
                    w.name as workstation_name,
                    w.maintenance_frequency
                FROM maintenance_schedule m
                JOIN workstations w ON m.workstation_id = w.id
                WHERE m.workstation_id = ?
                AND m.scheduled_date BETWEEN ? AND ?
                ORDER BY m.scheduled_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iss', $resourceId, $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Calculate total available minutes between dates
     * @param string $startDate
     * @param string $endDate
     * @return int Total available minutes
     */
    private function calculateTotalAvailableMinutes($startDate, $endDate) {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = $start->diff($end);
        
        // Calculate total days
        $totalDays = $interval->days + 1;
        
        // Assume 8-hour workday (480 minutes)
        return $totalDays * 480;
    }
} 