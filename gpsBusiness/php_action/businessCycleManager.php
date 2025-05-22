<?php
require_once 'core.php';

class BusinessCycleManager {
    private $db;
    private $lastError;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Create a new business cycle
     * @param string $cycleNumber Format: BC-YYYY-NNN (e.g., BC-2024-001)
     * @param string $startDate Format: Y-m-d
     * @return int|false Returns cycle ID if successful, false on failure
     */
    public function createCycle($cycleNumber = null, $startDate = null) {
        try {
            // Generate cycle number if not provided
            if (!$cycleNumber) {
                $cycleNumber = $this->generateCycleNumber();
            }

            // Use current date if start date not provided
            if (!$startDate) {
                $startDate = date('Y-m-d');
            }

            $stmt = $this->db->prepare("
                INSERT INTO gps_business_cycles 
                (cycle_number, start_date, status) 
                VALUES (?, ?, 'active')
            ");

            $stmt->bind_param("ss", $cycleNumber, $startDate);
            
            if ($stmt->execute()) {
                return $stmt->insert_id;
            }
            
            $this->lastError = $stmt->error;
            return false;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Generate a unique cycle number
     * @return string Format: BC-YYYY-NNN
     */
    private function generateCycleNumber() {
        $year = date('Y');
        
        // Get the last cycle number for this year
        $stmt = $this->db->prepare("
            SELECT MAX(cycle_number) as last_number 
            FROM gps_business_cycles 
            WHERE cycle_number LIKE ?
        ");
        
        $pattern = "BC-$year-%";
        $stmt->bind_param("s", $pattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['last_number']) {
            // Extract the sequence number and increment
            $lastSeq = intval(substr($row['last_number'], -3));
            $newSeq = $lastSeq + 1;
        } else {
            $newSeq = 1;
        }
        
        return sprintf("BC-%d-%03d", $year, $newSeq);
    }

    /**
     * Add an order to a business cycle
     * @param int $cycleId
     * @param int $orderId
     * @return bool
     */
    public function addOrderToCycle($cycleId, $orderId) {
        try {
            // First check if the cycle is active
            $stmt = $this->db->prepare("
                SELECT status FROM gps_business_cycles 
                WHERE id = ? AND status = 'active'
            ");
            $stmt->bind_param("i", $cycleId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $this->lastError = "Cycle is not active";
                return false;
            }

            // Add order to cycle
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO gps_business_cycle_orders 
                (business_cycle_id, order_id) 
                VALUES (?, ?)
            ");
            $stmt->bind_param("ii", $cycleId, $orderId);
            
            if (!$stmt->execute()) {
                $this->lastError = $stmt->error;
                return false;
            }

            // Update cycle totals
            return $this->updateCycleTotals($cycleId);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Add a sale to a business cycle
     * @param int $cycleId
     * @param int $saleId
     * @return bool
     */
    public function addSaleToCycle($cycleId, $saleId) {
        try {
            // First check if the cycle is active
            $stmt = $this->db->prepare("
                SELECT status FROM gps_business_cycles 
                WHERE id = ? AND status = 'active'
            ");
            $stmt->bind_param("i", $cycleId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                $this->lastError = "Cycle is not active";
                return false;
            }

            // Add sale to cycle
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO gps_business_cycle_sales 
                (business_cycle_id, sale_id) 
                VALUES (?, ?)
            ");
            $stmt->bind_param("ii", $cycleId, $saleId);
            
            if (!$stmt->execute()) {
                $this->lastError = $stmt->error;
                return false;
            }

            // Update cycle totals
            return $this->updateCycleTotals($cycleId);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Update cycle totals based on orders and sales
     * @param int $cycleId
     * @return bool
     */
    private function updateCycleTotals($cycleId) {
        try {
            // Start transaction
            $this->db->begin_transaction();

            // Calculate order totals
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(CASE WHEN o.currency = 'ETB' THEN o.total_price ELSE o.total_price * o.rate END) as total_purchase_etb,
                    SUM(CASE WHEN o.currency = 'USD' THEN o.total_price ELSE o.total_price / o.rate END) as total_purchase_usd,
                    SUM(CASE WHEN o.has_credit = 1 THEN o.credit_amount ELSE 0 END) as total_credit
                FROM gps_business_cycle_orders bco
                JOIN gps_orders o ON bco.order_id = o.id
                WHERE bco.business_cycle_id = ?
                GROUP BY bco.business_cycle_id
            ");
            $stmt->bind_param("i", $cycleId);
            $stmt->execute();
            $orderTotals = $stmt->get_result()->fetch_assoc();

            // Calculate sale totals
            $stmt = $this->db->prepare("
                SELECT 
                    SUM(CASE WHEN s.currency = 'ETB' THEN s.total ELSE s.total * s.rate END) as total_sales_etb,
                    SUM(CASE WHEN s.currency = 'USD' THEN s.total ELSE s.total / s.rate END) as total_sales_usd
                FROM gps_business_cycle_sales bcs
                JOIN gps_sales s ON bcs.sale_id = s.id
                WHERE bcs.business_cycle_id = ?
                GROUP BY bcs.business_cycle_id
            ");
            $stmt->bind_param("i", $cycleId);
            $stmt->execute();
            $saleTotals = $stmt->get_result()->fetch_assoc();

            // Calculate expenses
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(amount_etb), 0) as total_expenses
                FROM gps_business_expenses
                WHERE business_cycle_id = ?
            ");
            $stmt->bind_param("i", $cycleId);
            $stmt->execute();
            $expenseTotals = $stmt->get_result()->fetch_assoc();

            // Calculate profits
            $grossProfitEtb = ($saleTotals['total_sales_etb'] ?? 0) - ($orderTotals['total_purchase_etb'] ?? 0);
            $netProfitEtb = $grossProfitEtb - ($expenseTotals['total_expenses'] ?? 0) - ($orderTotals['total_credit'] ?? 0);

            // Update cycle
            $stmt = $this->db->prepare("
                UPDATE gps_business_cycles SET
                    total_purchase_etb = ?,
                    total_purchase_usd = ?,
                    total_sales_etb = ?,
                    total_sales_usd = ?,
                    total_expenses_etb = ?,
                    total_credit_amount = ?,
                    gross_profit_etb = ?,
                    net_profit_etb = ?
                WHERE id = ?
            ");
            
            $stmt->bind_param(
                "ddddddddi",
                $orderTotals['total_purchase_etb'],
                $orderTotals['total_purchase_usd'],
                $saleTotals['total_sales_etb'],
                $saleTotals['total_sales_usd'],
                $expenseTotals['total_expenses'],
                $orderTotals['total_credit'],
                $grossProfitEtb,
                $netProfitEtb,
                $cycleId
            );
            
            $success = $stmt->execute();
            
            if ($success) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollback();
                $this->lastError = $stmt->error;
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollback();
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Complete a business cycle and calculate profit distribution
     * @param int $cycleId
     * @return bool
     */
    public function completeCycle($cycleId) {
        try {
            $this->db->begin_transaction();

            // Update cycle status
            $stmt = $this->db->prepare("
                UPDATE gps_business_cycles 
                SET status = 'completed', 
                    end_date = CURRENT_DATE 
                WHERE id = ? AND status = 'active'
            ");
            $stmt->bind_param("i", $cycleId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to complete cycle");
            }

            // Get cycle details
            $stmt = $this->db->prepare("
                SELECT net_profit_etb 
                FROM gps_business_cycles 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $cycleId);
            $stmt->execute();
            $cycle = $stmt->get_result()->fetch_assoc();

            // Get investors
            $stmt = $this->db->prepare("
                SELECT id, share_percentage 
                FROM gps_investors
            ");
            $stmt->execute();
            $investors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Create profit distributions
            foreach ($investors as $investor) {
                $amount = $cycle['net_profit_etb'] * ($investor['share_percentage'] / 100);
                $stmt = $this->db->prepare("
                    INSERT INTO gps_profit_distributions 
                    (business_cycle_id, investor_id, distribution_date, amount_etb, distribution_type) 
                    VALUES (?, ?, CURRENT_DATE, ?, ?)
                ");
                $type = $amount >= 0 ? 'profit' : 'loss';
                $stmt->bind_param("iids", $cycleId, $investor['id'], $amount, $type);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to create profit distribution");
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Get the last error message
     * @return string
     */
    public function getLastError() {
        return $this->lastError;
    }
} 