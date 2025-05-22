<?php
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"><i class="fa fa-chart-bar"></i> Purchase Reports</div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <!-- Filter Section -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-12">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h4 class="panel-title"><i class="fa fa-filter"></i> Filter Options</h4>
                            </div>
                            <div class="panel-body">
                                <form id="filterForm" class="form-horizontal">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="col-sm-4 control-label">Date Range</label>
                                                <div class="col-sm-8">
                                                    <select class="form-control" id="dateRange" name="dateRange">
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
                                        </div>
                                        <div class="col-md-8" id="customDateRange" style="display:none;">
                                            <div class="form-group">
                                                <label class="col-sm-2 control-label">From</label>
                                                <div class="col-sm-4">
                                                    <input type="date" class="form-control" id="startDate" name="startDate">
                                                </div>
                                                <label class="col-sm-2 control-label">To</label>
                                                <div class="col-sm-4">
                                                    <input type="date" class="form-control" id="endDate" name="endDate">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="col-sm-4 control-label">Supplier</label>
                                                <div class="col-sm-8">
                                                    <select class="form-control selectpicker" id="supplier" name="supplier" data-live-search="true">
                                                        <option value="">All Suppliers</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="col-sm-4 control-label">Payment Status</label>
                                                <div class="col-sm-8">
                                                    <select class="form-control" id="paymentStatus" name="paymentStatus">
                                                        <option value="">All Statuses</option>
                                                        <option value="unpaid">Unpaid</option>
                                                        <option value="partially_paid">Partially Paid</option>
                                                        <option value="paid">Paid</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-search"></i> Generate Report
                                            </button>
                                            <button type="button" class="btn btn-success" id="exportExcel">
                                                <i class="fa fa-file-excel"></i> Export to Excel
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h3 class="panel-title">Total Purchases</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="totalPurchases">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <h3 class="panel-title">Total Amount</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="totalAmount">0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h3 class="panel-title">Paid Amount</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="paidAmount">0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-danger">
                            <div class="panel-heading">
                                <h3 class="panel-title">Due Amount</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="dueAmount">0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Purchases Table -->
                <table class="table table-hover table-striped table-bordered" id="purchaseReportTable">
                    <thead>
                        <tr>
                            <th>Purchase #</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>Sub Total</th>
                            <th>VAT</th>
                            <th>Grand Total</th>
                            <th>Paid Amount</th>
                            <th>Due Amount</th>
                            <th>Payment Status</th>
                            <th>Options</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Purchase Details Modal -->
<div class="modal fade" id="viewPurchaseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-eye"></i> Purchase Details</h4>
            </div>
            <div class="modal-body">
                <!-- Purchase header information -->
                <div class="row">
                    <div class="col-md-6">
                        <table class="table">
                            <tr>
                                <th>Purchase Number:</th>
                                <td id="view_purchase_number"></td>
                            </tr>
                            <tr>
                                <th>Supplier:</th>
                                <td id="view_supplier"></td>
                            </tr>
                            <tr>
                                <th>Purchase Date:</th>
                                <td id="view_purchase_date"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table">
                            <tr>
                                <th>Sub Total:</th>
                                <td id="view_sub_total"></td>
                            </tr>
                            <tr>
                                <th>VAT Amount:</th>
                                <td id="view_vat_amount"></td>
                            </tr>
                            <tr>
                                <th>Grand Total:</th>
                                <td id="view_grand_total"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Purchase items -->
                <h4>Purchase Items</h4>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Raw Material</th>
                            <th>Quantity</th>
                            <th>Rate</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseItemsTable">
                    </tbody>
                </table>

                <!-- Payment history -->
                <h4>Payment History</h4>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody id="paymentHistoryTable">
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="custom/js/purchase_reports.js"></script>

<?php require_once 'includes/footer.php'; ?> 