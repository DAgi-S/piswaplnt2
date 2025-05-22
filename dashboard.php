<?php 
require_once 'php_action/db_connect.php';
require_once 'php_action/core.php';
require_once 'php_action/functions.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit();
}

// Check base dashboard access (support both new and legacy permissions)
if (!hasPermission('dashboard.view') && !hasPermission('view_dashboard')) {
    $_SESSION['error'] = "You don't have permission to view the dashboard";
    header('Location: access_denied.php');
    exit();
}

// Initialize permission flags for different dashboard sections
$permissions = array(
    // Core Dashboard Permissions
    'core' => array(
        'view' => hasPermission('dashboard.view') || hasPermission('view_dashboard'),
        'customize' => hasPermission('dashboard.customize'),
        'export' => hasPermission('dashboard.analytics.export') || hasPermission('dashboard.export')
    ),
    
    // Analytics Permissions
    'analytics' => array(
        'view' => hasPermission('dashboard.analytics.view') || hasPermission('view_analytics'),
        'export' => hasPermission('dashboard.analytics.export'),
        'sales' => hasPermission('dashboard.analytics.sales'),
        'inventory' => hasPermission('dashboard.analytics.inventory'),
        'revenue' => hasPermission('dashboard.analytics.revenue') || hasPermission('view_revenue')
    ),
    
    // Inventory Permissions
    'inventory' => array(
        'view' => hasPermission('dashboard.inventory.view') || hasPermission('inventory.view'),
        'stock_level' => hasPermission('dashboard.inventory.stock_level'),
        'movements' => hasPermission('dashboard.inventory.movements'),
        'alerts' => hasPermission('dashboard.inventory.alerts'),
        'low_stock' => hasPermission('dashboard.inventory.alerts') || hasPermission('view_low_stock')
    ),
    
    // Financial Permissions
    'financial' => array(
        'view' => hasPermission('dashboard.financial.view'),
        'overview' => hasPermission('dashboard.financial.overview'),
        'profit_loss' => hasPermission('dashboard.financial.profit_loss'),
        'expenses' => hasPermission('dashboard.financial.expenses'),
        'revenue' => hasPermission('dashboard.financial.revenue') || hasPermission('view_revenue')
    ),
    
    // Reports Permissions
    'reports' => array(
        'view' => hasPermission('dashboard.reports.view'),
        'generate' => hasPermission('dashboard.reports.generate'),
        'schedule' => hasPermission('dashboard.reports.schedule'),
        'export' => hasPermission('dashboard.reports.export')
    ),

    // Purchase Permissions
    'purchase' => array(
        'view' => hasPermission('purchase.view') || hasPermission('purchases.view'),
        'overview' => hasPermission('purchase.overview') || hasPermission('purchases.overview'),
        'analytics' => hasPermission('purchase.analytics.view')
    ),

    // Legacy Support
    'legacy' => array(
        'view_dashboard' => hasPermission('view_dashboard'),
        'view_analytics' => hasPermission('view_analytics'),
        'view_low_stock' => hasPermission('view_low_stock'),
        'view_revenue' => hasPermission('view_revenue'),
        'view_recent_orders' => hasPermission('view_recent_orders'),
        'view_recent_letters' => hasPermission('view_recent_letters')
    )
);

// Helper function to check combined permissions
function check_dashboard_permission($new_perm, $legacy_perm = null) {
    if ($legacy_perm) {
        return hasPermission($new_perm) || hasPermission($legacy_perm);
    }
    return hasPermission($new_perm);
}

try {
    $countProduct = 0;
    $countLowStock = 0;
    $countOrder = 0;
    $totalRevenue = 0;
    $totalPurchases = 0;
    $totalPurchaseAmount = 0;
    
    // Only query what user has permission to see
    if ($permissions['analytics']['inventory'] || $permissions['inventory']['view'] || hasPermission('product.view') || hasPermission('view_product')) {
        // Get product count
        $sql = "SELECT COUNT(*) as count FROM products WHERE status = 1";
        $result = $connect->query($sql);
        $countProduct = $result ? $result->fetch_assoc()['count'] : 0;
    }

    // Low stock data
    if ($permissions['inventory']['low_stock'] || hasPermission('product.inventory.view')) {
        $lowStockSql = "SELECT COUNT(*) as count FROM products WHERE current_stock <= 3 AND status = 'active'";
        $result = $connect->query($lowStockSql);
        $countLowStock = $result ? $result->fetch_assoc()['count'] : 0;
    }

    // Revenue data
    if ($permissions['financial']['revenue'] || $permissions['analytics']['revenue']) {
        $orderSql = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders WHERE order_status = 1";
        $result = $connect->query($orderSql);
        $orderStats = $result ? $result->fetch_assoc() : ['count' => 0, 'total' => 0];
        $countOrder = $orderStats['count'];
        $totalRevenue = $orderStats['total'] ?? 0;
    }

    // Purchase data
    if ($permissions['purchase']['view'] || $permissions['financial']['overview']) {
        $purchaseSql = "SELECT COUNT(*) as count, SUM(grand_total) as total FROM purchases WHERE active = 1";
        $result = $connect->query($purchaseSql);
        $purchaseStats = $result ? $result->fetch_assoc() : ['count' => 0, 'total' => 0];
        $totalPurchases = $purchaseStats['count'];
        $totalPurchaseAmount = $purchaseStats['total'] ?? 0;

        // Get recent purchases
        $recentPurchasesSql = "SELECT p.*, s.company_name 
                              FROM purchases p 
                              LEFT JOIN suppliers s ON p.supplier_id = s.id 
                              WHERE p.active = 1 
                              ORDER BY p.purchase_date DESC LIMIT 5";
        $recentPurchases = $connect->query($recentPurchasesSql);

        // Get top suppliers
        $topSuppliersSql = "SELECT s.company_name, COUNT(p.id) as purchase_count, 
                                   SUM(p.grand_total) as total_amount 
                            FROM suppliers s 
                            LEFT JOIN purchases p ON s.id = p.supplier_id 
                            WHERE s.active = 1 AND p.active = 1 
                            GROUP BY s.id 
                            ORDER BY total_amount DESC 
                            LIMIT 5";
        $topSuppliers = $connect->query($topSuppliersSql);
    }

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
}

// Include header after all checks
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <ol class="breadcrumb">
                <li><a href="dashboard.php">Home</a></li>
                <li class="active">Dashboard</li>
            </ol>

            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="page-heading"> <i class="glyphicon glyphicon-dashboard"></i> Dashboard</div>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <!-- Products Statistics -->
                        <?php if($permissions['analytics']['inventory'] || $permissions['inventory']['view'] || hasPermission('product.view') || hasPermission('view_product')): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="stat-card products-card">
                                <div class="icon"><i class="glyphicon glyphicon-shopping-cart"></i></div>
                                <div class="stat-value"><?php echo number_format($countProduct); ?></div>
                                <div class="stat-label">Total Products</div>
                                <?php if($permissions['inventory']['low_stock'] || hasPermission('product.inventory.view')): ?>
                                <div class="balance-info">
                                    Low Stock: <span class="<?php echo $countLowStock > 0 ? 'profit-negative' : ''; ?>">
                                        <?php echo number_format($countLowStock); ?> items
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Orders Statistics -->
                        <?php if($permissions['financial']['revenue'] || $permissions['analytics']['revenue']): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="stat-card orders-card">
                                <div class="icon"><i class="glyphicon glyphicon-list-alt"></i></div>
                                <div class="stat-value"><?php echo number_format($countOrder); ?></div>
                                <div class="stat-label">Total Orders</div>
                                <div class="balance-info">
                                    Revenue: <?php echo number_format($totalRevenue, 2); ?> ETB
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Purchase Statistics -->
                        <?php if($permissions['purchase']['view'] || $permissions['financial']['overview']): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="stat-card purchases-card">
                                <div class="icon"><i class="glyphicon glyphicon-shopping-cart"></i></div>
                                <div class="stat-value"><?php echo number_format($totalPurchases); ?></div>
                                <div class="stat-label">Total Purchases</div>
                                <div class="balance-info">
                                    Cost: <?php echo number_format($totalPurchaseAmount, 2); ?> ETB
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if($permissions['purchase']['view'] || $permissions['financial']['overview']): ?>
                    <div class="row">
                        <!-- Recent Purchases -->
                        <div class="col-md-6">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <i class="glyphicon glyphicon-time"></i> Recent Purchases
                                    <?php if($permissions['core']['export']): ?>
                                    <button class="btn btn-xs btn-info pull-right export-data">
                                        <i class="glyphicon glyphicon-download"></i> Export
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Supplier</th>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(isset($recentPurchases) && $recentPurchases && $recentPurchases->num_rows > 0): ?>
                                                    <?php while($purchase = $recentPurchases->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($purchase['company_name']); ?></td>
                                                            <td><?php echo date('d M Y', strtotime($purchase['purchase_date'])); ?></td>
                                                            <td><?php echo number_format($purchase['grand_total'], 2); ?> ETB</td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center">No recent purchases</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Suppliers -->
                        <div class="col-md-6">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <i class="glyphicon glyphicon-star"></i> Top Suppliers
                                    <?php if($permissions['core']['export']): ?>
                                    <button class="btn btn-xs btn-info pull-right export-data">
                                        <i class="glyphicon glyphicon-download"></i> Export
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="panel-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Supplier</th>
                                                    <th>Purchases</th>
                                                    <th>Total Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if(isset($topSuppliers) && $topSuppliers && $topSuppliers->num_rows > 0): ?>
                                                    <?php while($supplier = $topSuppliers->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($supplier['company_name']); ?></td>
                                                            <td><?php echo number_format($supplier['purchase_count']); ?></td>
                                                            <td><?php echo number_format($supplier['total_amount'], 2); ?> ETB</td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center">No supplier data available</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if($permissions['core']['customize']): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <button class="btn btn-primary customize-dashboard">
                                <i class="glyphicon glyphicon-cog"></i> Customize Dashboard
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Add JavaScript for export functionality if user has permission
if ($permissions['core']['export']): 
?>
<script>
$(document).ready(function() {
    $('.export-data').click(function() {
        var $table = $(this).closest('.panel').find('table');
        var data = [];
        
        // Get headers
        var headers = [];
        $table.find('thead th').each(function() {
            headers.push($(this).text().trim());
        });
        data.push(headers);
        
        // Get data
        $table.find('tbody tr').each(function() {
            var row = [];
            $(this).find('td').each(function() {
                row.push($(this).text().trim());
            });
            data.push(row);
        });
        
        // Convert to CSV
        var csv = data.map(row => row.join(',')).join('\n');
        
        // Download
        var blob = new Blob([csv], { type: 'text/csv' });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'dashboard-export-' + new Date().toISOString().slice(0,10) + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    });
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>