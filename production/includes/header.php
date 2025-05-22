<?php 
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Set content type
header('Content-Type: text/html; charset=UTF-8');

// Include core files
require_once dirname(__FILE__) . '/../php_action/core.php';
require_once dirname(__FILE__) . '/../php_action/db_connect.php';
require_once dirname(__FILE__) . '/auth_check.php';

// Check if user is logged in
if(!isset($_SESSION['userId'])) {
    header('location: ../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Production Management System</title>

    <!-- Meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <!-- CSS Files -->
    <!-- bootstrap -->
    <link rel="stylesheet" href="../assests/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assests/bootstrap/css/bootstrap-theme.min.css">
    
    <!-- font awesome -->
    <link rel="stylesheet" href="../assests/font-awesome/css/font-awesome.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="../assests/plugins/datatables/css/dataTables.bootstrap.min.css">
    <link rel="stylesheet" href="../assests/plugins/datatables/css/buttons.bootstrap.min.css">
    
    <!-- Select2 -->
    <link rel="stylesheet" href="../assests/plugins/select2/css/select2.min.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="../assests/plugins/sweetalert2/css/sweetalert2.min.css">
    
    <!-- file input -->
    <link rel="stylesheet" href="../assests/plugins/fileinput/css/fileinput.min.css">
    
    <!-- jquery ui -->  
    <link rel="stylesheet" href="../assests/jquery-ui/jquery-ui.min.css">

    <!-- Toastr notifications -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    
    <!-- Moment.js for time formatting -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    
    <!-- custom css -->
    <link rel="stylesheet" href="../custom/css/custom.css">

    <!-- Bootstrap Select CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">

    <!-- DataTables -->
    <link rel="stylesheet" href="../assests/plugins/datatables/jquery.dataTables.min.css">

    <!-- Report System Styles -->
    <style>
        /* Table Alignment Fixes */
        .table {
            text-align: left;
        }
        
        .table th {
            text-align: left;
        }
        
        .table td {
            text-align: left;
        }

        /* Right align specific columns */
        .table td.text-right,
        .table th.text-right {
            text-align: right;
        }

        /* Center align specific columns */
        .table td.text-center,
        .table th.text-center {
            text-align: center;
        }

        /* Numeric columns alignment */
        .table td.numeric,
        .table th.numeric {
            text-align: right;
        }

        /* Modal content alignment */
        .modal-content {
            text-align: left;
        }

        /* Form alignment */
        .form-horizontal {
            text-align: left;
        }

        /* Report System Specific Styles */
        .report-filters {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .report-actions {
            margin-bottom: 20px;
        }

        .schedule-badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .schedule-active { background: #28a745; color: white; }
        .schedule-inactive { background: #ffc107; color: #212529; }
        .schedule-error { background: #dc3545; color: white; }

        /* Report Cards */
        .report-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .report-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }

        .report-card .report-icon {
            font-size: 24px;
            margin-bottom: 15px;
            color: #007bff;
        }

        .report-card .report-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .report-card .report-description {
            color: #6c757d;
            margin-bottom: 15px;
        }

        /* Schedule Modal */
        .schedule-form label {
            font-weight: 600;
            color: #495057;
        }

        .schedule-form .form-control {
            border-radius: 4px;
            border: 1px solid #ced4da;
        }

        .schedule-form .select2-container .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
        }

        /* DataTable Customization */
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 6px 12px;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 6px 12px;
        }

        /* Export Buttons */
        .dt-buttons .btn {
            margin-right: 5px;
            border-radius: 4px;
        }

        /* Filter Collapse Panel */
        .filter-panel {
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .filter-panel .panel-heading {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
        }

        .filter-panel .panel-body {
            padding: 15px;
        }

        /* Date Range Picker */
        .daterangepicker {
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .daterangepicker .ranges li.active {
            background-color: #007bff;
        }

        .profile-img-small {
            width: 25px;
            height: 25px;
            margin-right: 5px;
            border: 2px solid #fff;
            display: inline-block;
            vertical-align: middle;
        }

        .navbar-nav > li > .dropdown-menu {
            margin-top: 0;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
            box-shadow: 0 6px 12px rgba(0,0,0,.175);
        }

        .dropdown-menu > li > a {
            padding: 8px 20px;
        }

        .dropdown-menu > li > a > i {
            margin-right: 10px;
            width: 16px;
        }

        .divider {
            margin: 5px 0;
        }

        /* Notification Styles */
        .notification-dropdown {
            min-width: 300px;
            padding: 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-item {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        .notification-item.unread {
            background-color: #e8f4fe;
        }
        .notification-icon {
            float: left;
            margin-right: 10px;
        }
        .notification-content {
            margin-left: 30px;
        }
        .notification-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .notification-message {
            color: #666;
            font-size: 0.9em;
        }
        .notification-time {
            color: #999;
            font-size: 0.8em;
            margin-top: 5px;
        }
        #notificationBadge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7em;
            display: none;
        }

        .floating-pos-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: #28a745;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .floating-pos-btn:hover {
            transform: scale(1.1);
            background-color: #218838;
            color: white;
            text-decoration: none;
        }
        
        .floating-pos-btn i {
            font-size: 24px;
        }

        /* Custom styling for the under development popup */
        .under-development-popup {
            border-radius: 15px;
            font-family: Arial, sans-serif;
        }
    </style>

    <!-- JavaScript Files -->
    <!-- jquery -->
    <script src="../assests/jquery/jquery.min.js"></script>
    
    <!-- jquery ui -->
    <script src="../assests/jquery-ui/jquery-ui.min.js"></script>
    
    <!-- bootstrap js -->
    <script src="../assests/bootstrap/js/bootstrap.min.js"></script>
    
    <!-- moment js -->
    <script src="../assests/plugins/moment/moment.min.js"></script>
    
    <!-- DataTables -->
    <script src="../assests/plugins/datatables/js/jquery.dataTables.min.js"></script>
    <script src="../assests/plugins/datatables/js/dataTables.bootstrap.min.js"></script>
    <script src="../assests/plugins/datatables/js/dataTables.buttons.min.js"></script>
    <script src="../assests/plugins/datatables/js/buttons.bootstrap.min.js"></script>
    <script src="../assests/plugins/datatables/js/buttons.html5.min.js"></script>
    <script src="../assests/plugins/datatables/js/buttons.print.min.js"></script>
    <script src="../assests/plugins/datatables/js/jszip.min.js"></script>
    <script src="../assests/plugins/datatables/js/pdfmake.min.js"></script>
    <script src="../assests/plugins/datatables/js/vfs_fonts.js"></script>
    
    <!-- Select2 -->
    <script src="../assests/plugins/select2/js/select2.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="../assests/plugins/sweetalert2/js/sweetalert2.all.min.js"></script>
    
    <!-- file input -->
    <script src="../assests/plugins/fileinput/js/fileinput.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    
    <!-- Toastr notifications -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>

    <!-- Common JS -->
    <script src="custom/js/common.js"></script>
    <script src="custom/js/active.js"></script>

    <!-- Bootstrap Select JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>

    <script>
    $(document).ready(function() {
        // Add click handler for expense management link
        $('#navExpenseManagement a').on('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Under Development',
                text: 'This new feature is under development. You will be notified when it\'s ready.',
                icon: 'info',
                confirmButtonText: 'Got it!',
                confirmButtonColor: '#3085d6',
                customClass: {
                    popup: 'under-development-popup'
                }
            });
        });
    });
    </script>
</head>
<body>

<nav class="navbar navbar-default navbar-static-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="../dashboard.php">Production Management System</a>
        </div>

        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li id="navDashboard"><a href="dashboard.php"><i class="fa fa-tachometer"></i> Dashboard</a></li>
                
                <!-- Raw Materials Management -->
                <li class="dropdown" id="navRawMaterials">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-cubes"></i> Raw Materials <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li id="navManageRawMaterials"><a href="raw_materials.php"><i class="fa fa-list"></i> Manage Raw Materials</a></li>
                        <li id="navManageCategories"><a href="raw_material_categories.php"><i class="fa fa-tags"></i> Manage Categories</a></li>
                        <li id="navManageWarehouses"><a href="warehouses.php"><i class="fa fa-building"></i> Manage Warehouses</a></li>
                        <li id="navStockMovements"><a href="stock_movements.php"><i class="fa fa-exchange"></i> Stock Movements</a></li>
                        <li id="navLowStock"><a href="low_stock.php"><i class="fa fa-warning"></i> Low Stock Alert</a></li>
                    </ul>
                </li>

                <!-- Purchase Management -->
                <li class="dropdown" id="navPurchase">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-shopping-cart"></i> Purchase <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li id="navPurchaseOrders"><a href="purchase.php"><i class="fa fa-file-text"></i> Purchase Orders</a></li>
                        <li id="navSuppliers"><a href="suppliers.php"><i class="fa fa-truck"></i> Suppliers</a></li>
                        <li id="navPurchaseReports"><a href="purchase_reports.php"><i class="fa fa-chart-bar"></i> Purchase Reports</a></li>
                        <li id="navPurchasePayment"><a href="purchase_payments.php"><i class="fa fa-credit-card"></i> Purchase Payment</a></li>
                    </ul>
                </li>

                <!-- Sales Management -->
                <li class="dropdown" id="navSales">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-dollar-sign"></i> Sales <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li id="navSalesOrders"><a href="sales.php"><i class="fa fa-file-invoice"></i> Sales Orders</a></li>
                        <li id="navClients"><a href="clients.php"><i class="fa fa-users"></i> Clients</a></li>
                        <li id="navSalesReports"><a href="sales_reports.php"><i class="fa fa-chart-line"></i> Sales Reports</a></li>
                        <li id="navSalesPayment"><a href="sales_payment.php"><i class="fa fa-credit-card"></i> Sales Payment</a></li>
                    </ul>
                </li>

                <!-- Production Management -->
                <li class="dropdown" id="navProduction">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-industry"></i> Production <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li id="navProductionOrders"><a href="production_orders.php"><i class="fa fa-clipboard"></i> Production Orders</a></li>
                        <li id="navBOM"><a href="bill_of_materials.php"><i class="fa fa-sitemap"></i> Bill of Materials</a></li>
                        <li id="navInventoryTracking"><a href="inventory_tracking.php"><i class="fa fa-sitemap"></i> Inventory Tracking</a></li>
                        <li id="navProducts"><a href="products.php"><i class="fa fa-cube"></i> Products</a></li>
                        <li id="navQualityControl"><a href="quality_control.php"><i class="fa fa-check-square-o"></i> Quality Control</a></li>
                        <li id="navExpenseManagement"><a href="expense_management.php"><i class="fa fa-money"></i> Expense Management</a></li>
                    </ul>
                </li>

                <!-- Reports -->
                <li class="dropdown" id="navReports">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-chart-bar"></i> Reports <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                        <li id="navReportDashboard"><a href="reports.php"><i class="fa fa-tachometer-alt"></i> Report Dashboard</a></li>
                        <li id="navNewReports"><a href="newreports.php"><i class="fa fa-industry"></i> Comprehensive Reports</a></li>
                        <li id="navAccountPayments"><a href="account_payments.php"><i class="fa fa-credit-card"></i> Account Payments</a></li>
                        <li class="divider"></li>
                        <li id="navAnnualReport"><a href="annual_report.php"><i class="fa fa-chart-bar"></i> Annual Business Report</a></li>
                        <li class="divider"></li>
                        <li id="navInventoryReport"><a href="reports.php?type=inventory"><i class="fa fa-boxes"></i> Inventory Report</a></li>
                        <li id="navStockMovement"><a href="reports.php?type=stock_movement"><i class="fa fa-exchange-alt"></i> Stock Movement</a></li>
                        <li id="navProductionReport"><a href="reports.php?type=production"><i class="fa fa-industry"></i> Production Report</a></li>
                        <li id="navMaterialUsage"><a href="reports.php?type=material_usage"><i class="fa fa-cubes"></i> Material Usage</a></li>
                        <li id="navQualityReport"><a href="reports.php?type=quality"><i class="fa fa-check-circle"></i> Quality Report</a></li>
                        <li id="navCostAnalysis"><a href="reports.php?type=cost_analysis"><i class="fa fa-dollar-sign"></i> Cost Analysis</a></li>
                        <li class="divider"></li>
                        <li id="navScheduledReports"><a href="reports.php?view=scheduled"><i class="fa fa-clock"></i> Scheduled Reports</a></li>
                        <li id="navReportSettings"><a href="reports.php?view=settings"><i class="fa fa-cog"></i> Report Settings</a></li>
                    </ul>
                </li>
               

                <!-- Settings -->
                <li class="dropdown" id="navSettings">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-cog"></i> Settings <span class="caret"></span>
                    </a>
                    <ul class="dropdown-menu">
                    <li id="navUsers"><a href="profile.php"><i class="fa fa-users"></i> Profile</a></li>
                        <li id="navUsers"><a href="../user.php"><i class="fa fa-users"></i> Manage Users</a></li>
                        <li id="navSystemSettings"><a href="settings.php"><i class="fa fa-wrench"></i> System Settings</a></li>
                        <li id="navPrintSettings"><a href="../print_settings_management.php"><i class="fa fa-print"></i> Print Settings</a></li>

                    </ul>
                </li>
            </ul>
            
        </div>
    </div>
</nav>

<div class="container"><?php if(isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <?php 
        echo $_SESSION['success_message']; 
        unset($_SESSION['success_message']);
        ?>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <?php 
        echo $_SESSION['error_message']; 
        unset($_SESSION['error_message']);
        ?>
    </div>
<?php endif; ?> 

<!-- Floating POS Button -->
<?php if(basename($_SERVER['PHP_SELF']) !== 'pos.php'): ?>
<a href="pos.php" class="floating-pos-btn" title="Open POS">
    <i class="fa fa-shopping-cart"></i>
</a>
<?php endif; ?>

<!-- Add notification.js to footer scripts -->
</body>
</html> 