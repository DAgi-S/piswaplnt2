<?php 
require_once 'auth.php';
?>
<!DOCTYPE html>
<html>
<head>
	<title>Pi Stock</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<!-- jquery first -->
	<script src="assests/jquery/jquery.min.js"></script>
	
	<!-- bootstrap css -->
	<link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="assests/bootstrap/css/bootstrap-theme.min.css">
	
	<!-- DataTables CSS -->
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.7/css/responsive.dataTables.min.css">
	
	<!-- font awesome -->
	<link rel="stylesheet" href="assests/font-awesome/css/font-awesome.min.css">
	
	<!-- custom css -->
	<link rel="stylesheet" href="custom/css/custom.css">
	
	<!-- dashboard css -->
	<link rel="stylesheet" href="custom/css/dashboard.css">
	
	<!-- file input -->
	<link rel="stylesheet" href="assests/plugins/fileinput/css/fileinput.min.css">
	
	<!-- jquery ui -->  
	<link rel="stylesheet" href="assests/jquery-ui/jquery-ui.min.css">
	
	<!-- Select2 CSS -->
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	
	<!-- SweetAlert2 CSS -->
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
	
	<!-- bootstrap js -->
	<script src="assests/bootstrap/js/bootstrap.min.js"></script>
	
	<!-- DataTables JS -->
	<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.print.min.js"></script>
	<script src="https://cdn.datatables.net/responsive/2.2.7/js/dataTables.responsive.min.js"></script>
	
	<!-- jquery ui -->
	<script src="assests/jquery-ui/jquery-ui.min.js"></script>
	
	<!-- Select2 JS -->
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
	
	<!-- SweetAlert2 JS -->
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Enhanced navbar styling */
        .navbar-default {
            background-color: #fff;
            border-color: #e7e7e7;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            padding: 5px 15px;
        }
        
        .navbar-nav > li > a {
            padding-top: 15px;
            padding-bottom: 15px;
            font-weight: 500;
        }
        
        .navbar-nav > li > a:hover {
            background-color: #f8f9fa;
        }
        
        .navbar-nav > .active > a,
        .navbar-nav > .active > a:hover,
        .navbar-nav > .active > a:focus {
            background-color: #f8f9fa;
            color: #337ab7;
        }
        
        /* Megamenu styles */
        .megamenu {
            position: static !important;
        }
        
        .megamenu .dropdown-menu {
            width: 100%;
            padding: 20px;
            left: 0;
            right: 0;
        }
        
        .megamenu .dropdown-menu .dropdown-header {
            padding: 10px 0;
            font-size: 14px;
            font-weight: bold;
            color: #337ab7;
            border-bottom: 1px solid #eee;
            margin-bottom: 10px;
        }
        
        .megamenu .dropdown-menu .col-md-3 {
            margin-bottom: 15px;
        }
        
        .megamenu-list {
            padding-left: 0;
            list-style: none;
        }
        
        .megamenu-list li {
            padding: 5px 0;
        }
        
        .megamenu-list a {
            color: #555;
            text-decoration: none;
            font-size: 13px;
            display: block;
            padding: 3px 0;
        }
        
        .megamenu-list a:hover {
            color: #337ab7;
        }
        
        .megamenu-list a i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }
        
        /* Standard dropdown styling */
        .dropdown-menu {
            border-radius: 3px;
            box-shadow: 0 6px 12px rgba(0,0,0,.175);
            padding: 5px 0;
        }
        
        .dropdown-menu > li > a {
            padding: 8px 20px;
            font-size: 13px;
            color: #333;
        }
        
        .dropdown-menu > li > a:hover {
            background-color: #f5f5f5;
            color: #337ab7;
        }
        
        .dropdown-menu > li > a i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }
        
        /* Quick action button */
        .quick-action-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .quick-action-btn .btn-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 20px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        
        .quick-action-btn .dropdown-menu {
            border-radius: 3px;
            min-width: 200px;
            padding: 5px 0;
            right: 0;
            left: auto;
        }
        
        /* Mobile optimizations */
        @media (max-width: 767px) {
            .navbar-brand img {
                max-height: 30px;
            }
            
            .navbar-toggle {
                margin-top: 10px;
                margin-bottom: 10px;
            }
            
            .megamenu .dropdown-menu {
                padding: 10px;
            }
            
            .megamenu-list a {
                padding: 5px 0;
            }
        }
        
        /* System indicators */
        .system-indicator {
            display: inline-block;
            padding: 2px 6px;
            margin-right: 5px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .main-system {
            background-color: #3498db;
            color: white;
        }
        
        .production-system {
            background-color: #2ecc71;
            color: white;
        }
        
        .gps-system {
            background-color: #e74c3c;
            color: white;
        }
        
        /* Enhanced dropdown animations */
        .dropdown-menu {
            display: block;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
        }
        
        .dropdown.open > .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        /* Nav tabs integration */
        .nav-tabs {
            margin-bottom: 15px;
        }
        
        .nav-tabs > li > a {
            color: #555;
        }
        
        .nav-tabs > li.active > a,
        .nav-tabs > li.active > a:hover,
        .nav-tabs > li.active > a:focus {
            color: #337ab7;
        }
    </style>

    <script>
    $(document).ready(function() {
        // Dropdown hover functionality for desktop
        if ($(window).width() > 767) {
            $('.navbar-nav .dropdown').hover(
                function() {
                    $(this).addClass('open');
                },
                function() {
                    $(this).removeClass('open');
                }
            );
        }
        
        // Active page detection
        var currentPath = window.location.pathname;
        var filename = currentPath.substring(currentPath.lastIndexOf('/')+1);
        
        // Mark active based on filename
        $('.navbar-nav li').each(function() {
            var link = $(this).find('a:first').attr('href');
            if (link && link !== '#') {
                if (filename === link || currentPath.indexOf(link) !== -1) {
                    $(this).addClass('active');
                    $(this).parents('.dropdown').addClass('active');
                }
            }
        });
        
        // Quick action button toggle
        $('#quickActionBtn').on('click', function(e) {
            e.stopPropagation();
            $(this).toggleClass('open');
        });
        
        // Close quick action on document click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#quickActionBtn').length) {
                $('#quickActionBtn').removeClass('open');
            }
        });
        
        // Handle quick action clicks
        $('.quick-action-item').on('click', function(e) {
            var action = $(this).data('action');
            switch(action) {
                case 'add-product':
                    // Store flag to open modal when redirected
                    sessionStorage.setItem('openAddProductModal', 'true');
                    break;
            }
        });
        
        // Check for stored actions
        if (window.location.pathname.indexOf('products.php') > -1 && 
            sessionStorage.getItem('openAddProductModal') === 'true') {
            sessionStorage.removeItem('openAddProductModal');
            setTimeout(function() {
                $('#addProductModalBtn').click();
            }, 500);
        }
    });
    </script>
</head>
<body>
	<nav class="navbar navbar-default navbar-static-top">
		<div class="container">
            <!-- Brand and toggle -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-navbar" aria-expanded="false">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="<?php echo isset($store_url) ? $store_url : '/dashboard.php'; ?>">
                    <?php if(isset($settings['company_logo']) && !empty($settings['company_logo'])): ?>
                        <img src="uploads/<?php echo $settings['company_logo']; ?>" alt="Logo" style="max-height: 40px;">
                    <?php else: ?>
                        <img src="logo.png" alt="Default Logo" style="max-height: 40px;">
                    <?php endif; ?>
                </a>
            </div>

            <!-- Collect the nav links and other content for toggling -->
            <div class="collapse navbar-collapse" id="main-navbar">
                <ul class="nav navbar-nav navbar-right">
                    <!-- Dashboard -->
                    <?php if(hasPermission('dashboard.view') || hasPermission('view_dashboard')): ?>
                    <li id="navDashboard">
                        <a href="dashboard.php"><i class="glyphicon glyphicon-list-alt"></i> Dashboard</a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Products -->
                    <?php if(hasPermission('product.view') || hasPermission('view_product')): ?>
                    <li id="navProduct">
                        <a href="products.php"><i class="glyphicon glyphicon-ruble"></i> Products</a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Invoice -->
                    <?php if(hasPermission('invoice.view') || hasPermission('view_invoice')): ?>
                    <li class="dropdown" id="navOrder">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="glyphicon glyphicon-shopping-cart"></i> Invoice <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if(hasPermission('invoice.edit') || hasPermission('edit_invoice')): ?>
                            <li id="topNavManageOrder">
                                <a href="orders.php?o=manord"><i class="glyphicon glyphicon-edit"></i> Manage Invoice</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if(hasPermission('quotation.edit') || hasPermission('edit_invoice')): ?>
                            <li id="topNavManageQuotation">
                                <a href="manageQuotations.php"><i class="glyphicon glyphicon-edit"></i> Manage Quotation</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if(hasPermission('supplier.edit') || hasPermission('edit_invoice')): ?>
                            <li id="topNavManageSupplier">
                                <a href="manageSuppliers.php"><i class="glyphicon glyphicon-briefcase"></i> Manage Suppliers</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if(hasPermission('purchase.edit') || hasPermission('edit_invoice')): ?>
                            <li id="topNavManagePurchase">
                                <a href="managePurchases.php"><i class="glyphicon glyphicon-shopping-cart"></i> Manage Purchase</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Accounts -->
                    <?php if(hasPermission('account.view') || hasPermission('view_account')): ?>
                    <li class="dropdown" id="navAccounts">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="glyphicon glyphicon-credit-card"></i> Accounts <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if(hasPermission('account.view')): ?>
                            <li id="accountTransactions">
                                <a href="account_transactions.php"><i class="glyphicon glyphicon-transfer"></i> Transaction History</a>
                            </li>
                            <li id="manageAccounts">
                                <a href="accounts.php"><i class="glyphicon glyphicon-cog"></i> Manage Accounts</a>
                            </li>
                            <li id="auditReport">
                                <a href="audit_report.php"><i class="glyphicon glyphicon-list-alt"></i> Audit Report</a>
                            </li>
                            <li id="annualReport">
                                <a href="annual_report.php"><i class="glyphicon glyphicon-calendar"></i> Annual Report</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if(hasPermission('account.view')): ?>
                            <li role="separator" class="divider"></li>
                            <li>
                                <a href="digital_categories.php"><i class="fa fa-tags"></i> Digital Categories</a>
                            </li>
                            <li>
                                <a href="categorized_transactions.php"><i class="fa fa-filter"></i> Categorized Transactions</a>
                            </li>
                            <li>
                                <a href="category_report.php"><i class="fa fa-bar-chart"></i> Category Report</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Letter -->
                    <?php if(hasPermission('letter.view') || hasPermission('view_letter')): ?>
                    <li class="dropdown" id="navLetter">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="glyphicon glyphicon-file"></i> Letter <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if(hasPermission('letter.create') || hasPermission('manage_templates')): ?>
                            <li id="topNavLetterTemplates">
                                <a href="letter_templates.php"><i class="glyphicon glyphicon-list"></i> Letter Templates</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if(hasPermission('letter.create') || hasPermission('create_letter')): ?>
                            <li id="topNavGPSLetter">
                                <a href="gps_letter.php"><i class="glyphicon glyphicon-pencil"></i> Prepare Letter</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if(hasPermission('letter.view') || hasPermission('view_letter')): ?>
                            <li id="generatedLettersNav">
                                <a href="generated_letters.php"><i class="glyphicon glyphicon-list"></i> Generated Letters</a>
                            </li>
                            <?php endif; ?>

                            <?php if(hasPermission('warranty_certificate.view') || hasPermission('view_warranty_certificate')): ?>
                            <li id="generatedLettersNav">
                                <a href="warranty/index.php"><i class="glyphicon glyphicon-list"></i> Warranty Certificate</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Digital Swap -->
                    <?php if(hasPermission('digitalswap.view') || hasPermission('digitalswap')): ?>
                    <li class="dropdown" id="navDigitalSwap">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="glyphicon glyphicon-transfer"></i> Tra Swap <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li id="manageDigitalSwap">
                                <a href="digitalswap.php"><i class="glyphicon glyphicon-list-alt"></i> Manage Digital Swap</a>
                            </li>
                            
                            <?php if(hasPermission('digitalswap.analytics.view') || hasPermission('view_digitalswap_analytics')): ?>
                            <li id="digitalSwapAnalytics">
                                <a href="digitalswap-analytics.php"><i class="glyphicon glyphicon-stats"></i> Analytics</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if(hasPermission('digitalswap.reports.view') || hasPermission('view_digitalswap_report')): ?>
                            <li id="digitalSwapReport">
                                <a href="digitalswap-report.php"><i class="glyphicon glyphicon-check"></i> Digital Swap Report</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Business -->
                    <?php if(hasPermission('business.manage') || hasPermission('manage_business')): ?>
                    <li class="dropdown" id="navGPSBusiness">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="glyphicon glyphicon-briefcase"></i> Manufacturing <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li id="topNavProduction">
                                <a href="production/dashboard.php"><i class="glyphicon glyphicon-refresh"></i> Production</a>
                            </li>
                            <li id="businessCycles">
                                <a href="production/raw_material.php"><i class="glyphicon glyphicon-box"></i>Raw Material</a>
                            </li>
                            <li id="profitDistribution">
                                <a href="production/purchase.php"><i class="glyphicon glyphicon-usd"></i> Purchase</a>
                            </li>
                            <li id="businessReports">
                                <a href="production/sales.php"><i class="glyphicon glyphicon-stats"></i> Sales</a>
                            </li>
                        </ul>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Settings -->
                    <?php if(isset($_SESSION['userId'])): ?>
                    <li class="dropdown" id="navSetting">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                            <i class="glyphicon glyphicon-user"></i> Settings <span class="caret"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li id="topNavProfile">
                                <a href="profile.php"><i class="glyphicon glyphicon-user"></i> Your Profile</a>
                            </li>
                            <li id="topNavSessions">
                                <a href="manage_sessions.php"><i class="glyphicon glyphicon-shield"></i> Active Sessions</a>
                            </li>
                            
                            <?php if(hasPermission('guest.view') || hasPermission('manage_guests')): ?>
                            <li class="divider"></li>
                            <li id="topNavSetting">
                                <a href="setting.php"><i class="glyphicon glyphicon-wrench"></i> General Settings</a>
                            </li>
                            <li id="topNavUser">
                                <a href="user.php"><i class="glyphicon glyphicon-user"></i> User Management</a>
                            </li>
                            <li id="topNavRole">
                                <a href="role_management.php"><i class="glyphicon glyphicon-lock"></i> Role Management</a>
                            </li>
                            
                            <?php if(hasPermission('guest.view') || hasPermission('manage_guests')): ?>
                            <li id="topNavGuests">
                                <a href="manage_guests.php"><i class="glyphicon glyphicon-user"></i> Manage Guests</a>
                            </li>
                            <li id="topNavBusiness">
                                <a href="gpsBusiness/gps_payments.php"><i class="glyphicon glyphicon-usd"></i> Business Management</a>
                            </li>
                            <li id="topNavSystem">
                                <a href="system_settings.php"><i class="glyphicon glyphicon-cog"></i> System Settings</a>
                            </li>
                            <li id="topNavPermission">
                                <a href="check_user_permissions.php"><i class="glyphicon glyphicon-check"></i> Check User Permissions</a>
                            </li>
                            <?php endif; ?>
                            
                            <li class="divider"></li>
                            <?php if(hasPermission('header.main.view') || hasPermission('manage_headers')): ?>
                            <li id="topNavHeaders">
                                <a href="header_management.php"><i class="glyphicon glyphicon-list"></i> Header Management</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if(hasPermission('settings.print.manage') || hasPermission('settings.manage') || 
                                   hasPermission('settings.access') || hasPermission('system.settings.access')): ?>
                            <li id="topNavPrintSettings">
                                <a href="print_settings_management.php"><i class="glyphicon glyphicon-print"></i> Print Settings</a>
                            </li>
                            <?php endif; ?>
                            
                            <li class="divider"></li>
                            <?php if(hasPermission('api.telegram.configure') || hasPermission('manage_telegram_bot')): ?>
                            <li id="topNavTelegram">
                                <a href="telegram_bot_settings.php"><i class="glyphicon glyphicon-send"></i> Telegram Bot Settings</a>
                            </li>
                            <?php endif; ?>
                            
                            <li class="divider"></li>
                            <?php if(hasPermission('access.audit') || hasPermission('view_audit_log')): ?>
                            <li id="topNavAudit">
                                <a href="audit.php"><i class="glyphicon glyphicon-eye-open"></i> Audit Log</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if(hasPermission('system.email.view')): ?>
                            <li id="topNavEmailReport">
                                <a href="digitalswap-email-report.php"><i class="glyphicon glyphicon-envelope"></i> Email Report</a>
                            </li>
                            <?php endif; ?>
                            
                            <li class="divider"></li>
                            <?php if(hasPermission('product.brand.manage')): ?>
                            <li id="topNavImportBrand">
                                <a href="importbrand.php"><i class="glyphicon glyphicon-import"></i> Import Brand</a>
                            </li>
                            <li id="topNavBrand">
                                <a href="brand.php"><i class="glyphicon glyphicon-tag"></i> Manage Brands</a>
                            </li>
                            <?php endif; ?>
                            
                            <li id="topNavLogout">
                                <a href="logout.php"><i class="glyphicon glyphicon-log-out"></i> Logout</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Messages section -->
        <?php if(isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?php 
            echo $_SESSION['success_message']; 
            unset($_SESSION['success_message']);
            ?>
        </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <?php 
            echo $_SESSION['error_message']; 
            unset($_SESSION['error_message']);
            ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Quick Action Button -->
    <div class="quick-action-btn dropdown" id="quickActionBtn">
        <button class="btn btn-primary btn-circle" data-toggle="dropdown">
            <i class="glyphicon glyphicon-plus"></i>
        </button>
        <ul class="dropdown-menu">
            <li>
                <a href="digitalswap.php" class="quick-action-item" data-action="add-swap">
                    <i class="glyphicon glyphicon-transfer"></i> Add Digital Swap
                </a>
            </li>
            <li>
                <a href="orders.php?o=add" class="quick-action-item" data-action="add-order">
                    <i class="glyphicon glyphicon-shopping-cart"></i> Add New Order
                </a>
            </li>
            <li>
                <a href="quotation.php" class="quick-action-item" data-action="add-quotation">
                    <i class="glyphicon glyphicon-file"></i> Add Quotation
                </a>
            </li>
            <li>
                <a href="products.php" class="quick-action-item" data-action="add-product" id="quickAddProduct">
                    <i class="glyphicon glyphicon-plus-sign"></i> Add Product
                </a>
            </li>
            <li>
                <a href="gps_letter.php" class="quick-action-item" data-action="add-letter">
                    <i class="glyphicon glyphicon-road"></i> Add GPS Letter
                </a>
            </li>
        </ul>
    </div>
</body>
</html>
