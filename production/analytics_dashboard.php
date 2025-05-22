<?php
require_once 'includes/header.php';
require_once 'php_action/classes/ResourceManager.php';
require_once 'php_action/classes/ProductionPredictor.php';
require_once 'php_action/classes/ProductionAnalytics.php';

$analytics = new Production\ProductionAnalytics($connect);

// Get date range from request or use default
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 month'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Generate analytics report
$report = $analytics->generateAnalyticsReport($startDate, $endDate);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Analytics Dashboard</title>
    
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Include DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .metric-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
        }
        .dashboard-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
        }
        .metric-label {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <h1>Production Analytics Dashboard</h1>
            <p class="mb-0">Real-time insights and performance metrics</p>
        </div>
    </div>

    <div class="container mb-4">
        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form class="row g-3" method="GET">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card metric-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">On-Time Completion</h5>
                        <div class="metric-value"><?php echo number_format($report['efficiency_metrics']['efficiency_metrics']['on_time_completion_rate'], 1); ?>%</div>
                        <div class="metric-label">Orders completed on schedule</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Completion Rate</h5>
                        <div class="metric-value"><?php echo number_format($report['efficiency_metrics']['efficiency_metrics']['completion_rate'], 1); ?>%</div>
                        <div class="metric-label">Target quantity achieved</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Orders</h5>
                        <div class="metric-value"><?php echo number_format($report['efficiency_metrics']['efficiency_metrics']['total_orders']); ?></div>
                        <div class="metric-label">Orders processed</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Avg. Production Time</h5>
                        <div class="metric-value"><?php echo number_format($report['efficiency_metrics']['efficiency_metrics']['avg_production_time'] / 60, 1); ?>h</div>
                        <div class="metric-label">Per order</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Production Trends Chart -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Production Trends</h5>
                        <div class="chart-container">
                            <canvas id="productionTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Resource Utilization Chart -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Resource Utilization</h5>
                        <div class="chart-container">
                            <canvas id="resourceUtilizationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottleneck Analysis -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Bottleneck Analysis</h5>
                <div class="table-responsive">
                    <table class="table table-striped" id="bottleneckTable">
                        <thead>
                            <tr>
                                <th>Workstation</th>
                                <th>Total Orders</th>
                                <th>Delayed Orders</th>
                                <th>Avg. Delay</th>
                                <th>Utilization Rate</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['bottleneck_analysis'] as $bottleneck): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($bottleneck['workstation_name']); ?></td>
                                <td><?php echo $bottleneck['metrics']['total_orders']; ?></td>
                                <td><?php echo $bottleneck['metrics']['delayed_orders']; ?></td>
                                <td><?php echo number_format($bottleneck['metrics']['avg_delay_hours'], 1); ?>h</td>
                                <td><?php echo number_format($bottleneck['metrics']['utilization_rate'], 1); ?>%</td>
                                <td>
                                    <?php
                                    $status = '';
                                    if ($bottleneck['metrics']['utilization_rate'] > 90) {
                                        $status = '<span class="badge bg-danger">Critical</span>';
                                    } elseif ($bottleneck['metrics']['utilization_rate'] > 75) {
                                        $status = '<span class="badge bg-warning">Warning</span>';
                                    } else {
                                        $status = '<span class="badge bg-success">Normal</span>';
                                    }
                                    echo $status;
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quality Metrics -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Quality Metrics</h5>
                <div class="table-responsive">
                    <table class="table table-striped" id="qualityTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Total Orders</th>
                                <th>Defect Rate</th>
                                <th>Quality Score</th>
                                <th>High Defect Orders</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['quality_metrics'] as $quality): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($quality['product_name']); ?></td>
                                <td><?php echo $quality['metrics']['total_orders']; ?></td>
                                <td><?php echo number_format($quality['metrics']['avg_defect_rate'], 2); ?>%</td>
                                <td><?php echo number_format($quality['metrics']['avg_quality_score'], 1); ?></td>
                                <td><?php echo $quality['metrics']['high_defect_orders']; ?></td>
                                <td>
                                    <?php
                                    $status = '';
                                    if ($quality['metrics']['avg_defect_rate'] > 5) {
                                        $status = '<span class="badge bg-danger">Action Required</span>';
                                    } elseif ($quality['metrics']['avg_defect_rate'] > 2) {
                                        $status = '<span class="badge bg-warning">Monitor</span>';
                                    } else {
                                        $status = '<span class="badge bg-success">Good</span>';
                                    }
                                    echo $status;
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Bootstrap JS and jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Include DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        // Initialize DataTables
        $(document).ready(function() {
            $('#bottleneckTable').DataTable();
            $('#qualityTable').DataTable();
        });

        // Production Trends Chart
        const trendsCtx = document.getElementById('productionTrendsChart').getContext('2d');
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($report['production_trends'], 'date')); ?>,
                datasets: [{
                    label: 'Completed Orders',
                    data: <?php echo json_encode(array_column(array_column($report['production_trends'], 'metrics'), 'completed_orders')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }, {
                    label: 'On-Time Orders',
                    data: <?php echo json_encode(array_column(array_column($report['production_trends'], 'metrics'), 'on_time_orders')); ?>,
                    borderColor: 'rgb(54, 162, 235)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Resource Utilization Chart
        const utilizationCtx = document.getElementById('resourceUtilizationChart').getContext('2d');
        new Chart(utilizationCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($report['resource_performance'], 'workstation_name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column(array_column($report['resource_performance'], 'metrics'), 'utilization_rate')); ?>,
                    backgroundColor: [
                        'rgb(255, 99, 132)',
                        'rgb(54, 162, 235)',
                        'rgb(255, 205, 86)',
                        'rgb(75, 192, 192)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>

<?php require_once 'includes/footer.php'; ?> 