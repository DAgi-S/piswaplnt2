<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';
?>

<style>
    /* Dashboard Styles */
    .dashboard-card {
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        transition: transform 0.2s;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
    }
    .metric-card {
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .metric-value {
        font-size: 24px;
        font-weight: bold;
        margin: 10px 0;
    }
    .metric-label {
        color: #666;
        font-size: 14px;
    }
    .chart-container {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        position: relative;
        min-height: 300px;
    }
    .filter-section {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .report-card {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .report-card:hover {
        background-color: #f8f9fa;
    }
    .report-icon {
        font-size: 24px;
        margin-bottom: 10px;
    }
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Report Dashboard</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fas fa-chart-line"></i> Report Dashboard
                </div>
            </div>
            <div class="panel-body">
                <!-- Quick Stats Section -->
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="metric-card">
                            <div class="metric-value" id="totalOrders">0</div>
                            <div class="metric-label">Total Orders</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="metric-card">
                            <div class="metric-value" id="totalRevenue">br0.00</div>
                            <div class="metric-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="metric-card">
                            <div class="metric-value" id="totalProducts">0</div>
                            <div class="metric-label">Total Products</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="metric-card">
                            <div class="metric-value" id="totalCustomers">0</div>
                            <div class="metric-label">Total Customers</div>
                        </div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="filter-section">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Date Range</label>
                                        <select class="form-control" id="dateRange">
                                            <option value="today">Today</option>
                                            <option value="yesterday">Yesterday</option>
                                            <option value="last7days">Last 7 Days</option>
                                            <option value="last30days">Last 30 Days</option>
                                            <option value="thisMonth">This Month</option>
                                            <option value="lastMonth">Last Month</option>
                                            <option value="custom">Custom Range</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4" id="customDateRange" style="display: none;">
                                    <div class="form-group">
                                        <label>Custom Date Range</label>
                                        <div class="input-group">
                                            <input type="date" class="form-control" id="startDate">
                                            <span class="input-group-addon">to</span>
                                            <input type="date" class="form-control" id="endDate">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Report Type</label>
                                        <select class="form-control" id="reportType">
                                            <option value="all">All Reports</option>
                                            <option value="sales">Sales Reports</option>
                                            <option value="inventory">Inventory Reports</option>
                                            <option value="production">Production Reports</option>
                                            <option value="financial">Financial Reports</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button class="btn btn-primary btn-block" id="applyFilters">
                                            <i class="fas fa-filter"></i> Apply Filters
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reports Grid -->
                <div class="row">
                    <!-- Sales Reports -->
                    <div class="col-md-4 report-card sales-report">
                        <div class="dashboard-card">
                            <div class="card-body">
                                <div class="report-icon text-primary">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <h4>Sales Performance</h4>
                                <p>View detailed sales metrics, trends, and analysis</p>
                                <a href="newreports.php?type=sales_performance" class="btn btn-primary btn-sm">View Report</a>
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Reports -->
                    <div class="col-md-4 report-card inventory-report">
                        <div class="dashboard-card">
                            <div class="card-body">
                                <div class="report-icon text-success">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <h4>Inventory Status</h4>
                                <p>Track stock levels, movements, and low stock alerts</p>
                                <a href="newreports.php?type=inventory" class="btn btn-success btn-sm">View Report</a>
                            </div>
                        </div>
                    </div>

                    <!-- Production Reports -->
                    <div class="col-md-4 report-card production-report">
                        <div class="dashboard-card">
                            <div class="card-body">
                                <div class="report-icon text-warning">
                                    <i class="fas fa-industry"></i>
                                </div>
                                <h4>Production Analysis</h4>
                                <p>Monitor production efficiency and output</p>
                                <a href="newreports.php?type=production" class="btn btn-warning btn-sm">View Report</a>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Reports -->
                    <div class="col-md-4 report-card financial-report">
                        <div class="dashboard-card">
                            <div class="card-body">
                                <div class="report-icon text-danger">
                                    <i class="fas fa-dollar-sign"></i>
                                </div>
                                <h4>Cost Analysis</h4>
                                <p>Analyze costs, expenses, and profitability</p>
                                <a href="newreports.php?type=cost_analysis" class="btn btn-danger btn-sm">View Report</a>
                            </div>
                        </div>
                    </div>

                    <!-- Quality Reports -->
                    <div class="col-md-4 report-card quality-report">
                        <div class="dashboard-card">
                            <div class="card-body">
                                <div class="report-icon text-info">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h4>Quality Metrics</h4>
                                <p>Track quality control and inspection results</p>
                                <a href="newreports.php?type=quality" class="btn btn-info btn-sm">View Report</a>
                            </div>
                        </div>
                    </div>

                    <!-- Comprehensive Reports -->
                    <div class="col-md-4 report-card comprehensive-report">
                        <div class="dashboard-card">
                            <div class="card-body">
                                <div class="report-icon text-secondary">
                                    <i class="fas fa-chart-pie"></i>
                                </div>
                                <h4>Comprehensive Analysis</h4>
                                <p>View all reports in one comprehensive dashboard</p>
                                <a href="newreports.php" class="btn btn-secondary btn-sm">View Report</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Reports Section -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h4>Recent Reports</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped" id="recentReportsTable">
                                        <thead>
                                            <tr>
                                                <th>Report Name</th>
                                                <th>Type</th>
                                                <th>Generated By</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Will be populated by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize date inputs with current date
    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];
    $('#startDate, #endDate').attr('max', todayStr);
    $('#startDate').on('change', function() {
        $('#endDate').attr('min', $(this).val());
    });

    // Handle date range selection
    $('#dateRange').change(function() {
        $('#customDateRange').toggle($(this).val() === 'custom');
    });

    // Handle report type filter
    $('#reportType').change(function() {
        const selectedType = $(this).val();
        $('.report-card').show();
        if (selectedType !== 'all') {
            $('.report-card').not('.' + selectedType + '-report').hide();
        }
    });

    // Load initial dashboard data
    function loadDashboardData() {
        $.ajax({
            url: 'php_action/fetchDashboardData.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#totalOrders').text(response.data.total_orders);
                    $('#totalRevenue').text('br' + numberWithCommas(response.data.total_revenue.toFixed(2)));
                    $('#totalProducts').text(response.data.total_products);
                    $('#totalCustomers').text(response.data.total_customers);
                }
            }
        });
    }

    // Load recent reports
    function loadRecentReports() {
        $.ajax({
            url: 'php_action/fetchRecentReports.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const tbody = $('#recentReportsTable tbody');
                    tbody.empty();
                    response.data.forEach(report => {
                        tbody.append(`
                            <tr>
                                <td>${report.name}</td>
                                <td>${report.type}</td>
                                <td>${report.generated_by}</td>
                                <td>${report.date}</td>
                                <td>
                                    <a href="${report.url}" class="btn btn-sm btn-primary">View</a>
                                    <button class="btn btn-sm btn-info" onclick="downloadReport(${report.id})">Download</button>
                                </td>
                            </tr>
                        `);
                    });
                }
            }
        });
    }

    // Helper function for number formatting
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Apply filters
    $('#applyFilters').click(function() {
        const dateRange = $('#dateRange').val();
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        const reportType = $('#reportType').val();

        // Show loading state
        $('.loading-overlay').show();

        // Reload data with filters
        loadDashboardData();
        loadRecentReports();

        // Hide loading state
        $('.loading-overlay').hide();
    });

    // Initial load
    loadDashboardData();
    loadRecentReports();
});
</script>

<?php require_once 'includes/footer.php'; ?> 