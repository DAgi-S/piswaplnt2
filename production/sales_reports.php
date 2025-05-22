<?php
require_once 'includes/header.php';

// Check base permission to view orders
if (!hasPermission('order.view')) {
    $_SESSION['error'] = "Access Denied. Please contact Admin or go to dashboard.";
    header('Location: dashboard.php');
    exit();
}

// Get user permissions for different order actions
$canViewOrders = hasPermission('order.view');
$canViewPayments = hasPermission('order.payment.manage');
$canProcessOrders = hasPermission('order.process');
$canExportReports = hasPermission('order.report.export');
$canUpdateStatus = hasPermission('order.status.update');
$canManageShipping = hasPermission('order.shipping.manage');
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"><i class="fa fa-chart-line"></i> Sales Reports</div>
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
                                                <label class="col-sm-4 control-label">Client</label>
                                                <div class="col-sm-8">
                                                    <select class="form-control selectpicker" id="client" name="client" data-live-search="true">
                                                        <option value="">All Clients</option>
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
                                            <?php if($canExportReports): ?>
                                            <button type="button" class="btn btn-success" id="exportExcel">
                                                <i class="fa fa-file-excel"></i> Export to Excel
                                            </button>
                                            <?php endif; ?>
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
                                <h3 class="panel-title">Total Sales</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="totalSales">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <h3 class="panel-title">Total Revenue</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="totalRevenue">0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h3 class="panel-title">Received Amount</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="receivedAmount">0.00</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-danger">
                            <div class="panel-heading">
                                <h3 class="panel-title">Outstanding Amount</h3>
                            </div>
                            <div class="panel-body">
                                <h3 id="outstandingAmount">0.00</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Table -->
                <table class="table table-hover table-striped table-bordered" id="salesReportTable">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Sub Total</th>
                            <th>VAT</th>
                            <th>Grand Total</th>
                            <th>Received</th>
                            <th>Outstanding</th>
                            <th>Payment Status</th>
                            <th>Options</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Sale Details Modal -->
<div class="modal fade" id="saleDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-eye"></i> Sales Order Details</h4>
            </div>
            <div class="modal-body">
                <!-- Order Information -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h4 class="panel-title">Order Information</h4>
                            </div>
                            <div class="panel-body">
                                <table class="table table-bordered table-striped">
                                    <tr>
                                        <th style="width:35%">Order Number:</th>
                                        <td id="invoiceNumber"></td>
                                    </tr>
                                    <tr>
                                        <th>Order Date:</th>
                                        <td id="saleDate"></td>
                                    </tr>
                                    <tr>
                                        <th>Order Status:</th>
                                        <td id="orderStatus"></td>
                                    </tr>
                                    <tr>
                                        <th>Payment Status:</th>
                                        <td id="paymentStatus"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h4 class="panel-title">Client Information</h4>
                            </div>
                            <div class="panel-body">
                                <table class="table table-bordered table-striped">
                                    <tr>
                                        <th style="width:35%">Company Name:</th>
                                        <td id="clientName"></td>
                                    </tr>
                                    <tr>
                                        <th>TIN Number:</th>
                                        <td id="clientTin"></td>
                                    </tr>
                                    <tr>
                                        <th>Phone:</th>
                                        <td id="clientPhone"></td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td id="clientEmail"></td>
                                    </tr>
                                    <tr>
                                        <th>Address:</th>
                                        <td id="clientAddress"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h4 class="panel-title">Order Items</h4>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-right">Quantity</th>
                                        <th class="text-right">Rate</th>
                                        <th class="text-right">Tax Rate</th>
                                        <th class="text-right">Tax Amount</th>
                                        <th class="text-right">Withholding</th>
                                        <th class="text-right">Discount</th>
                                        <th class="text-right">Subtotal</th>
                                        <th class="text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary -->
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <h4 class="panel-title">Financial Summary</h4>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered table-striped">
                                    <tr>
                                        <th style="width:35%">Sub Total:</th>
                                        <td id="subTotal" class="text-right"></td>
                                    </tr>
                                    <tr>
                                        <th>VAT Amount:</th>
                                        <td id="vatAmount" class="text-right"></td>
                                    </tr>
                                    <tr>
                                        <th>Withholding Amount:</th>
                                        <td id="withholdingAmount" class="text-right"></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered table-striped">
                                    <tr>
                                        <th style="width:35%">Discount Amount:</th>
                                        <td id="discountAmount" class="text-right"></td>
                                    </tr>
                                    <tr>
                                        <th>Grand Total:</th>
                                        <td id="grandTotal" class="text-right"></td>
                                    </tr>
                                    <tr>
                                        <th>Paid Amount:</th>
                                        <td id="paidAmount" class="text-right"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment history -->
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <h4 class="panel-title">Payment History</h4>
                    </div>
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="text-right">Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="paymentsTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printInvoice()">
                    <i class="fa fa-print"></i> Print Invoice
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add JavaScript permissions -->
<script>
const permissions = {
    canViewOrders: <?php echo json_encode($canViewOrders); ?>,
    canViewPayments: <?php echo json_encode($canViewPayments); ?>,
    canProcessOrders: <?php echo json_encode($canProcessOrders); ?>,
    canExportReports: <?php echo json_encode($canExportReports); ?>,
    canUpdateStatus: <?php echo json_encode($canUpdateStatus); ?>,
    canManageShipping: <?php echo json_encode($canManageShipping); ?>
};
</script>

<script src="custom/js/sales_reports.js"></script>

<?php require_once 'includes/footer.php'; ?> 