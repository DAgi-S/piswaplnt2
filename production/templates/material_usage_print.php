<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Usage Report - Print View</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }
            body {
                font-size: 12pt;
            }
            .no-print {
                display: none;
            }
            .page-break {
                page-break-before: always;
            }
            .chart-container {
                break-inside: avoid;
            }
            .summary-card {
                break-inside: avoid;
            }
        }
        body {
            padding: 20px;
        }
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-top: 20px;
        }
        .company-logo {
            max-width: 150px;
            margin-bottom: 15px;
        }
        .summary-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .chart-container {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 300px;
        }
        .table-container {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .filter-info {
            margin-bottom: 20px;
            font-size: 0.9em;
            color: #666;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            font-style: italic;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Print Controls (only visible on screen) -->
        <div class="no-print text-end mb-3">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fa fa-print"></i> Print Report
            </button>
            <a href="material_usage_report.php" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Report
            </a>
        </div>

        <!-- Report Header -->
        <div class="report-header">
            <h2>Material Usage Report</h2>
            <p class="filter-info">
                Period: <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?><br>
                <?php if ($material_id > 0): ?>
                Material: <?php 
                    $material_name = "All Materials";
                    if ($materials_result) {
                        $materials_result->data_seek(0);
                        while ($material = $materials_result->fetch_assoc()) {
                            if ($material['material_id'] == $material_id) {
                                $material_name = $material['name'];
                                break;
                            }
                        }
                    }
                    echo htmlspecialchars($material_name); 
                ?><br>
                <?php endif; ?>
                <?php if ($category_id > 0): ?>
                Category: <?php 
                    $category_name = "All Categories";
                    if ($categories_result) {
                        $categories_result->data_seek(0);
                        while ($category = $categories_result->fetch_assoc()) {
                            if ($category['category_id'] == $category_id) {
                                $category_name = $category['name'];
                                break;
                            }
                        }
                    }
                    echo htmlspecialchars($category_name); 
                ?><br>
                <?php endif; ?>
                Generated on: <?php echo date('Y-m-d H:i:s'); ?>
            </p>
        </div>

        <!-- Summary Cards Row -->
        <div class="row">
            <div class="col-md-3">
                <div class="summary-card">
                    <h4>Total Materials Used</h4>
                    <div class="h2"><?php echo number_format($summary['total_materials']); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <h4>Low Stock Materials</h4>
                    <div class="h2"><?php echo number_format($summary['low_stock_count']); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <h4>Total Quantity Used</h4>
                    <div class="h2"><?php echo number_format($summary['total_quantity_used'], 2); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <h4>Total Production Orders</h4>
                    <div class="h2"><?php echo number_format($summary['total_orders']); ?></div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <!-- Material Usage by Category Chart -->
            <div class="col-md-6">
                <div class="chart-container">
                    <?php if (!empty($category_stats)): ?>
                    <canvas id="categoryChart"></canvas>
                    <?php else: ?>
                    <div class="no-data">No category data available for the selected period</div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Monthly Usage Trend Chart -->
            <div class="col-md-6">
                <div class="chart-container">
                    <?php if (!empty($monthly_trend)): ?>
                    <canvas id="trendChart"></canvas>
                    <?php else: ?>
                    <div class="no-data">No trend data available for the selected period</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Material Usage Table -->
        <div class="table-container">
            <h4>Material Usage Details</h4>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Material Code</th>
                        <th>Material Name</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Quantity</th>
                        <th>Production Order</th>
                        <th>Date</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result && $result->num_rows > 0) {
                        // Reset result pointer to beginning
                        $result->data_seek(0);
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['material_code']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['material_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['category_name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['unit']) . '</td>';
                            echo '<td>' . number_format($row['quantity_consumed'], 2) . '</td>';
                            echo '<td>' . htmlspecialchars($row['order_number']) . '</td>';
                            echo '<td>' . date('Y-m-d', strtotime($row['created_at'])) . '</td>';
                            echo '<td>' . htmlspecialchars($row['created_by_name']) . '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="8" class="text-center">No data found for the selected criteria</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare and validate data for charts
        <?php if (!empty($category_stats)): ?>
        const categoryLabels = <?php 
            $labels = [];
            foreach ($category_stats as $category) {
                $labels[] = $category['category_name'];
            }
            echo json_encode($labels); 
        ?>;
        
        const categoryValues = <?php 
            $values = [];
            foreach ($category_stats as $category) {
                $values[] = floatval($category['total_quantity']);
            }
            echo json_encode($values); 
        ?>;

        // Initialize Category Chart if we have data
        if (document.getElementById('categoryChart')) {
            new Chart(document.getElementById('categoryChart'), {
                type: 'pie',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryValues,
                        backgroundColor: [
                            '#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF',
                            '#FF9F40', '#FF6384', '#C9CBCF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Material Usage by Category'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
        <?php endif; ?>

        <?php if (!empty($monthly_trend)): ?>
        const trendLabels = <?php 
            $labels = [];
            foreach ($monthly_trend as $trend) {
                $month = date('M Y', strtotime($trend['month'] . '-01'));
                $labels[] = $month;
            }
            echo json_encode($labels); 
        ?>;
        
        const trendValues = <?php 
            $values = [];
            foreach ($monthly_trend as $trend) {
                $values[] = floatval($trend['quantity']);
            }
            echo json_encode($values); 
        ?>;

        // Initialize Trend Chart if we have data
        if (document.getElementById('trendChart')) {
            new Chart(document.getElementById('trendChart'), {
                type: 'bar',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Monthly Usage',
                        data: trendValues,
                        backgroundColor: '#36A2EB'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Usage Trend'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Quantity'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
    });
    </script>
</body>
</html>