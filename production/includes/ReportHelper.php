<?php
class ReportHelper {
    private static $instance = null;
    private $db;
    private $cache = [];
    
    private function __construct() {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getDateRange($default_days = 30) {
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
        
        return [
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
    }
    
    public function getMaterials($force_refresh = false) {
        $cache_key = 'materials_list';
        
        if (!$force_refresh && isset($this->cache[$cache_key])) {
            return $this->cache[$cache_key];
        }
        
        $sql = "SELECT material_id, code, name, unit FROM raw_materials ORDER BY name";
        $result = $this->db->query($sql, [], true);
        
        $this->cache[$cache_key] = $result;
        return $result;
    }
    
    public function getCategories($type = 'material', $force_refresh = false) {
        $cache_key = 'categories_' . $type;
        
        if (!$force_refresh && isset($this->cache[$cache_key])) {
            return $this->cache[$cache_key];
        }
        
        $table = $type === 'material' ? 'raw_material_categories' : 'product_categories';
        $sql = "SELECT category_id, name FROM {$table} ORDER BY name";
        $result = $this->db->query($sql, [], true);
        
        $this->cache[$cache_key] = $result;
        return $result;
    }
    
    public function getProducts($force_refresh = false) {
        $cache_key = 'products_list';
        
        if (!$force_refresh && isset($this->cache[$cache_key])) {
            return $this->cache[$cache_key];
        }
        
        $sql = "SELECT product_id, code, name FROM products ORDER BY name";
        $result = $this->db->query($sql, [], true);
        
        $this->cache[$cache_key] = $result;
        return $result;
    }
    
    public function formatNumber($number, $decimals = 2) {
        return number_format($number, $decimals);
    }
    
    public function getStockStatus($current_stock, $minimum_stock) {
        $stock_ratio = $current_stock / max(1, $minimum_stock);
        
        if ($stock_ratio <= 0.5) {
            return [
                'class' => 'status-danger',
                'text' => 'Critical'
            ];
        } elseif ($stock_ratio <= 1) {
            return [
                'class' => 'status-warning',
                'text' => 'Low'
            ];
        } else {
            return [
                'class' => 'status-success',
                'text' => 'Good'
            ];
        }
    }
    
    public function getQualityStatus($rejection_rate) {
        if ($rejection_rate >= 10) {
            return [
                'class' => 'status-danger',
                'text' => 'Poor'
            ];
        } elseif ($rejection_rate >= 5) {
            return [
                'class' => 'status-warning',
                'text' => 'Fair'
            ];
        } else {
            return [
                'class' => 'status-success',
                'text' => 'Good'
            ];
        }
    }
    
    public function buildFilterConditions($filters) {
        $conditions = [];
        $params = [];
        
        foreach ($filters as $field => $value) {
            if ($value !== '' && $value !== 'all' && $value !== 0) {
                $conditions[] = $field . ' = ?';
                $params[] = $value;
            }
        }
        
        return [
            'conditions' => $conditions,
            'params' => $params
        ];
    }
    
    public function clearCache() {
        $this->cache = [];
    }
    
    public function getCommonStyles() {
        return '
        <style>
            .status-badge {
                padding: 5px 10px;
                border-radius: 4px;
                font-weight: bold;
            }
            .status-danger {
                background-color: #ffebee;
                color: #c62828;
            }
            .status-warning {
                background-color: #fff3e0;
                color: #ef6c00;
            }
            .status-success {
                background-color: #e8f5e9;
                color: #2e7d32;
            }
            .summary-card {
                background-color: #fff;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .summary-card h4 {
                margin-top: 0;
                color: #666;
            }
            .summary-card .value {
                font-size: 24px;
                font-weight: bold;
                color: #333;
            }
            .filter-card {
                background-color: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
            }
        </style>';
    }
    
    public function getCommonScripts() {
        return '
        <script>
            $(document).ready(function() {
                $(".select2").select2({
                    theme: "bootstrap4",
                    width: "100%"
                });
                
                $("[data-toggle=\'tooltip\']").tooltip();
                
                // Initialize DataTables with common settings
                $(".datatable").DataTable({
                    pageLength: ' . ITEMS_PER_PAGE . ',
                    responsive: true,
                    dom: "Bfrtip",
                    buttons: [
                        "copy",
                        {
                            extend: "excel",
                            title: document.title
                        },
                        {
                            extend: "pdf",
                            title: document.title
                        },
                        "print"
                    ]
                });
            });
        </script>';
    }
    
    public function getTrendData($sql, $params, $interval = 'month', $value_field = 'value', $date_field = 'date') {
        $result = $this->db->query($sql, $params, true);
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $date = new DateTime($row[$date_field]);
            $key = $interval === 'month' ? $date->format('Y-m') : $date->format('Y-m-d');
            
            if (!isset($data[$key])) {
                $data[$key] = 0;
            }
            $data[$key] += floatval($row[$value_field]);
        }
        
        // Fill in missing periods
        $start = new DateTime(min(array_keys($data)));
        $end = new DateTime(max(array_keys($data)));
        $interval = new DateInterval($interval === 'month' ? 'P1M' : 'P1D');
        $period = new DatePeriod($start, $interval, $end);
        
        foreach ($period as $date) {
            $key = $interval === 'month' ? $date->format('Y-m') : $date->format('Y-m-d');
            if (!isset($data[$key])) {
                $data[$key] = 0;
            }
        }
        
        ksort($data);
        return $data;
    }
    
    public function calculateTrend($data) {
        if (count($data) < 2) {
            return 0;
        }
        
        $x = array_keys(array_values($data));
        $y = array_values($data);
        
        $n = count($data);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumXX = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumXX += $x[$i] * $x[$i];
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
        $last_value = end($y);
        
        if ($last_value == 0) {
            return 0;
        }
        
        return ($slope / $last_value) * 100;
    }
    
    public function getUsageTrend($material_id, $start_date, $end_date) {
        $sql = "SELECT 
                DATE(po.start_date) as date,
                SUM(pom.quantity_used) as value
            FROM production_order_materials pom
            JOIN production_orders po ON pom.production_order_id = po.order_id
            WHERE pom.material_id = ? 
            AND po.start_date BETWEEN ? AND ?
            GROUP BY DATE(po.start_date)
            ORDER BY date";
            
        return $this->getTrendData($sql, [$material_id, $start_date, $end_date], 'day', 'value', 'date');
    }
    
    public function getStockTrend($material_id, $start_date, $end_date) {
        $sql = "SELECT 
                DATE(created_at) as date,
                current_stock as value
            FROM raw_material_movements
            WHERE material_id = ?
            AND created_at BETWEEN ? AND ?
            ORDER BY date";
            
        return $this->getTrendData($sql, [$material_id, $start_date, $end_date], 'day', 'value', 'date');
    }
    
    public function getTrendIndicator($trend_value) {
        if ($trend_value > 10) {
            return [
                'class' => 'text-success',
                'icon' => 'fa-arrow-up',
                'text' => 'Increasing'
            ];
        } elseif ($trend_value < -10) {
            return [
                'class' => 'text-danger',
                'icon' => 'fa-arrow-down',
                'text' => 'Decreasing'
            ];
        } else {
            return [
                'class' => 'text-warning',
                'icon' => 'fa-arrows-h',
                'text' => 'Stable'
            ];
        }
    }
} 