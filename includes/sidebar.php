<?php
require_once 'auth.php';
?>

<div class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo isset($store_url) ? $store_url : '/'; ?>" class="sidebar-brand">
            <?php if(isset($settings['company_logo']) && !empty($settings['company_logo'])): ?>
                <img src="uploads/<?php echo $settings['company_logo']; ?>" alt="Logo" style="max-height: 40px;">
            <?php else: ?>
                <img src="logo.png" alt="Default Logo" style="max-height: 40px;">
            <?php endif; ?>
        </a>
    </div>

    <!-- User Profile Section -->
    <div class="user-profile">
        <img src="<?php echo isset($_SESSION['user_image']) ? 'uploads/'.$_SESSION['user_image'] : 'assets/img/default-avatar.png'; ?>" 
             alt="User Avatar" class="user-avatar">
        <h5 class="user-name"><?php echo $_SESSION['userName']; ?></h5>
        <p class="user-role"><?php echo $_SESSION['user_role']; ?></p>
    </div>

    <!-- Search Box -->
    <div class="sidebar-search">
        <input type="text" class="search-input" placeholder="Search menu...">
    </div>

    <!-- Main Navigation -->
    <ul class="sidebar-nav">
        <!-- Main System -->
        <div class="system-indicator main-system">Main System</div>
        
        <?php if(hasPermission('dashboard.view') || hasPermission('view_dashboard')): ?>
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link">
                <i class="glyphicon glyphicon-list-alt"></i>
                <span class="nav-label">Dashboard</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if(hasPermission('product.view') || hasPermission('view_product')): ?>
        <li class="nav-item">
            <a href="products.php" class="nav-link">
                <i class="glyphicon glyphicon-ruble"></i>
                <span class="nav-label">Products</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if(hasPermission('invoice.view') || hasPermission('view_invoice')): ?>
        <li class="nav-item">
            <a href="#invoiceSubmenu" class="nav-link" data-toggle="collapse" data-target="#invoiceSubmenu">
                <i class="glyphicon glyphicon-shopping-cart"></i>
                <span class="nav-label">Invoice</span>
                <i class="fa fa-angle-down submenu-arrow"></i>
            </a>
            <ul class="sidebar-submenu" id="invoiceSubmenu">
                <?php if(hasPermission('invoice.edit') || hasPermission('edit_invoice')): ?>
                <li><a href="orders.php?o=manord"><i class="glyphicon glyphicon-edit"></i> Manage Invoice</a></li>
                <?php endif; ?>
                <?php if(hasPermission('quotation.edit') || hasPermission('edit_invoice')): ?>
                <li><a href="manageQuotations.php"><i class="glyphicon glyphicon-edit"></i> Manage Quotation</a></li>
                <?php endif; ?>
                <?php if(hasPermission('supplier.edit') || hasPermission('edit_invoice')): ?>
                <li><a href="manageSuppliers.php"><i class="glyphicon glyphicon-briefcase"></i> Manage Suppliers</a></li>
                <?php endif; ?>
                <?php if(hasPermission('purchase.edit') || hasPermission('edit_invoice')): ?>
                <li><a href="managePurchases.php"><i class="glyphicon glyphicon-shopping-cart"></i> Manage Purchase</a></li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <!-- Production System -->
        <div class="system-indicator production-system">Production System</div>

        <li class="nav-item">
            <a href="#rawMaterialsSubmenu" class="nav-link" data-toggle="collapse" data-target="#rawMaterialsSubmenu">
                <i class="fa fa-cubes"></i>
                <span class="nav-label">Raw Materials</span>
                <i class="fa fa-angle-down submenu-arrow"></i>
            </a>
            <ul class="sidebar-submenu" id="rawMaterialsSubmenu">
                <li><a href="production/raw_materials.php"><i class="fa fa-list"></i> Manage Raw Materials</a></li>
                <li><a href="production/raw_material_categories.php"><i class="fa fa-tags"></i> Manage Categories</a></li>
                <li><a href="production/warehouses.php"><i class="fa fa-building"></i> Manage Warehouses</a></li>
                <li><a href="production/stock_movements.php"><i class="fa fa-exchange"></i> Stock Movements</a></li>
                <li><a href="production/low_stock.php"><i class="fa fa-warning"></i> Low Stock Alert</a></li>
            </ul>
        </li>

        <li class="nav-item">
            <a href="#productionSubmenu" class="nav-link" data-toggle="collapse" data-target="#productionSubmenu">
                <i class="fa fa-industry"></i>
                <span class="nav-label">Production</span>
                <i class="fa fa-angle-down submenu-arrow"></i>
            </a>
            <ul class="sidebar-submenu" id="productionSubmenu">
                <li><a href="production/production_orders.php"><i class="fa fa-clipboard"></i> Production Orders</a></li>
                <li><a href="production/bill_of_materials.php"><i class="fa fa-sitemap"></i> Bill of Materials</a></li>
                <li><a href="production/inventory_tracking.php"><i class="fa fa-sitemap"></i> Inventory Tracking</a></li>
                <li><a href="production/quality_control.php"><i class="fa fa-check-square-o"></i> Quality Control</a></li>
            </ul>
        </li>

        <!-- GPS Business System -->
        <div class="system-indicator gps-system">GPS Business System</div>

        <li class="nav-item">
            <a href="#gpsOrdersSubmenu" class="nav-link" data-toggle="collapse" data-target="#gpsOrdersSubmenu">
                <i class="fa fa-shopping-cart"></i>
                <span class="nav-label">Orders & Payments</span>
                <i class="fa fa-angle-down submenu-arrow"></i>
            </a>
            <ul class="sidebar-submenu" id="gpsOrdersSubmenu">
                <li><a href="gpsBusiness/gps_orders.php"><i class="fa fa-list"></i> GPS Orders</a></li>
                <li><a href="gpsBusiness/gps_payments.php"><i class="fa fa-money"></i> GPS Payments</a></li>
                <li><a href="gpsBusiness/gps_payment_followup.php"><i class="fa fa-tasks"></i> Payment Follow-up</a></li>
            </ul>
        </li>

        <li class="nav-item">
            <a href="#gpsBusinessSubmenu" class="nav-link" data-toggle="collapse" data-target="#gpsBusinessSubmenu">
                <i class="fa fa-briefcase"></i>
                <span class="nav-label">GPS Business</span>
                <i class="fa fa-angle-down submenu-arrow"></i>
            </a>
            <ul class="sidebar-submenu" id="gpsBusinessSubmenu">
                <li><a href="gpsBusiness/gps_business_cycles.php"><i class="fa fa-refresh"></i> Business Cycles</a></li>
                <li><a href="gpsBusiness/gps_profit_distribution.php"><i class="fa fa-money"></i> Profit Distribution</a></li>
                <li><a href="gpsBusiness/gps_business_reports.php"><i class="fa fa-bar-chart"></i> Business Reports</a></li>
            </ul>
        </li>

        <!-- Warranty Certificate -->
        <?php if(hasPermission('warranty_certificate')): ?>
        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' && dirname($_SERVER['PHP_SELF']) == '/warranty') ? 'active' : ''; ?>">
            <a href="<?php echo $base_url; ?>warranty/index.php">
                <i class="fa fa-certificate"></i> <span>Warranty Certificates</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- Settings -->
        <?php if(isset($_SESSION['userId'])): ?>
        <li class="nav-item">
            <a href="#settingsSubmenu" class="nav-link" data-toggle="collapse" data-target="#settingsSubmenu">
                <i class="glyphicon glyphicon-cog"></i>
                <span class="nav-label">Settings</span>
                <i class="fa fa-angle-down submenu-arrow"></i>
            </a>
            <ul class="sidebar-submenu" id="settingsSubmenu">
                <li><a href="profile.php"><i class="glyphicon glyphicon-user"></i> Your Profile</a></li>
                <li><a href="setting.php"><i class="glyphicon glyphicon-wrench"></i> General Settings</a></li>
                <?php if(hasPermission('guest.view') || hasPermission('manage_guests')): ?>
                <li><a href="user.php"><i class="glyphicon glyphicon-user"></i> User Management</a></li>
                <li><a href="role_management.php"><i class="glyphicon glyphicon-lock"></i> Role Management</a></li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>
    </ul>

    <!-- Quick Access Menu -->
    <div class="quick-access">
        <button class="quick-access-btn" data-action="pos">
            <i class="fa fa-shopping-cart"></i> Open POS
        </button>
        <button class="quick-access-btn" data-action="new-order">
            <i class="fa fa-plus"></i> New Order
        </button>
    </div>
</div>

<!-- Sidebar Toggle Button -->
<button id="sidebarToggle">
    <i class="fa fa-bars"></i>
</button>

<!-- Main Content Wrapper -->
<div class="main-content">
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