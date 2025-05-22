<?php
require_once 'php_action/db_connect.php';
require_once 'includes/header.php';

// Check user permissions

// Initialize date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$account_id = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;

// Fetch initial metrics
try {
    $account_filter = $account_id > 0 ? "AND account_id = ?" : "";
    
    $sql = "SELECT 
            COUNT(*) as total_transactions,
            COALESCE(SUM(amount), 0) as total_amount,
            COUNT(DISTINCT account_id) as active_accounts,
            COUNT(DISTINCT platform_id) as platforms_used,
            COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) as total_deposits,
            COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN ABS(amount) ELSE 0 END), 0) as total_withdrawals
            FROM digitalswap 
            WHERE transaction_date BETWEEN ? AND ?
            $account_filter";

    $stmt = $connect->prepare($sql);
    
    if ($account_id > 0) {
        $stmt->bind_param("ssi", $start_date, $end_date, $account_id);
    } else {
        $stmt->bind_param("ss", $start_date, $end_date);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $metrics = $result->fetch_assoc();

    // Set default values if null
    $metrics = array_merge([
        'total_transactions' => 0,
        'total_amount' => 0,
        'active_accounts' => 0,
        'platforms_used' => 0,
        'total_deposits' => 0,
        'total_withdrawals' => 0
    ], $metrics ?: []);

} catch (Exception $e) {
    error_log("Metrics Error: " . $e->getMessage());
    $metrics = [
        'total_transactions' => 0,
        'total_amount' => 0,
        'active_accounts' => 0,
        'platforms_used' => 0,
        'total_deposits' => 0,
        'total_withdrawals' => 0
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Swap Manager - Account Reporting</title>
    <!-- Bootstrap & jQuery -->
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap-theme.min.css">
    
    <!-- DataTables - Single inclusion -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        .dashboard-header {
            background: #f8f9fa;
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .metric-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin: 10px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .metric-card h5 {
            color: #6c757d;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .metric-card h3 {
            color: #2c3e50;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .chart-container {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 400px;
        }
        
        .chart-title {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .filter-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .table-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-generate {
            background: #3498db;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .btn-generate:hover {
            background: #2980b9;
            color: #fff;
        }
        
        .status-active {
            color: #27ae60;
            font-weight: 600;
        }
        
        .status-inactive {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .action-buttons .btn {
            margin: 0 5px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .metric-card {
                margin: 10px 0;
            }
            
            .chart-container {
                height: 300px;
            }
        }
        
        /* Modern Dashboard Styles */
:root {
    --primary-color: #2196F3;
    --secondary-color: #607D8B;
    --success-color: #4CAF50;
    --warning-color: #FFC107;
    --danger-color: #F44336;
    --light-bg: #f8f9fa;
    --card-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.dashboard-header {
    background: linear-gradient(135deg, #2196F3, #1976D2);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--card-shadow);
    transition: transform 0.2s;
    position: relative;
    overflow: hidden;
}

.metric-card:hover {
    transform: translateY(-5px);
}

.gradient-blue {
    background: linear-gradient(135deg, #2196F3, #1976D2);
    color: white;
}

.metric-icon {
    position: absolute;
    right: -10px;
    top: -10px;
    font-size: 4rem;
    opacity: 0.1;
}

.chart-container {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--card-shadow);
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.chart-actions .btn {
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
}

/* Add more styles as needed */

/* Modern Google Analytics inspired theme */
.analytics-container {
    padding: 20px;
    background: #f8f9fa;
}

.analytics-header {
    background: #1a73e8;
    color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.analytics-header h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 400;
}

.analytics-header p {
    margin: 8px 0 0;
    opacity: 0.8;
    font-size: 14px;
}

.date-badge {
    background: rgba(255,255,255,0.2);
    padding: 4px 12px;
    border-radius: 16px;
    font-size: 13px;
    float: right;
}

/* Stats Cards */
.metric-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.metric-card h5 {
    color: #5f6368;
    margin-bottom: 8px;
    font-size: 14px;
}

.metric-card h3 {
    color: #1a73e8;
    margin: 0;
    font-size: 24px;
    font-weight: 500;
}

.trend-indicator {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    margin-top: 8px;
}

.trend-up { background: #e6f4ea; color: #137333; }
.trend-down { background: #fce8e6; color: #c5221f; }
.trend-neutral { background: #f1f3f4; color: #5f6368; }

/* Filter Section */
.filter-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.form-control {
    border: 1px solid #dadce0;
    border-radius: 4px;
    padding: 8px 12px;
    font-size: 14px;
}

.form-control:focus {
    border-color: #1a73e8;
    box-shadow: 0 0 0 2px rgba(26,115,232,0.2);
}

.btn-primary {
    background: #1a73e8;
    border: none;
    padding: 8px 16px;
    font-size: 14px;
}

/* Charts Section */
.chart-container {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.chart-title {
    color: #202124;
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 16px;
}

/* Period Selector */
.period-selector button {
    background: white;
    border: 1px solid #dadce0;
    padding: 6px 12px;
    font-size: 13px;
    color: #5f6368;
}

.period-selector button.active {
    background: #1a73e8;
    color: white;
    border-color: #1a73e8;
}

/* DataTable Styling */
.table-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.dataTables_wrapper .dataTables_length select,
.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #dadce0;
    border-radius: 4px;
    padding: 4px 8px;
}

.table thead th {
    background: #f8f9fa;
    color: #5f6368;
    font-weight: 500;
    border-bottom: 2px solid #dadce0;
}

.table tbody td {
    border-bottom: 1px solid #dadce0;
    padding: 12px 8px;
    font-size: 14px;
}

/* Status Badges */
.badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-weight: normal;
    font-size: 12px;
}

.bg-success {
    background: #e6f4ea !important;
    color: #137333 !important;
}

.bg-warning {
    background: #fef7e0 !important;
    color: #b06000 !important;
}

/* Action Buttons */
.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

.btn-info {
    background: #e8f0fe;
    color: #1a73e8;
    border: none;
}

.btn-warning {
    background: #fef7e0;
    color: #b06000;
    border: none;
}

/* Modal Styling */
.modal-content {
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.modal-header {
    background: #f8f9fa;
    border-bottom: 1px solid #dadce0;
    padding: 16px 24px;
}

.modal-title {
    color: #202124;
    font-size: 18px;
    font-weight: 500;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .analytics-container {
        padding: 12px;
    }
    
    .metric-card {
        margin-bottom: 16px;
    }
}
    </style>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<div class="analytics-container">
    <!-- Wrap your header in this new structure -->
    <div class="analytics-header">
        <h1>Digital Swap Analytics</h1>
        <p>Track and analyze your digital swap transactions</p>
        <span class="date-badge">Last 30 days</span>
    </div>
    
    <!-- Your existing content remains the same, just add the new classes -->
</div>

<div class="container-fluid">
    <!-- Dashboard Header -->
    

    <!-- Enhanced Filter Section -->
    <div class="container-fluid">
        <div class="filter-card mb-4">
            <form id="reportFilter" method="GET" class="mb-0">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><i class="fas fa-user-circle"></i> Account</label>
                            <select class="form-control select2" name="account_id" id="account_id">
                                <option value="">All Accounts</option>
                                <?php
                                $sql = "SELECT id, account_owner, account_platform FROM accounts ORDER BY account_owner";
                                $result = $connect->query($sql);
                                while ($row = $result->fetch_assoc()) {
                                    $selected = (isset($_GET['account_id']) && $_GET['account_id'] == $row['id']) ? 'selected' : '';
                                    echo "<option value='" . $row['id'] . "' {$selected}>" . 
                                         htmlspecialchars($row['account_owner']) . " (" . 
                                         htmlspecialchars($row['account_platform']) . ")</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Date Range</label>
                            <div class="input-group">
                                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                                <div class="input-group-append input-group-prepend">
                                    <span class="input-group-text">to</span>
                                </div>
                                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-sync-alt"></i> Generate Report
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Enhanced Metrics Cards -->
        <div class="row">
            <div class="col-md-2">
                <div class="metric-card gradient-blue">
                    <div class="metric-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="metric-content">
                        <h5>Transactions</h5>
                        <h3 id="transactions"><?php echo number_format($metrics['total_transactions']); ?></h3>
                        <div class="metric-trend positive">
                            <i class="fas fa-arrow-up"></i> 12.5%
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="metric-card gradient-green">
                    <div class="metric-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="metric-content">
                        <h5>Total Amount</h5>
                        <h3 id="total_amount">$<?php echo number_format($metrics['total_amount'], 2); ?></h3>
                        <div class="metric-trend positive">
                            <i class="fas fa-arrow-up"></i> 10%
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="metric-card gradient-purple">
                    <div class="metric-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="metric-content">
                        <h5>Active Accounts</h5>
                        <h3 id="active_accounts"><?php echo number_format($metrics['active_accounts']); ?></h3>
                        <div class="metric-trend positive">
                            <i class="fas fa-arrow-up"></i> 5%
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="metric-card gradient-orange">
                    <div class="metric-icon">
                        <i class="fas fa-cubes"></i>
                    </div>
                    <div class="metric-content">
                        <h5>Platforms</h5>
                        <h3 id="platforms"><?php echo number_format($metrics['platforms_used']); ?></h3>
                        <div class="metric-trend positive">
                            <i class="fas fa-arrow-up"></i> 8%
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="metric-card gradient-teal">
                    <div class="metric-icon">
                        <i class="fas fa-money-bill-alt"></i>
                    </div>
                    <div class="metric-content">
                        <h5>Total Deposits</h5>
                        <h3 id="total_deposits">$<?php echo number_format($metrics['total_deposits'], 2); ?></h3>
                        <div class="metric-trend positive">
                            <i class="fas fa-arrow-up"></i> 15%
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="metric-card gradient-red">
                    <div class="metric-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="metric-content">
                        <h5>Total Withdrawals</h5>
                        <h3 id="total_withdrawals">$<?php echo number_format($metrics['total_withdrawals'], 2); ?></h3>
                        <div class="metric-trend negative">
                            <i class="fas fa-arrow-down"></i> 5%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Charts Section -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Transaction Volume Over Time</h5>
                        <div class="btn-group mb-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary active" data-period="daily">Daily</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-period="weekly">Weekly</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-period="monthly">Monthly</button>
                        </div>
                        <div style="height: 300px;">
                            <canvas id="transactionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Platform Distribution</h5>
                        <div class="chart-container" style="height: 300px; position: relative;">
                            <canvas id="platformChart"></canvas>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Deposits</small>
                                <span class="badge bg-success">+</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">Withdrawals</small>
                                <span class="badge bg-danger">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Details Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Transaction Details</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="exportTableToCSV()">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
                <div class="table-responsive">
                    <table id="transactionTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Account</th>
                                <th>Platform</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery first, then other dependencies -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables and its dependencies -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.2.2/js/buttons.html5.min.js"></script>

<!-- Custom JavaScript -->
<script>
$(document).ready(function() {
    // Debug log
    console.log('Document ready...');

    // Initialize DataTable
    var table = $('#transactionTable').DataTable({
        processing: true,
        serverSide: false, // Changed to false for simpler implementation
        ajax: {
            url: 'php_action/fetchSwapTransactions.php',
            type: 'GET',
            data: function(d) {
                return {
                    start_date: $('input[name="start_date"]').val(),
                    end_date: $('input[name="end_date"]').val(),
                    account_id: $('#account_id').val()
                };
            },
            dataSrc: function(response) {
                if (response.error) {
                    console.error('Server error:', response.message);
                    return [];
                }
                return response.data || [];
            },
            error: function(xhr, error, thrown) {
                console.error('Ajax error:', error);
                console.error('Server response:', xhr.responseText);
            }
        },
        columns: [
            { data: 'transaction_date' },
            { data: 'account' },
            { data: 'platform' },
            { data: 'type' },
            { data: 'amount' },
            { data: 'status' },
            { data: 'actions' }
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csv',
                text: '<i class="fas fa-download"></i> Export CSV',
                className: 'btn btn-sm btn-outline-secondary d-none',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                }
            }
        ],
        language: {
            processing: "Loading...",
            emptyTable: "No transactions found",
            zeroRecords: "No matching records found"
        }
    });

    // Handle form submission
    $('#reportFilter').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Export CSV function
    window.exportTableToCSV = function() {
        $('.buttons-csv').click();
    };
});
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let transactionChart = null;
let platformChart = null;

function initializeCharts() {
    // Transaction Volume Chart
    const transactionCtx = document.getElementById('transactionChart').getContext('2d');
    transactionChart = new Chart(transactionCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Deposits',
                    borderColor: '#4CAF50',
                    data: []
                },
                {
                    label: 'Withdrawals',
                    borderColor: '#F44336',
                    data: []
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Platform Distribution Chart
    const platformCtx = document.getElementById('platformChart').getContext('2d');
    platformChart = new Chart(platformCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#4CAF50',
                    '#2196F3',
                    '#FFC107',
                    '#9C27B0',
                    '#FF5722'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function updateCharts() {
    const params = {
        start_date: $('input[name="start_date"]').val(),
        end_date: $('input[name="end_date"]').val(),
        account_id: $('#account_id').val(),
        period: $('.btn-group button.active').data('period') || 'daily'
    };

    $.get('php_action/getChartData.php', params)
        .done(function(response) {
            if (response.success) {
                // Update Transaction Volume Chart
                const transactionData = response.transactionData;
                transactionChart.data.labels = transactionData.map(item => item.date);
                transactionChart.data.datasets[0].data = transactionData.map(item => item.deposits);
                transactionChart.data.datasets[1].data = transactionData.map(item => item.withdrawals);
                transactionChart.update();

                // Update Platform Distribution Chart
                const platformData = response.platformData;
                platformChart.data.labels = platformData.map(item => item.platform_name);
                platformChart.data.datasets[0].data = platformData.map(item => item.total_transactions);
                platformChart.update();
            }
        })
        .fail(function(jqXHR, textStatus, error) {
            console.error('Chart update failed:', error);
        });
}

$(document).ready(function() {
    initializeCharts();
    updateCharts();

    // Update charts when form is submitted
    $('#reportFilter').on('submit', function(e) {
        e.preventDefault();
        updateCharts();
        if (typeof table !== 'undefined') {
            table.ajax.reload();
        }
    });

    // Update charts when period buttons are clicked
    $('.btn-group button').on('click', function() {
        $('.btn-group button').removeClass('active');
        $(this).addClass('active');
        updateCharts();
    });
});
</script>

<!-- View Modal -->
<div class="modal" id="viewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">View Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <tr><td>Date:</td><td id="view-date"></td></tr>
                    <tr><td>Account:</td><td id="view-account"></td></tr>
                    <tr><td>Platform:</td><td id="view-platform"></td></tr>
                    <tr><td>Type:</td><td id="view-type"></td></tr>
                    <tr><td>Amount:</td><td id="view-amount"></td></tr>
                    <tr><td>Status:</td><td id="view-status"></td></tr>
                    <tr><td>Comment:</td><td id="view-comment"></td></tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="edit-id">
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" id="edit-date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Platform</label>
                        <input type="text" class="form-control" id="edit-platform" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-control" id="edit-type" required>
                            <option value="deposit">Deposit</option>
                            <option value="withdraw">Withdraw</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" class="form-control" id="edit-amount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-control" id="edit-status" required>
                            <option value="1">Completed</option>
                            <option value="0">Pending</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comment</label>
                        <textarea class="form-control" id="edit-comment"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveTransaction()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Add this just before closing </body> tag -->
<script>
// Initialize modals
const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
const editModal = new bootstrap.Modal(document.getElementById('editModal'));

function viewTransaction(id) {
    $.get('php_action/transactionOperations.php', {
        action: 'view',
        id: id
    })
    .done(function(response) {
        if (response.success) {
            const data = response.data;
            $('#view-date').text(data.transaction_date);
            $('#view-account').text(data.account_owner);
            $('#view-platform').text(data.platform);
            $('#view-type').text(data.type);
            $('#view-amount').text('$' + Math.abs(data.amount));
            $('#view-status').text(data.status === '1' ? 'Completed' : 'Pending');
            $('#view-comment').text(data.comment || 'No comment');
            viewModal.show();
        } else {
            alert('Failed to load transaction details');
        }
    })
    .fail(function(error) {
        console.error('Error:', error);
        alert('Failed to load transaction details');
    });
}

function editTransaction(id) {
    $.get('php_action/transactionOperations.php', {
        action: 'view',
        id: id
    })
    .done(function(response) {
        if (response.success) {
            const data = response.data;
            $('#edit-id').val(data.id);
            $('#edit-date').val(data.transaction_date);
            $('#edit-platform').val(data.platform);
            $('#edit-type').val(data.type);
            $('#edit-amount').val(Math.abs(data.amount));
            $('#edit-status').val(data.status);
            $('#edit-comment').val(data.comment);
            editModal.show();
        } else {
            alert('Failed to load transaction details');
        }
    })
    .fail(function(error) {
        console.error('Error:', error);
        alert('Failed to load transaction details');
    });
}

function saveTransaction() {
    const data = {
        id: $('#edit-id').val(),
        transaction_date: $('#edit-date').val(),
        platform: $('#edit-platform').val(),
        type: $('#edit-type').val(),
        amount: $('#edit-amount').val(),
        status: $('#edit-status').val(),
        comment: $('#edit-comment').val()
    };

    $.ajax({
        url: 'php_action/transactionOperations.php?action=update',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                editModal.hide();
                table.ajax.reload();
                alert('Transaction updated successfully');
            } else {
                alert('Failed to update transaction');
            }
        },
        error: function(error) {
            console.error('Error:', error);
            alert('Failed to update transaction');
        }
    });
}
</script>

</body>
</html> 