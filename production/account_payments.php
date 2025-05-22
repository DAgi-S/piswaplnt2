<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';

// Fetch company information
$company_settings = array();
$company_query = "SELECT setting_key, setting_value FROM company_settings";
$company_result = $connect->query($company_query);
while($row = $company_result->fetch_assoc()) {
    $company_settings[$row['setting_key']] = $row['setting_value'];
}

// Add custom styles at the top
?>
<style>
    /* Table layout optimization */
    .table-responsive {
        margin-bottom: 15px;
    }
    .table {
        font-size: 13px;
        margin-bottom: 0;
    }
    .table > thead > tr > th,
    .table > tbody > tr > td {
        padding: 8px 4px;
        vertical-align: middle;
        white-space: nowrap;
    }
    /* Column width optimization */
    .col-date { width: 85px; }
    .col-number { width: 130px; }
    .col-name { width: 120px; max-width: 120px; overflow: hidden; text-overflow: ellipsis; }
    .col-amount { width: 90px; text-align: right; }
    .col-status { width: 80px; text-align: center; }
    
    /* Status label optimization */
    .label {
        display: inline-block;
        min-width: 60px;
        padding: 3px 8px;
        font-size: 11px;
    }
    
    /* Info boxes optimization */
    .info-box-content {
        padding: 5px 10px;
    }
    .info-box-text {
        font-size: 13px;
    }
    .info-box-number {
        font-size: 18px;
    }
    .progress-description {
        font-size: 12px;
    }
    .nav-tabs-custom > .nav-tabs > li.active {
        border-top-color: #3c8dbc;
    }
    .nav-tabs-custom > .tab-content {
        padding: 10px;
        border-bottom-right-radius: 3px;
        border-bottom-left-radius: 3px;
    }
    /* Financial Summary Cards Optimization */
    .financial-summary .info-box {
        min-height: 100px;
        margin-bottom: 15px;
    }
    .financial-summary .info-box-icon {
        height: 100px;
        width: 60px;
        font-size: 28px;
        line-height: 100px;
    }
    .financial-summary .info-box-content {
        margin-left: 60px;
        padding: 8px;
    }
    .financial-summary .info-box-text {
        font-size: 12px;
        font-weight: 600;
        margin: 0;
    }
    .financial-summary .info-box-number {
        font-size: 16px;
        margin: 5px 0;
    }
    .financial-summary .progress-description {
        font-size: 10px;
        margin-top: 2px;
    }
    .financial-grid {
        display: flex;
        flex-wrap: wrap;
        margin: -7.5px;
    }
    .financial-grid .col-md-3 {
        padding: 7.5px;
        width: 25%;
    }

    /* Print Button Styles */
    .print-button {
        float: right;
        margin: 10px;
    }
    .print-button i {
        margin-right: 5px;
    }

    /* Print Styles */
    @media print {
        /* Hide unnecessary elements */
        .main-header,
        .main-sidebar,
        .content-header,
        .box-tools,
        .nav-tabs,
        footer,
        .no-print {
            display: none !important;
        }
        
        /* Adjust page layout */
        .content-wrapper {
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .wrapper {
            background: #fff !important;
        }
        
        /* Ensure full width */
        .container,
        .row,
        .col-md-12,
        .col-md-4,
        .col-md-3 {
            width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* Adjust card styles for printing */
        .financial-summary .info-box {
            border: 1px solid #ddd !important;
            margin: 5px !important;
        }

        .info-box-icon {
            background: #f4f4f4 !important;
            color: #000 !important;
        }

        .info-box-content {
            color: #000 !important;
        }

        /* Ensure tables print properly */
        .table-responsive {
            overflow: visible !important;
        }
        
        .table {
            font-size: 10px !important;
            border-collapse: collapse !important;
        }
        
        .table td,
        .table th {
            border: 1px solid #ddd !important;
            padding: 4px !important;
        }

        /* Add print header */
        .print-header {
            display: block !important;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .company-logo {
            max-height: 60px;
        }
        .company-name {
            color: #000 !important;
        }
        .company-details {
            color: #333 !important;
        }
        .report-title {
            color: #000 !important;
        }
        .report-period {
            color: #333 !important;
        }

        /* Format date range for print */
        .print-date-range {
            text-align: right;
            font-size: 12px;
            margin-bottom: 10px;
        }

        /* Force page break before table */
        .transactions-section {
            page-break-before: always;
        }

        /* Adjust grid for print */
        .financial-grid .col-md-3 {
            width: 25% !important;
            float: left !important;
            page-break-inside: avoid !important;
        }

        /* Hide POS button */
        .pos-button {
            display: none !important;
        }
    }

    /* Company Header Print Styles */
    .print-header {
        text-align: center;
        margin-bottom: 30px;
        padding: 20px;
    }
    .company-logo {
        max-height: 80px;
        margin-bottom: 10px;
    }
    .company-name {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .company-details {
        font-size: 12px;
        color: #666;
        margin-bottom: 5px;
    }
    .report-title {
        font-size: 18px;
        font-weight: bold;
        margin: 20px 0 10px;
        padding-top: 10px;
        border-top: 1px solid #ddd;
    }
    .report-period {
        font-size: 13px;
        color: #666;
        margin-bottom: 20px;
    }
</style>

<?php
// Check if user has permission to access this page
if (!hasPermission('account.view')) {
    header('Location: dashboard.php');
    exit();
}

// Get date range filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Fetch sales data
$sales_query = "SELECT 
    so.id as id,
    so.order_number,
    so.order_date,
    so.subtotal,
    so.tax_amount as vat_amount,
    so.withholding_amount,
    so.total_amount,
    so.paid_amount,
    so.balance,
    so.payment_status,
    so.order_status,
    c.company_name as customer_name
FROM sales_orders so
LEFT JOIN clients c ON so.client_id = c.id
WHERE so.order_date BETWEEN ? AND ?
ORDER BY so.order_date DESC";

$sales_stmt = $connect->prepare($sales_query);
$sales_stmt->bind_param("ss", $start_date, $end_date);
$sales_stmt->execute();
$sales_result = $sales_stmt->get_result();

// Fetch purchase data
$purchases_query = "SELECT 
    po.id as id,
    po.purchase_number,
    po.purchase_date,
    po.sub_total as subtotal,
    po.vat_amount,
    po.withholding_tax_amount as withholding_amount,
    po.grand_total,
    po.paid_amount,
    (po.grand_total - po.paid_amount) as balance,
    po.payment_status,
    po.status,
    s.company_name
FROM purchases po
LEFT JOIN suppliers s ON po.supplier_id = s.id
WHERE po.purchase_date BETWEEN ? AND ?
ORDER BY po.purchase_date DESC";

$purchases_stmt = $connect->prepare($purchases_query);
$purchases_stmt->bind_param("ss", $start_date, $end_date);
$purchases_stmt->execute();
$purchases_result = $purchases_stmt->get_result();

// Calculate totals
$total_sales = 0;
$total_purchases = 0;
$total_sales_paid = 0;
$total_purchases_paid = 0;
$total_sales_vat = 0;
$total_purchases_vat = 0;
$total_sales_withholding = 0;
$total_purchases_withholding = 0;

while ($sale = $sales_result->fetch_assoc()) {
    $total_sales += $sale['total_amount'];
    $total_sales_paid += $sale['paid_amount'];
    $total_sales_vat += $sale['vat_amount'];
    $total_sales_withholding += $sale['withholding_amount'];
}

while ($purchase = $purchases_result->fetch_assoc()) {
    $total_purchases += $purchase['grand_total'];
    $total_purchases_paid += $purchase['paid_amount'];
    $total_purchases_vat += $purchase['vat_amount'];
    $total_purchases_withholding += $purchase['withholding_amount'];
}

// Reset result pointers
$sales_result->data_seek(0);
$purchases_result->data_seek(0);
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Account Payments</h1>
        <ol class="breadcrumb">
            <li><a href="../dashboard.php"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active">Account Payments</li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Financial Summary</h3>
                        <div class="box-tools pull-right">
                            <button class="btn btn-primary print-button" onclick="window.print();">
                                <i class="fa fa-print"></i> Print Report
                            </button>
                            <form class="form-inline" method="GET">
                                <div class="form-group">
                                    <label>From:</label>
                                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                                </div>
                                <div class="form-group">
                                    <label>To:</label>
                                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                                </div>
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </form>
                        </div>
                    </div>
                    <div class="box-body">
                        <!-- Print Header (Only visible when printing) -->
                        <div class="print-header" style="display: none;">
                            <?php if (!empty($company_settings['company_logo'])): ?>
                            <img src="<?php echo htmlspecialchars($company_settings['company_logo']); ?>" alt="Company Logo" class="company-logo">
                            <?php endif; ?>
                            
                            <div class="company-name">
                                <?php echo htmlspecialchars($company_settings['company_name']); ?>
                            </div>
                            
                            <div class="company-details">
                                <?php if (!empty($company_settings['company_address'])): ?>
                                    <?php echo htmlspecialchars($company_settings['company_address']); ?><br>
                                <?php endif; ?>
                                
                                <?php if (!empty($company_settings['company_phone'])): ?>
                                    Phone: <?php echo htmlspecialchars($company_settings['company_phone']); ?><br>
                                <?php endif; ?>
                                
                                <?php if (!empty($company_settings['company_email'])): ?>
                                    Email: <?php echo htmlspecialchars($company_settings['company_email']); ?><br>
                                <?php endif; ?>
                                
                                <?php if (!empty($company_settings['company_website'])): ?>
                                    Website: <?php echo htmlspecialchars($company_settings['company_website']); ?>
                                <?php endif; ?>
                            </div>

                            <div class="report-title">
                                Financial Summary Report
                            </div>
                            
                            <div class="report-period">
                                Report Period: <?php echo date('F j, Y', strtotime($start_date)); ?> - <?php echo date('F j, Y', strtotime($end_date)); ?><br>
                                Generated on: <?php echo date('F j, Y h:i A'); ?>
                            </div>
                        </div>

                        <div class="row financial-summary">
                            <div class="col-md-12">
                                <div class="financial-grid">
                                    <!-- Main Summary Cards -->
                                    <div class="col-md-3">
                                        <div class="info-box bg-green">
                                            <span class="info-box-icon"><i class="fa fa-shopping-cart"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Sales</span>
                                                <span class="info-box-number"><?php echo number_format($total_sales, 2); ?></span>
                                                <span class="progress-description">
                                                    Paid: <?php echo number_format($total_sales_paid, 2); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="info-box bg-red">
                                            <span class="info-box-icon"><i class="fa fa-truck"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Total Purchases</span>
                                                <span class="info-box-number"><?php echo number_format($total_purchases, 2); ?></span>
                                                <span class="progress-description">
                                                    Paid: <?php echo number_format($total_purchases_paid, 2); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="info-box bg-blue">
                                            <span class="info-box-icon"><i class="fa fa-money"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Net Income</span>
                                                <span class="info-box-number"><?php echo number_format($total_sales - $total_purchases, 2); ?></span>
                                                <span class="progress-description">
                                                    Net Cash Flow: <?php echo number_format($total_sales_paid - $total_purchases_paid, 2); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="info-box bg-yellow">
                                            <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Pending Payments</span>
                                                <span class="info-box-number"><?php echo number_format(($total_sales - $total_sales_paid) + ($total_purchases - $total_purchases_paid), 2); ?></span>
                                                <span class="progress-description">
                                                    Sales: <?php echo number_format($total_sales - $total_sales_paid, 2); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- VAT Cards -->
                                    <div class="col-md-3">
                                        <div class="info-box bg-purple">
                                            <span class="info-box-icon"><i class="fa fa-plus-circle"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Sales VAT</span>
                                                <span class="info-box-number"><?php echo number_format($total_sales_vat, 2); ?></span>
                                                <span class="progress-description">
                                                    Total VAT collected
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="info-box bg-teal">
                                            <span class="info-box-icon"><i class="fa fa-minus-circle"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Purchase VAT</span>
                                                <span class="info-box-number"><?php echo number_format($total_purchases_vat, 2); ?></span>
                                                <span class="progress-description">
                                                    Total VAT paid
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="info-box bg-maroon">
                                            <span class="info-box-icon"><i class="fa fa-balance-scale"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Payable VAT</span>
                                                <span class="info-box-number"><?php echo number_format($total_sales_vat - $total_purchases_vat, 2); ?></span>
                                                <span class="progress-description">
                                                    Net VAT payable
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Empty space for grid alignment -->
                                    <div class="col-md-3">
                                        <div class="info-box" style="visibility: hidden;">
                                            <span class="info-box-icon"><i class="fa fa-square"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Placeholder</span>
                                                <span class="info-box-number">0.00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Withholding Cards -->
                                    <div class="col-md-3">
                                        <div class="info-box bg-olive">
                                            <span class="info-box-icon"><i class="fa fa-plus-square"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Sales W/Tax</span>
                                                <span class="info-box-number"><?php echo number_format($total_sales_withholding, 2); ?></span>
                                                <span class="progress-description">
                                                    Sales withholding
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="info-box bg-orange">
                                            <span class="info-box-icon"><i class="fa fa-minus-square"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Purchase W/Tax</span>
                                                <span class="info-box-number"><?php echo number_format($total_purchases_withholding, 2); ?></span>
                                                <span class="progress-description">
                                                    Purchase withholding
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="info-box bg-navy">
                                            <span class="info-box-icon"><i class="fa fa-calculator"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Net W/Tax</span>
                                                <span class="info-box-number"><?php echo number_format($total_sales_withholding - $total_purchases_withholding, 2); ?></span>
                                                <span class="progress-description">
                                                    Net withholding
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Empty space for grid alignment -->
                                    <div class="col-md-3">
                                        <div class="info-box" style="visibility: hidden;">
                                            <span class="info-box-icon"><i class="fa fa-square"></i></span>
                                            <div class="info-box-content">
                                                <span class="info-box-text">Placeholder</span>
                                                <span class="info-box-number">0.00</span>
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

        <div class="row transactions-section">
            <div class="col-md-12">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#sales_tab" data-toggle="tab">Sales Transactions</a></li>
                        <li><a href="#purchases_tab" data-toggle="tab">Purchase Transactions</a></li>
                    </ul>
                    <div class="tab-content">
                        <!-- Sales Tab -->
                        <div class="tab-pane active" id="sales_tab">
                            <div class="box box-success" style="border-top: none;">
                                <div class="box-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th class="col-number">Order #</th>
                                                    <th class="col-date">Date</th>
                                                    <th class="col-name">Customer</th>
                                                    <th class="col-amount">Subtotal</th>
                                                    <th class="col-amount">VAT</th>
                                                    <th class="col-amount">W/Tax</th>
                                                    <th class="col-amount">Total</th>
                                                    <th class="col-amount">Paid</th>
                                                    <th class="col-amount">Balance</th>
                                                    <th class="col-status">Payment</th>
                                                    <th class="col-status">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($sale = $sales_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="col-number"><?php echo htmlspecialchars($sale['order_number']); ?></td>
                                                    <td class="col-date"><?php echo date('Y-m-d', strtotime($sale['order_date'])); ?></td>
                                                    <td class="col-name" title="<?php echo htmlspecialchars($sale['customer_name']); ?>">
                                                        <?php echo htmlspecialchars($sale['customer_name']); ?>
                                                    </td>
                                                    <td class="col-amount"><?php echo number_format($sale['subtotal'], 2); ?></td>
                                                    <td class="col-amount"><?php echo number_format($sale['vat_amount'], 2); ?></td>
                                                    <td class="col-amount"><?php echo number_format($sale['withholding_amount'], 2); ?></td>
                                                    <td class="col-amount"><?php echo number_format($sale['total_amount'], 2); ?></td>
                                                    <td class="col-amount"><?php echo number_format($sale['paid_amount'], 2); ?></td>
                                                    <td class="col-amount"><?php echo number_format($sale['balance'], 2); ?></td>
                                                    <td class="col-status">
                                                        <?php
                                                        $label_class = '';
                                                        switch($sale['payment_status']) {
                                                            case 'paid': $label_class = 'label-success'; break;
                                                            case 'partial': $label_class = 'label-warning'; break;
                                                            case 'unpaid': $label_class = 'label-danger'; break;
                                                        }
                                                        ?>
                                                        <span class="label <?php echo $label_class; ?>"><?php echo ucfirst($sale['payment_status']); ?></span>
                                                    </td>
                                                    <td class="col-status">
                                                        <span class="label label-info"><?php echo ucfirst($sale['order_status']); ?></span>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Purchases Tab -->
                        <div class="tab-pane" id="purchases_tab">
                            <div class="box box-danger" style="border-top: none;">
                                <div class="box-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th class="col-number">Order #</th>
                                                    <th class="col-date">Date</th>
                                                    <th class="col-name">Supplier</th>
                                                    <th class="col-amount">Subtotal</th>
                                                    <th class="col-amount">VAT</th>
                                                    <th class="col-amount">W/Tax</th>
                                                    <th class="col-amount">Total</th>
                                                    <th class="col-amount">Paid</th>
                                                    <th class="col-amount">Balance</th>
                                                    <th class="col-status">Payment</th>
                                                    <th class="col-status">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($purchase = $purchases_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="col-number"><?php echo htmlspecialchars($purchase['purchase_number']); ?></td>
                                                    <td class="col-date"><?php echo date('Y-m-d', strtotime($purchase['purchase_date'])); ?></td>
                                                    <td class="col-name" title="<?php echo htmlspecialchars($purchase['company_name']); ?>">
                                                        <?php echo htmlspecialchars($purchase['company_name']); ?>
                                                    </td>
                                                    <td class="col-amount"><?php echo number_format($purchase['subtotal'], 2); ?></td>
                                                    <td class="col-amount"><?php echo number_format($purchase['vat_amount'], 2); ?></td>
                                                    <td class="col-amount"><?php echo number_format($purchase['withholding_amount'], 2); ?></td>
                                                    <td class="col-amount"><?php echo number_format($purchase['grand_total'], 2); ?></td>
                                                    <td class="col-amount"><?php echo number_format($purchase['paid_amount'], 2); ?></td>
                                                    <td class="col-amount"><?php echo number_format($purchase['balance'], 2); ?></td>
                                                    <td class="col-status">
                                                        <?php
                                                        $label_class = '';
                                                        switch($purchase['payment_status']) {
                                                            case 'paid': $label_class = 'label-success'; break;
                                                            case 'partial': $label_class = 'label-warning'; break;
                                                            case 'unpaid': $label_class = 'label-danger'; break;
                                                        }
                                                        ?>
                                                        <span class="label <?php echo $label_class; ?>"><?php echo ucfirst($purchase['payment_status']); ?></span>
                                                    </td>
                                                    <td class="col-status">
                                                        <?php
                                                        $status_label = $purchase['status'] == 1 ? 'Active' : 'Inactive';
                                                        $status_class = $purchase['status'] == 1 ? 'label-info' : 'label-default';
                                                        ?>
                                                        <span class="label <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
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
    </section>
</div>

<?php require_once '../includes/footer.php'; ?> 