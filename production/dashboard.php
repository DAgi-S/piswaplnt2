<?php 
require_once 'includes/header.php';
require_once 'php_action/production_middleware.php';

// Initialize production middleware
$productionMiddleware = new ProductionMiddleware($connect);

// Check both new and legacy permissions
$permissions = [
    // New permission format
    'view' => $productionMiddleware->checkPermission('dashboard.view'),
    'customize' => $productionMiddleware->checkPermission('dashboard.customize'),
    'analytics_export' => $productionMiddleware->checkPermission('dashboard.analytics.export'),
    'reports_schedule' => $productionMiddleware->checkPermission('dashboard.reports.schedule'),
    'reports_view' => $productionMiddleware->checkPermission('dashboard.reports.view'),
    'digitalswap_view' => $productionMiddleware->checkPermission('digitalswap.view'),
    'production_view' => $productionMiddleware->checkPermission('production.view'),
    
    // Legacy permissions
    'legacy_analytics' => $productionMiddleware->checkPermission('view_analytics'),
    'legacy_dashboard' => $productionMiddleware->checkPermission('view_dashboard'),
    'legacy_low_stock' => $productionMiddleware->checkPermission('view_low_stock'),
    'legacy_revenue' => $productionMiddleware->checkPermission('view_revenue')
];

// Check if user has either new or legacy permission to view dashboard
if (!($permissions['view'] || $permissions['legacy_dashboard'])) {
    echo '<div class="alert alert-danger">You do not have permission to view the dashboard.</div>';
    exit();
}

// Fetch summary data
$totalMaterials = $connect->query("SELECT COUNT(*) as count FROM raw_materials WHERE status = 'active'")->fetch_assoc()['count'];
$lowStockCount = $connect->query("SELECT COUNT(*) as count FROM raw_materials WHERE status = 'active' AND current_stock <= min_stock_level")->fetch_assoc()['count'];
$outOfStockCount = $connect->query("SELECT COUNT(*) as count FROM raw_materials WHERE status = 'active' AND current_stock = 0")->fetch_assoc()['count'];
$totalCategories = $connect->query("SELECT COUNT(*) as count FROM raw_material_categories WHERE status = 'active'")->fetch_assoc()['count'];

// Get recent stock movements
$recentMovements = $connect->query("
    SELECT 
        rmm.created_at,
        rm.name as material_name,
        rmm.movement_type,
        rmm.quantity
    FROM raw_material_movements rmm
    JOIN raw_materials rm ON rmm.material_id = rm.id
    WHERE rm.status = 'active'
    ORDER BY rmm.created_at DESC
    LIMIT 5
");

// Get critical stock items
$criticalStock = $connect->query("
    SELECT 
        rm.name,
        rm.current_stock,
        rm.min_stock_level,
        rm.unit,
        rmc.name as category_name
    FROM raw_materials rm
    LEFT JOIN raw_material_categories rmc ON rm.category_id = rmc.id
    WHERE rm.status = 'active' 
    AND (rm.current_stock = 0 OR rm.current_stock <= rm.min_stock_level * 0.5)
    ORDER BY 
        CASE 
            WHEN rm.current_stock = 0 THEN 1
            ELSE 2
        END,
        rm.current_stock ASC
    LIMIT 5
");

// Function to get stock movement trends
function getStockMovementTrends($connect) {
    $query = "
        SELECT 
            DATE(rmm.created_at) as movement_date,
            SUM(CASE WHEN rmm.movement_type = 'in' THEN rmm.quantity ELSE 0 END) as inbound,
            SUM(CASE WHEN rmm.movement_type = 'out' THEN rmm.quantity ELSE 0 END) as outbound
        FROM raw_material_movements rmm
        JOIN raw_materials rm ON rmm.material_id = rm.id
        WHERE rm.status = 'active'
        AND rmm.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(rmm.created_at)
        ORDER BY movement_date ASC
    ";
    
    return $connect->query($query);
}

// Function to get stock status distribution
function getStockStatusDistribution($connect) {
    $query = "
        SELECT 
            CASE 
                WHEN current_stock = 0 THEN 'Out of Stock'
                WHEN current_stock <= min_stock_level THEN 'Low Stock'
                ELSE 'Normal Stock'
            END as stock_status,
            COUNT(*) as count
        FROM raw_materials
        WHERE status = 'active'
        GROUP BY 
            CASE 
                WHEN current_stock = 0 THEN 'Out of Stock'
                WHEN current_stock <= min_stock_level THEN 'Low Stock'
                ELSE 'Normal Stock'
            END
    ";
    
    return $connect->query($query);
}

// Get data for charts
$stockMovementTrends = getStockMovementTrends($connect);
$stockStatusDistribution = getStockStatusDistribution($connect);

// Convert results to JSON for JavaScript
$movementTrendsData = [];
while ($row = $stockMovementTrends->fetch_assoc()) {
    $movementTrendsData[] = $row;
}

$statusDistributionData = [];
while ($row = $stockStatusDistribution->fetch_assoc()) {
    $statusDistributionData[] = $row;
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <ol class="breadcrumb">
                <li><a href="dashboard.php">Home</a></li>
                <li class="active">Dashboard</li>
            </ol>

            <!-- Summary Cards -->
            <?php if ($permissions['view'] || $permissions['legacy_dashboard']): ?>
            <div class="row">
                <div class="col-md-3">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-xs-3">
                                    <i class="fa fa-cubes fa-4x"></i>
                                </div>
                                <div class="col-xs-9 text-right">
                                    <div class="huge"><?php echo $totalMaterials; ?></div>
                                    <div>Total Materials</div>
                                </div>
                            </div>
                        </div>
                        <?php if ($permissions['legacy_low_stock'] || $permissions['view']): ?>
                        <a href="raw_materials.php">
                            <div class="panel-footer">
                                <span class="pull-left">View Details</span>
                                <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="panel panel-red">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-xs-3">
                                    <i class="fa fa-exclamation-triangle fa-4x"></i>
                                </div>
                                <div class="col-xs-9 text-right">
                                    <div class="huge"><?php echo $outOfStockCount; ?></div>
                                    <div>Out of Stock</div>
                                </div>
                            </div>
                        </div>
                        <?php if ($permissions['legacy_low_stock'] || $permissions['view']): ?>
                        <a href="low_stock.php">
                            <div class="panel-footer">
                                <span class="pull-left">View Details</span>
                                <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="panel panel-yellow">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-xs-3">
                                    <i class="fa fa-warning fa-4x"></i>
                                </div>
                                <div class="col-xs-9 text-right">
                                    <div class="huge"><?php echo $lowStockCount; ?></div>
                                    <div>Low Stock Items</div>
                                </div>
                            </div>
                        </div>
                        <?php if ($permissions['legacy_low_stock'] || $permissions['view']): ?>
                        <a href="low_stock.php">
                            <div class="panel-footer">
                                <span class="pull-left">View Details</span>
                                <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="panel panel-green">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-xs-3">
                                    <i class="fa fa-tags fa-4x"></i>
                                </div>
                                <div class="col-xs-9 text-right">
                                    <div class="huge"><?php echo $totalCategories; ?></div>
                                    <div>Categories</div>
                                </div>
                            </div>
                        </div>
                        <?php if ($permissions['view']): ?>
                        <a href="raw_material_categories.php">
                            <div class="panel-footer">
                                <span class="pull-left">View Details</span>
                                <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Charts Row -->
            <?php if ($permissions['legacy_analytics'] || $permissions['reports_view']): ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-bar-chart"></i> Stock Movement Trends</h3>
                        </div>
                        <div class="panel-body">
                            <canvas id="stockMovementChart" width="100%" height="50"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-pie-chart"></i> Stock Status Distribution</h3>
                        </div>
                        <div class="panel-body">
                            <canvas id="stockStatusChart" width="100%" height="50"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Activities and Alerts Row -->
            <?php if ($permissions['view'] || $permissions['legacy_dashboard']): ?>
            <div class="row">
                <!-- Recent Stock Movements -->
                <div class="col-md-6">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-exchange"></i> Recent Stock Movements</h3>
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Material</th>
                                            <th>Type</th>
                                            <th>Quantity</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($movement = $recentMovements->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($movement['created_at'])); ?></td>
                                            <td><?php echo $movement['material_name']; ?></td>
                                            <td>
                                                <span class="label <?php echo $movement['movement_type'] === 'in' ? 'label-success' : 'label-warning'; ?>">
                                                    <?php echo strtoupper($movement['movement_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($movement['quantity'], 2); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if ($permissions['view']): ?>
                            <div class="text-right">
                                <a href="stock_movements.php">View All Movements <i class="fa fa-arrow-circle-right"></i></a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Critical Stock Alerts -->
                <?php if ($permissions['legacy_low_stock'] || $permissions['view']): ?>
                <div class="col-md-6">
                    <div class="panel panel-danger">
                        <div class="panel-heading">
                            <h3 class="panel-title"><i class="fa fa-exclamation-circle"></i> Critical Stock Alerts</h3>
                        </div>
                        <div class="panel-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Material</th>
                                            <th>Category</th>
                                            <th>Current Stock</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($item = $criticalStock->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $item['name']; ?></td>
                                            <td><?php echo $item['category_name']; ?></td>
                                            <td><?php echo number_format($item['current_stock'], 2) . ' ' . $item['unit']; ?></td>
                                            <td>
                                                <span class="label <?php echo $item['current_stock'] == 0 ? 'label-danger' : 'label-warning'; ?>">
                                                    <?php echo $item['current_stock'] == 0 ? 'OUT OF STOCK' : 'CRITICAL'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-right">
                                <a href="low_stock.php">View All Low Stock <i class="fa fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
    :root {
        /* Primary Brand Colors */
        --primary-blue: #1976D2;
        --primary-dark: #1565C0;
        --primary-light: #42A5F5;
        
        /* Accent Colors */
        --accent-purple: #7E57C2;
        --accent-teal: #26A69A;
        
        /* Status Colors */
        --success-green: #2E7D32;
        --success-light: #4CAF50;
        --warning-yellow: #FFA000;
        --warning-light: #FFB74D;
        --danger-red: #D32F2F;
        --danger-light: #EF5350;
        
        /* Neutral Colors */
        --background-light: #F5F7FA;
        --background-white: #FFFFFF;
        --text-primary: #2C3E50;
        --text-secondary: #546E7A;
        --text-light: #FFFFFF;
        --border-color: #E0E6ED;
        
        /* Component Variables */
        --border-radius: 12px;
        --box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        --box-shadow-hover: 0 4px 20px rgba(0,0,0,0.12);
        --transition: all 0.3s ease;
    }

    body {
        background-color: var(--background-light);
        color: var(--text-primary);
    }

    .container {
        padding: 25px;
        max-width: 1400px;
        margin: 0 auto;
    }

    /* Panel Styles */
    .panel {
        background-color: var(--background-white);
        border: none;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        transition: var(--transition);
        margin-bottom: 25px;
    }

    .panel:hover {
        transform: translateY(-2px);
        box-shadow: var(--box-shadow-hover);
    }

    /* Summary Cards */
    .panel-primary {
        background: linear-gradient(135deg, var(--primary-blue), var(--primary-dark));
    }

    .panel-primary .panel-heading {
        background: transparent;
        border: none;
    }

    .panel-green {
        background: linear-gradient(135deg, var(--success-green), var(--success-light));
    }

    .panel-green .panel-heading {
        background: transparent;
        border: none;
    }

    .panel-red {
        background: linear-gradient(135deg, var(--danger-red), var(--danger-light));
    }

    .panel-red .panel-heading {
        background: transparent;
        border: none;
    }

    .panel-yellow {
        background: linear-gradient(135deg, var(--warning-yellow), var(--warning-light));
    }

    .panel-yellow .panel-heading {
        background: transparent;
        border: none;
    }

    /* Chart Panels */
    .panel-default {
        background-color: var(--background-white);
    }

    .panel-default .panel-heading {
        background-color: var(--background-white);
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        font-weight: 600;
    }

    /* Tables */
    .table > thead > tr > th {
        background-color: var(--background-light);
        color: var(--text-primary);
        border-bottom: 2px solid var(--border-color);
        font-weight: 600;
    }

    .table-striped > tbody > tr:nth-of-type(odd) {
        background-color: var(--background-light);
    }

    .table-hover > tbody > tr:hover {
        background-color: rgba(25, 118, 210, 0.05);
    }

    /* Status Labels */
    .label {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 20px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .label-success {
        background-color: var(--success-light);
    }

    .label-warning {
        background-color: var(--warning-light);
        color: var(--text-primary);
    }

    .label-danger {
        background-color: var(--danger-light);
    }

    /* Links and Buttons */
    .panel-footer {
        background-color: var(--background-light);
        border-top: 1px solid var(--border-color);
        padding: 15px 20px;
    }

    .panel-footer a {
        color: var(--primary-blue);
        font-weight: 500;
    }

    .panel-footer a:hover {
        color: var(--primary-dark);
        text-decoration: none;
    }

    /* Breadcrumb */
    .breadcrumb {
        background-color: var(--background-white);
        box-shadow: var(--box-shadow);
        border-radius: var(--border-radius);
        padding: 15px 25px;
        margin-bottom: 25px;
    }

    .breadcrumb > li + li:before {
        color: var(--text-secondary);
    }

    .breadcrumb a {
        color: var(--primary-blue);
    }

    /* Chart Colors */
    canvas {
        background-color: var(--background-white);
        border-radius: var(--border-radius);
        padding: 10px;
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .container {
            padding: 15px;
        }

        .panel {
            margin-bottom: 20px;
        }

        .breadcrumb {
            padding: 12px 20px;
            margin-bottom: 20px;
        }
    }
</style>

<?php if ($permissions['legacy_analytics'] || $permissions['reports_view']): ?>
<script>
// Stock Movement Trends Chart
var movementTrendsData = <?php echo json_encode($movementTrendsData); ?>;
var statusDistributionData = <?php echo json_encode($statusDistributionData); ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Stock Movement Trends Chart
    var trendCtx = document.getElementById('stockMovementChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: movementTrendsData.map(item => item.movement_date),
            datasets: [{
                label: 'Inbound',
                data: movementTrendsData.map(item => item.inbound),
                borderColor: '#5cb85c',
                fill: false
            }, {
                label: 'Outbound',
                data: movementTrendsData.map(item => item.outbound),
                borderColor: '#d9534f',
                fill: false
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Stock Status Distribution Chart
    var statusCtx = document.getElementById('stockStatusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: statusDistributionData.map(item => item.stock_status),
            datasets: [{
                data: statusDistributionData.map(item => item.count),
                backgroundColor: [
                    '#d9534f',  // Out of Stock - Red
                    '#f0ad4e',  // Low Stock - Yellow
                    '#5cb85c'   // Normal Stock - Green
                ]
            }]
        },
        options: {
            responsive: true
        }
    });
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?> 