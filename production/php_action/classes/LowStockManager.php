<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/NotificationHandler.php';

class LowStockManager {
    private $connect;
    private $notificationHandler;
    private $mailer;
    private $thresholdBuffer = 1.2; // 20% buffer for reorder point
    private $defaultThresholdBuffer = 20.0; // Default 20% buffer

    public function __construct($connect) {
        $this->connect = $connect;
        $this->notificationHandler = new NotificationHandler($connect);
        // Initialize PHPMailer
        try {
            $this->initializeMailer();
        } catch (Exception $e) {
            error_log("PHPMailer initialization error: " . $e->getMessage());
            // Continue without email functionality
            $this->mailer = null;
        }
    }

    private function initializeMailer() {
        // Use Composer's autoloader
        if (!file_exists(__DIR__ . '/../../vendor/autoload.php')) {
            throw new Exception("Composer autoloader not found. Please run 'composer install'");
        }
        require_once __DIR__ . '/../../vendor/autoload.php';
        
        try {
            $this->mailer = new PHPMailer(true);
            // Configure mailer settings
            $this->mailer->isSMTP();
            $this->mailer->Host = 'smtp.example.com'; // Replace with actual SMTP host
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = 'your-email@example.com'; // Replace with actual email
            $this->mailer->Password = 'your-password'; // Replace with actual password
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = 587;
        } catch (Exception $e) {
            error_log("PHPMailer configuration error: " . $e->getMessage());
            throw $e;
        }
    }

    public function checkAndNotifyLowStock() {
        try {
            // Get threshold settings
            $thresholdBuffer = $this->getThresholdBuffer();
            
            // Get low stock items
            $sql = "SELECT 
                    p.id,
                    p.name,
                    p.current_stock,
                    p.min_stock_level,
                    p.unit,
                    w.name as warehouse_name
                FROM production_products p
                LEFT JOIN warehouses w ON p.warehouse_id = w.id
                WHERE p.status = 'active' 
                AND p.current_stock <= (p.min_stock_level * (1 + ?/100))";
            
            $stmt = $this->connect->prepare($sql);
            $stmt->bind_param("d", $thresholdBuffer);
            $stmt->execute();
            $result = $stmt->get_result();

            $notifiedItems = [];
            while ($row = $result->fetch_assoc()) {
                // Calculate stock percentage
                $stockPercentage = ($row['current_stock'] / $row['min_stock_level']) * 100;
                
                // Send notification for items below threshold
                if ($stockPercentage <= (100 + $thresholdBuffer)) {
                    $this->notificationHandler->sendLowStockNotification($row, $thresholdBuffer);
                    $notifiedItems[] = $row;
                }
            }

            return [
                'success' => true,
                'message' => count($notifiedItems) . ' notifications sent',
                'items' => $notifiedItems
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function getThresholdBuffer() {
        // Get threshold buffer from settings or use default
        $sql = "SELECT value FROM settings WHERE setting_key = 'threshold_buffer'";
        $result = $this->connect->query($sql);
        
        if ($result && $row = $result->fetch_assoc()) {
            return (float)$row['value'];
        }
        
        return 20.0; // Default 20% buffer
    }

    public function getLowStockSummary() {
        $sql = "SELECT 
                COUNT(CASE WHEN current_stock <= 0 THEN 1 END) as out_of_stock,
                COUNT(CASE WHEN current_stock > 0 AND current_stock <= min_stock_level THEN 1 END) as critical_stock,
                COUNT(CASE WHEN current_stock > min_stock_level AND current_stock <= (min_stock_level * 1.2) THEN 1 END) as low_stock,
                COUNT(*) as total_items
                FROM production_products
                WHERE status = 'active'";
        
        $result = $this->connect->query($sql);
        return $result->fetch_assoc();
    }

    public function checkLowStockItems() {
        $query = "SELECT 
                    CASE 
                        WHEN rm.id IS NOT NULL THEN 'raw_material'
                        WHEN p.product_id IS NOT NULL THEN 'product'
                    END as type,
                    COALESCE(rm.id, p.product_id) as id,
                    COALESCE(rm.material_code, p.product_code) as code,
                    COALESCE(rm.name, p.name) as name,
                    COALESCE(rm.current_stock, p.current_stock) as current_stock,
                    COALESCE(rm.min_stock_level, p.min_stock_level) as minimum_stock,
                    COALESCE(rm.unit, p.unit) as unit,
                    COALESCE(
                        (
                            SELECT AVG(daily_usage) * 7 + (AVG(daily_usage) * 2)
                            FROM (
                                SELECT 
                                    DATE(created_at) as date,
                                    SUM(CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END) as daily_usage
                                FROM inventory_movement_log
                                WHERE item_type = CASE 
                                        WHEN rm.id IS NOT NULL THEN 'raw_material'
                                        ELSE 'product'
                                    END
                                AND item_id = COALESCE(rm.id, p.product_id)
                                AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                                GROUP BY DATE(created_at)
                                HAVING daily_usage > 0
                            ) usage_data
                        ),
                        COALESCE(rm.min_stock_level, p.min_stock_level) * 1.5
                    ) as reorder_point
                FROM (
                    SELECT * FROM raw_materials 
                    WHERE current_stock <= min_stock_level * ? AND status = 'active'
                ) rm
                LEFT JOIN (
                    SELECT * FROM products 
                    WHERE current_stock <= min_stock_level * ? AND status = 'active'
                ) p ON FALSE";
        
        try {
            $stmt = $this->connect->prepare($query);
            $stmt->bind_param("dd", $this->thresholdBuffer, $this->thresholdBuffer);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error checking low stock items: " . $e->getMessage());
            return false;
        }
    }

    public function calculateReorderPoint($itemType, $itemId) {
        // Get historical data for the last 90 days
        $query = "SELECT 
                    SUM(quantity) as total_quantity,
                    COUNT(DISTINCT DATE(created_at)) as days_with_movement
                FROM inventory_movement_log
                WHERE item_type = ? 
                AND item_id = ?
                AND movement_type = 'out'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
        
        try {
            $stmt = $this->connect->prepare($query);
            $stmt->bind_param("si", $itemType, $itemId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            // Calculate daily usage
            $dailyUsage = $result['total_quantity'] / 90;
            
            // Calculate lead time (assumed 7 days, should be configured per supplier)
            $leadTime = 7;
            
            // Calculate safety stock (2 days worth of stock)
            $safetyStock = $dailyUsage * 2;
            
            // Calculate reorder point
            $reorderPoint = ($dailyUsage * $leadTime) + $safetyStock;
            
            return $reorderPoint;
        } catch (Exception $e) {
            error_log("Error calculating reorder point: " . $e->getMessage());
            return false;
        }
    }

    public function updateThresholds() {
        $items = $this->checkLowStockItems();
        foreach ($items as $item) {
            $reorderPoint = $this->calculateReorderPoint($item['type'], $item['id']);
            if ($reorderPoint) {
                $table = $item['type'] == 'raw_material' ? 'raw_materials' : 'products';
                $idField = $item['type'] == 'raw_material' ? 'id' : 'product_id';
                
                $query = "UPDATE {$table} SET min_stock_level = ? WHERE {$idField} = ?";
                try {
                    $stmt = $this->connect->prepare($query);
                    $stmt->bind_param("di", $reorderPoint, $item['id']);
                    $stmt->execute();
                } catch (Exception $e) {
                    error_log("Error updating threshold: " . $e->getMessage());
                }
            }
        }
    }

    public function sendLowStockAlerts() {
        $items = $this->checkLowStockItems();
        if (!$items) return false;

        $emailBody = "<h2>Low Stock Alert</h2><table border='1'>";
        $emailBody .= "<tr><th>Item</th><th>Current Stock</th><th>Minimum Level</th><th>Status</th></tr>";

        foreach ($items as $item) {
            $status = $this->getStockStatus($item['current_stock'], $item['minimum_stock']);
            $emailBody .= "<tr>";
            $emailBody .= "<td>{$item['name']}</td>";
            $emailBody .= "<td>{$item['current_stock']} {$item['unit']}</td>";
            $emailBody .= "<td>{$item['minimum_stock']} {$item['unit']}</td>";
            $emailBody .= "<td>{$status}</td>";
            $emailBody .= "</tr>";
        }
        $emailBody .= "</table>";

        try {
            $this->mailer->setFrom('inventory@example.com', 'Inventory System');
            $this->mailer->addAddress('manager@example.com', 'Inventory Manager');
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Low Stock Alert - Action Required';
            $this->mailer->Body = $emailBody;
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Error sending low stock alert email: " . $e->getMessage());
            return false;
        }
    }

    private function getStockStatus($currentStock, $minStock) {
        if ($currentStock <= 0) {
            return 'OUT OF STOCK';
        } elseif ($currentStock <= $minStock * 0.5) {
            return 'CRITICAL';
        } else {
            return 'LOW';
        }
    }

    public function getStockTrends($itemType, $itemId, $days = 30) {
        $query = "SELECT 
                    DATE(created_at) as date,
                    SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE -quantity END) as net_change
                FROM inventory_movement_log
                WHERE item_type = ? 
                AND item_id = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        
        try {
            $stmt = $this->connect->prepare($query);
            $stmt->bind_param("sii", $itemType, $itemId, $days);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting stock trends: " . $e->getMessage());
            return false;
        }
    }
} 