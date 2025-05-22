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

<style>
    /* Form Styles */
    .form-group {
        margin-bottom: 8px;
    }
    
    .form-control {
        font-size: 11px;
        height: 30px;
        padding: 5px 10px;
    }
    
    .control-label {
        font-size: 11px;
        padding-top: 5px;
    }
    
    .modal-body {
        padding: 15px;
    }
    
    .row {
        margin-bottom: 5px;
    }
    
    /* Image Preview */
    .payment-image-preview {
        max-width: 100%;
        max-height: 200px;
        margin-top: 10px;
    }
    
    /* Modal Size */
    .modal-dialog {
        width: 600px;
    }
    
    /* Button Styles */
    .btn {
        font-size: 11px;
        padding: 4px 8px;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"><i class="fas fa-chart-line"></i> GPS Profit Tracking</div>
            </div>
            <div class="panel-body">
                <div class="row">
                    <!-- Date Range Filter -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Date Range:</label>
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="far fa-calendar-alt"></i>
                                </div>
                                <input type="text" class="form-control" id="profitDateRange">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8 text-right">
                        <button type="button" class="btn btn-success" id="generateReport">
                            <i class="fas fa-file-excel"></i> Generate Report
                        </button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row margin-top-10">
                    <div class="col-lg-3 col-xs-6">
                        <div class="small-box bg-aqua">
                            <div class="inner">
                                <h3 id="totalSalesAmount">0.00</h3>
                                <p>Total Sales</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-xs-6">
                        <div class="small-box bg-green">
                            <div class="inner">
                                <h3 id="totalPurchaseAmount">0.00</h3>
                                <p>Total Purchase</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-xs-6">
                        <div class="small-box bg-yellow">
                            <div class="inner">
                                <h3 id="totalExpenseAmount">0.00</h3>
                                <p>Total Expenses</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-xs-6">
                        <div class="small-box bg-red">
                            <div class="inner">
                                <h3 id="totalCreditAmount">0.00</h3>
                                <p>Total Credit</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Investment and Profit Cards -->
                <div class="row">
                    <div class="col-lg-4 col-xs-6">
                        <div class="small-box bg-purple">
                            <div class="inner">
                                <h3 id="totalInvestmentAmount">0.00</h3>
                                <p>Total Investment</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-xs-6">
                        <div class="small-box bg-olive">
                            <div class="inner">
                                <h3 id="grossProfitAmount">0.00</h3>
                                <p>Gross Profit</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-xs-6">
                        <div class="small-box bg-blue">
                            <div class="inner">
                                <h3 id="netProfitAmount">0.00</h3>
                                <p>Net Profit</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Profit Chart -->
                <div class="row margin-top-10">
                    <div class="col-md-12">
                        <div class="box box-info">
                            <div class="box-header with-border">
                                <h3 class="box-title">Profit Trend</h3>
                            </div>
                            <div class="box-body">
                                <canvas id="profitChart" style="height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Transactions Table -->
                <div class="row margin-top-10">
                    <div class="col-md-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h3 class="box-title">Daily Transactions</h3>
                            </div>
                            <div class="box-body">
                                <table id="gpsProfitTable" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Sales</th>
                                            <th>Purchase</th>
                                            <th>Credit</th>
                                            <th>Expenses</th>
                                            <th>Investment</th>
                                            <th>Gross Profit</th>
                                            <th>Net Profit</th>
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

<!-- Add GPS Profit Modal -->
<div class="modal fade" id="addGpsProfitModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitGpsProfitForm" action="php_action/createGpsProfit.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Profit Record</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Date Range</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="profit_date_range" name="profit_date_range" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Total Purchase</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="total_purchase" name="total_purchase" readonly>
                            <small class="text-muted">Auto-calculated from GPS Orders</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Total Sales</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="total_sales" name="total_sales" readonly>
                            <small class="text-muted">Auto-calculated from GPS Sales</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Total Credit</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="total_credit" name="total_credit" readonly>
                            <small class="text-muted">Auto-calculated from Credit Records</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Total Expenses</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="total_expenses" name="total_expenses" readonly>
                            <small class="text-muted">Auto-calculated from GPS Expenses</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Gross Profit</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="gross_profit" name="gross_profit" readonly>
                            <small class="text-muted">Sales - Purchase</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Net Profit</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="net_profit" name="net_profit" readonly>
                            <small class="text-muted">Gross Profit - (Expenses + Credit)</small>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Comment</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="comment" name="comment" placeholder="Add notes about this profit record"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createGpsProfitBtn">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit GPS Profit Modal -->
<div class="modal fade" id="editGpsProfitModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editGpsProfitForm" action="php_action/editGpsProfit.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Profit Record</h4>
                </div>
                <div class="modal-body">
                    <div id="edit-gps-profit-messages"></div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="editProfitDate" name="editProfitDate" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Total Purchase</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editTotalPurchase" name="editTotalPurchase" placeholder="Total Purchase" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Total Sales</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editTotalSales" name="editTotalSales" placeholder="Total Sales" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Total Credit</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editTotalCredit" name="editTotalCredit" placeholder="Total Credit" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Total Expenses</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editTotalExpenses" name="editTotalExpenses" placeholder="Total Expenses" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Total Profit</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="editTotalProfit" name="editTotalProfit" placeholder="Total Profit" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Comment</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="editComment" name="editComment" placeholder="Comment"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="profitId" id="profitId">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add necessary CSS -->
<style>
.small-box {
    border-radius: 3px;
    position: relative;
    display: block;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.1);
    padding: 20px;
}

.small-box > .inner {
    padding: 10px;
}

.small-box h3 {
    font-size: 38px;
    font-weight: bold;
    margin: 0 0 10px 0;
    white-space: nowrap;
    padding: 0;
}

.bg-aqua { background-color: #00c0ef !important; color: #fff !important; }
.bg-green { background-color: #00a65a !important; color: #fff !important; }
.bg-yellow { background-color: #f39c12 !important; color: #fff !important; }
.bg-red { background-color: #dd4b39 !important; color: #fff !important; }

.box {
    position: relative;
    border-radius: 3px;
    background: #ffffff;
    border-top: 3px solid #d2d6de;
    margin-bottom: 20px;
    width: 100%;
    box-shadow: 0 1px 1px rgba(0,0,0,0.1);
}

.box.box-info { border-top-color: #00c0ef; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<script src="js/gps_profit.js"></script>

<?php require_once 'includes/footer.php'; ?> 