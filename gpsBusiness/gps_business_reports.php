<?php
require_once '../php_action/core.php';

// Initialize the database connection if not already done
if (!isset($connect)) {
    require_once '../php_action/db_connect.php';
}

require_once 'includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('location: ../index.php');
    exit();
}

// Fetch investors for dropdown
$investorsSql = "SELECT id, name FROM gps_investors ORDER BY name ASC";
$investorsResult = $connect->query($investorsSql);
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fas fa-chart-line"></i> Business Reports</h3>
            </div>
            <div class="panel-body">
                <!-- Report Type Selection -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#profitLoss" aria-controls="profitLoss" role="tab" data-toggle="tab">
                                    <i class="fas fa-chart-bar"></i> Profit & Loss
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#salesAnalysis" aria-controls="salesAnalysis" role="tab" data-toggle="tab">
                                    <i class="fas fa-chart-line"></i> Sales Analysis
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#expenseAnalysis" aria-controls="expenseAnalysis" role="tab" data-toggle="tab">
                                    <i class="fas fa-chart-pie"></i> Expense Analysis
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#investorReturns" aria-controls="investorReturns" role="tab" data-toggle="tab">
                                    <i class="fas fa-users"></i> Investor Returns
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Profit & Loss Tab -->
                    <div role="tabpanel" class="tab-pane active" id="profitLoss">
                        <div class="row">
                            <div class="col-md-12">
                                <!-- Filters -->
                                <div class="well">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Date Range</label>
                                                <select class="form-control" id="profitLossDateRange">
                                                    <option value="this_month">This Month</option>
                                                    <option value="last_month">Last Month</option>
                                                    <option value="this_quarter">This Quarter</option>
                                                    <option value="last_quarter">Last Quarter</option>
                                                    <option value="this_year">This Year</option>
                                                    <option value="last_year">Last Year</option>
                                                    <option value="custom">Custom Range</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Business Cycle</label>
                                                <select class="form-control" id="profitLossCycle">
                                                    <option value="">All Cycles</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Currency</label>
                                                <select class="form-control" id="profitLossCurrency">
                                                    <option value="ETB">ETB</option>
                                                    <option value="USD">USD</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <button class="btn btn-primary form-control" id="generateProfitLossReport">
                                                    <i class="fas fa-sync"></i> Generate Report
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary Cards -->
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="small-box bg-aqua">
                                            <div class="inner">
                                                <h3 id="totalRevenue">0.00</h3>
                                                <p>Total Revenue</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small-box bg-green">
                                            <div class="inner">
                                                <h3 id="grossProfit">0.00</h3>
                                                <p>Gross Profit</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small-box bg-yellow">
                                            <div class="inner">
                                                <h3 id="totalExpenses">0.00</h3>
                                                <p>Total Expenses</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small-box bg-red">
                                            <div class="inner">
                                                <h3 id="netProfit">0.00</h3>
                                                <p>Net Profit</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-chart-pie"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Charts -->
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="box">
                                            <div class="box-header">
                                                <h3 class="box-title">Profit & Loss Trend</h3>
                                            </div>
                                            <div class="box-body">
                                                <canvas id="profitLossTrendChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="box">
                                            <div class="box-header">
                                                <h3 class="box-title">Revenue Distribution</h3>
                                            </div>
                                            <div class="box-body">
                                                <canvas id="revenueDistributionChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detailed Table -->
                                <div class="box">
                                    <div class="box-header">
                                        <h3 class="box-title">Detailed Profit & Loss Statement</h3>
                                        <div class="box-tools">
                                            <button class="btn btn-box-tool" id="exportProfitLoss">
                                                <i class="fas fa-download"></i> Export
                                            </button>
                                        </div>
                                    </div>
                                    <div class="box-body">
                                        <table id="profitLossTable" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Business Cycle</th>
                                                    <th>Revenue</th>
                                                    <th>Cost of Sales</th>
                                                    <th>Gross Profit</th>
                                                    <th>Expenses</th>
                                                    <th>Net Profit</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Analysis Tab -->
                    <div role="tabpanel" class="tab-pane" id="salesAnalysis">
                        <div class="row">
                            <div class="col-md-12">
                                <!-- Filters -->
                                <div class="well">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Date Range</label>
                                                <select class="form-control" id="salesDateRange">
                                                    <option value="this_month">This Month</option>
                                                    <option value="last_month">Last Month</option>
                                                    <option value="this_quarter">This Quarter</option>
                                                    <option value="last_quarter">Last Quarter</option>
                                                    <option value="this_year">This Year</option>
                                                    <option value="last_year">Last Year</option>
                                                    <option value="custom">Custom Range</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Business Cycle</label>
                                                <select class="form-control" id="salesCycle">
                                                    <option value="">All Cycles</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Currency</label>
                                                <select class="form-control" id="salesCurrency">
                                                    <option value="ETB">ETB</option>
                                                    <option value="USD">USD</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <button class="btn btn-primary form-control" id="generateSalesReport">
                                                    <i class="fas fa-sync"></i> Generate Report
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary Cards -->
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="small-box bg-aqua">
                                            <div class="inner">
                                                <h3 id="totalSales">0.00</h3>
                                                <p>Total Sales</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-shopping-cart"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small-box bg-green">
                                            <div class="inner">
                                                <h3 id="totalQuantity">0</h3>
                                                <p>Total Quantity</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-boxes"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small-box bg-yellow">
                                            <div class="inner">
                                                <h3 id="averageSaleAmount">0.00</h3>
                                                <p>Average Sale Amount</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-calculator"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small-box bg-red">
                                            <div class="inner">
                                                <h3 id="totalCustomers">0</h3>
                                                <p>Total Customers</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Charts -->
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="box">
                                            <div class="box-header">
                                                <h3 class="box-title">Sales Trend</h3>
                                            </div>
                                            <div class="box-body">
                                                <canvas id="salesTrendChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="box">
                                            <div class="box-header">
                                                <h3 class="box-title">Top Customers</h3>
                                            </div>
                                            <div class="box-body">
                                                <canvas id="topCustomersChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detailed Table -->
                                <div class="box">
                                    <div class="box-header">
                                        <h3 class="box-title">Detailed Sales Report</h3>
                                        <div class="box-tools">
                                            <button class="btn btn-box-tool" id="exportSales">
                                                <i class="fas fa-download"></i> Export
                                            </button>
                                        </div>
                                    </div>
                                    <div class="box-body">
                                        <table id="salesTable" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Business Cycle</th>
                                                    <th>Customer</th>
                                                    <th>Quantity</th>
                                                    <th>Unit Price</th>
                                                    <th>Total Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expense Analysis Tab -->
                    <div role="tabpanel" class="tab-pane" id="expenseAnalysis">
                        <div class="row">
                            <div class="col-md-12">
                                <!-- Filters -->
                                <div class="well">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Date Range</label>
                                                <select class="form-control" id="expenseDateRange">
                                                    <option value="this_month">This Month</option>
                                                    <option value="last_month">Last Month</option>
                                                    <option value="this_quarter">This Quarter</option>
                                                    <option value="last_quarter">Last Quarter</option>
                                                    <option value="this_year">This Year</option>
                                                    <option value="last_year">Last Year</option>
                                                    <option value="custom">Custom Range</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Business Cycle</label>
                                                <select class="form-control" id="expenseCycle">
                                                    <option value="">All Cycles</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Expense Type</label>
                                                <select class="form-control" id="expenseType">
                                                    <option value="">All Types</option>
                                                    <option value="damaged_product">Damaged Product</option>
                                                    <option value="transportation">Transportation</option>
                                                    <option value="storage">Storage</option>
                                                    <option value="other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <button class="btn btn-primary form-control" id="generateExpenseReport">
                                                    <i class="fas fa-sync"></i> Generate Report
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary Cards -->
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="small-box bg-aqua">
                                            <div class="inner">
                                                <h3 id="totalExpensesAmount">0.00</h3>
                                                <p>Total Expenses</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small-box bg-green">
                                            <div class="inner">
                                                <h3 id="averageExpenseAmount">0.00</h3>
                                                <p>Average Expense</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-calculator"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small-box bg-yellow">
                                            <div class="inner">
                                                <h3 id="totalExpenseCount">0</h3>
                                                <p>Total Transactions</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-list"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small-box bg-red">
                                            <div class="inner">
                                                <h3 id="highestExpense">0.00</h3>
                                                <p>Highest Expense</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-arrow-up"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Charts -->
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="box">
                                            <div class="box-header">
                                                <h3 class="box-title">Expense Trend</h3>
                                            </div>
                                            <div class="box-body">
                                                <canvas id="expenseTrendChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="box">
                                            <div class="box-header">
                                                <h3 class="box-title">Expense Distribution by Type</h3>
                                            </div>
                                            <div class="box-body">
                                                <canvas id="expenseDistributionChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detailed Table -->
                                <div class="box">
                                    <div class="box-header">
                                        <h3 class="box-title">Detailed Expense Report</h3>
                                        <div class="box-tools">
                                            <button class="btn btn-box-tool" id="exportExpenses">
                                                <i class="fas fa-download"></i> Export
                                            </button>
                                        </div>
                                    </div>
                                    <div class="box-body">
                                        <table id="expenseTable" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Business Cycle</th>
                                                    <th>Description</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                    <th>Payment Method</th>
                                                    <th>Reference</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Investor Returns Tab -->
                    <div role="tabpanel" class="tab-pane" id="investorReturns">
                        <div class="row">
                            <div class="col-md-12">
                                <!-- Filters -->
                                <div class="well">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Date Range</label>
                                                <select class="form-control" id="investorDateRange">
                                                    <option value="this_month">This Month</option>
                                                    <option value="last_month">Last Month</option>
                                                    <option value="this_quarter">This Quarter</option>
                                                    <option value="last_quarter">Last Quarter</option>
                                                    <option value="this_year">This Year</option>
                                                    <option value="last_year">Last Year</option>
                                                    <option value="custom">Custom Range</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Investor</label>
                                                <select class="form-control" id="investorSelect">
                                                    <option value="">All Investors</option>
                                                    <?php
                                                    if ($investorsResult) {
                                                        while ($investor = $investorsResult->fetch_assoc()) {
                                                            echo '<option value="'.$investor['id'].'">'.$investor['name'].'</option>';
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select class="form-control" id="investorStatus">
                                                    <option value="">All Status</option>
                                                    <option value="pending">Pending</option>
                                                    <option value="completed">Completed</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <button class="btn btn-primary form-control" id="generateInvestorReport">
                                                    <i class="fas fa-sync"></i> Generate Report
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary Cards -->
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="small-box bg-aqua">
                                            <div class="inner">
                                                <h3 id="totalDistributed">0.00</h3>
                                                <p>Total Distributed</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small-box bg-green">
                                            <div class="inner">
                                                <h3 id="averageDistribution">0.00</h3>
                                                <p>Average Distribution</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-calculator"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small-box bg-yellow">
                                            <div class="inner">
                                                <h3 id="completedCount">0</h3>
                                                <p>Completed Distributions</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="small-box bg-red">
                                            <div class="inner">
                                                <h3 id="pendingCount">0</h3>
                                                <p>Pending Distributions</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Charts -->
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="box">
                                            <div class="box-header">
                                                <h3 class="box-title">Distribution Trend</h3>
                                            </div>
                                            <div class="box-body">
                                                <canvas id="distributionTrendChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="box">
                                            <div class="box-header">
                                                <h3 class="box-title">Distribution by Investor</h3>
                                            </div>
                                            <div class="box-body">
                                                <canvas id="investorDistributionChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detailed Table -->
                                <div class="box">
                                    <div class="box-header">
                                        <h3 class="box-title">Detailed Distribution Report</h3>
                                        <div class="box-tools">
                                            <button class="btn btn-box-tool" id="exportDistributions">
                                                <i class="fas fa-download"></i> Export
                                            </button>
                                        </div>
                                    </div>
                                    <div class="box-body">
                                        <table id="distributionTable" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Business Cycle</th>
                                                    <th>Investor</th>
                                                    <th>Share %</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                    <th>Reinvested</th>
                                                </tr>
                                            </thead>
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
</div>

<!-- Custom Date Range Modal -->
<div class="modal fade" id="dateRangeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Select Date Range</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" class="form-control" id="customStartDate">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" class="form-control" id="customEndDate">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="applyDateRange">Apply</button>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom JS -->
<script src="custom/js/gps_business_reports.js"></script>

<?php require_once 'includes/footer.php'; ?> 