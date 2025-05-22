<?php 
require_once 'includes/header.php';

// Initialize filter variables
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : 'all';

// Base query for quality metrics
$query = "
    SELECT 
        po.id,
        po.order_number,
        p.name as product_name,
        po.target_quantity,
        po.completed_quantity,
        po.start_date,
        po.actual_completion_date,
        COALESCE(pp.total_quantity, 0) as total_produced,
        COALESCE(pp.rejected_quantity, 0) as total_rejected,
        CASE 
            WHEN COALESCE(pp.total_quantity, 0) > 0 
            THEN (COALESCE(pp.rejected_quantity, 0) / pp.total_quantity) * 100 
            ELSE 0 
        END as rejection_rate,
        po.status,
        u.username as created_by_name
    FROM production_orders po
    LEFT JOIN products p ON po.product_id = p.product_id
    LEFT JOIN (
        SELECT 
            production_order_id,
            SUM(quantity) as total_quantity,
            SUM(CASE WHEN notes LIKE '%reject%' OR notes LIKE '%defect%' THEN quantity ELSE 0 END) as rejected_quantity
        FROM production_progress
        GROUP BY production_order_id
    ) pp ON po.id = pp.production_order_id
    LEFT JOIN users u ON po.created_by = u.user_id
    WHERE po.start_date BETWEEN ? AND ?";

if ($product_id !== 'all') {
    $query .= " AND po.product_id = ?";
}

$query .= " ORDER BY po.start_date DESC";

// Prepare statement with dynamic parameters
$types = "ss"; // start with two dates
$params = [$start_date, $end_date];

if ($product_id !== 'all') {
    $types .= "i";
    $params[] = $product_id;
}

$stmt = $connect->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Calculate summary statistics
$total_orders = 0;
$total_produced = 0;
$total_rejected = 0;
$avg_rejection_rate = 0;
$orders_with_rejections = 0;

while ($row = $result->fetch_assoc()) {
    $total_orders++;
    $total_produced += $row['total_produced'];
    $total_rejected += $row['total_rejected'];
    if ($row['total_rejected'] > 0) {
        $orders_with_rejections++;
    }
}

$avg_rejection_rate = $total_produced > 0 ? ($total_rejected / $total_produced) * 100 : 0;

// Reset result pointer
$result->data_seek(0);

// Get products for dropdown
$products_query = "SELECT product_id, name FROM products WHERE status = 'active' ORDER BY name";
$products = $connect->query($products_query);
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Quality Report</li>
        </ol>

        <!-- Filters -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-filter"></i> Filter Report</h3>
            </div>
            <div class="panel-body">
                <form method="GET" class="form-horizontal">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Start Date</label>
                                <div class="col-sm-8">
                                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">End Date</label>
                                <div class="col-sm-8">
                                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Product</label>
                                <div class="col-sm-8">
                                    <select name="product_id" class="form-control">
                                        <option value="all">All Products</option>
                                        <?php while($product = $products->fetch_assoc()): ?>
                                            <option value="<?php echo $product['product_id']; ?>" <?php echo $product_id == $product['product_id'] ? 'selected' : ''; ?>>
                                                <?php echo $product['name']; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="quality_report.php" class="btn btn-default">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row">
            <div class="col-md-3">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-industry fa-4x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo number_format($total_produced); ?></div>
                                <div>Total Produced</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-danger">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-exclamation-triangle fa-4x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo number_format($total_rejected); ?></div>
                                <div>Total Rejected</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-percent fa-4x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo number_format($avg_rejection_rate, 2); ?>%</div>
                                <div>Avg. Rejection Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-tasks fa-4x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo $orders_with_rejections; ?>/<?php echo $total_orders; ?></div>
                                <div>Orders with Rejections</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quality Data Table -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="panel-title"><i class="fa fa-check-square"></i> Production Quality Details</h3>
                    </div>
                    <div class="col-md-6 text-right">
                        <button onclick="exportToPDF()" class="btn btn-danger btn-sm"><i class="fa fa-file-pdf-o"></i> Export PDF</button>
                        <button onclick="exportToExcel()" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Export Excel</button>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Product</th>
                                <th>Start Date</th>
                                <th>Completion Date</th>
                                <th>Total Produced</th>
                                <th>Rejected</th>
                                <th>Rejection Rate</th>
                                <th>Status</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['order_number']; ?></td>
                                    <td><?php echo $row['product_name']; ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($row['start_date'])); ?></td>
                                    <td><?php echo $row['actual_completion_date'] ? date('d/m/Y', strtotime($row['actual_completion_date'])) : '-'; ?></td>
                                    <td><?php echo number_format($row['total_produced']); ?></td>
                                    <td><?php echo number_format($row['total_rejected']); ?></td>
                                    <td>
                                        <?php 
                                        $rejection_rate = $row['rejection_rate'];
                                        $rate_class = $rejection_rate > 5 ? 'danger' : ($rejection_rate > 2 ? 'warning' : 'success');
                                        ?>
                                        <span class="label label-<?php echo $rate_class; ?>">
                                            <?php echo number_format($rejection_rate, 2); ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch($row['status']) {
                                            case 'completed':
                                                $status_class = 'success';
                                                break;
                                            case 'in_progress':
                                                $status_class = 'primary';
                                                break;
                                            case 'pending':
                                                $status_class = 'warning';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'danger';
                                                break;
                                        }
                                        ?>
                                        <span class="label label-<?php echo $status_class; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['created_by_name']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/quality_report.js"></script>

<?php 
$stmt->close();
require_once 'includes/footer.php'; 
?>