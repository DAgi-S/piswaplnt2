<?php 
require_once 'includes/header.php';

// Initialize filter variables
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : 'all';
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : 'all';

// Base query for cost analysis
$query = "
    SELECT 
        po.id,
        po.order_number,
        p.name as product_name,
        po.target_quantity,
        po.completed_quantity,
        po.start_date,
        po.actual_completion_date,
        COALESCE(pom.total_material_cost, 0) as material_cost,
        COALESCE(pom.total_consumed_cost, 0) as consumed_cost,
        COALESCE(pom.total_wastage_cost, 0) as wastage_cost,
        COALESCE(pp.total_produced, 0) as units_produced,
        CASE 
            WHEN COALESCE(pp.total_produced, 0) > 0 
            THEN COALESCE(pom.total_consumed_cost, 0) / pp.total_produced
            ELSE 0 
        END as cost_per_unit,
        CASE 
            WHEN COALESCE(pom.total_material_cost, 0) > 0 
            THEN (COALESCE(pom.total_wastage_cost, 0) / COALESCE(pom.total_material_cost, 0)) * 100
            ELSE 0 
        END as wastage_percentage,
        po.status,
        pc.name as category_name,
        u.username as created_by_name
    FROM production_orders po
    LEFT JOIN products p ON po.product_id = p.product_id
    LEFT JOIN production_categories pc ON p.category_id = pc.id
    LEFT JOIN (
        SELECT 
            pom.production_order_id,
            SUM(pom.required_quantity * rm.cost_per_unit) as total_material_cost,
            SUM(pom.consumed_quantity * rm.cost_per_unit) as total_consumed_cost,
            SUM((pom.consumed_quantity - pom.required_quantity) * rm.cost_per_unit) as total_wastage_cost
        FROM production_order_materials pom
        JOIN raw_materials rm ON pom.material_id = rm.id
        GROUP BY pom.production_order_id
    ) pom ON po.id = pom.production_order_id
    LEFT JOIN (
        SELECT 
            production_order_id,
            SUM(quantity) as total_produced
        FROM production_progress
        WHERE notes NOT LIKE '%reject%' AND notes NOT LIKE '%defect%'
        GROUP BY production_order_id
    ) pp ON po.id = pp.production_order_id
    LEFT JOIN users u ON po.created_by = u.user_id
    WHERE po.start_date BETWEEN ? AND ?";

if ($product_id !== 'all') {
    $query .= " AND po.product_id = ?";
}
if ($category_id !== 'all') {
    $query .= " AND p.category_id = ?";
}

$query .= " ORDER BY po.start_date DESC";

// Prepare statement with dynamic parameters
$types = "ss"; // start with two dates
$params = [$start_date, $end_date];

if ($product_id !== 'all') {
    $types .= "i";
    $params[] = $product_id;
}
if ($category_id !== 'all') {
    $types .= "i";
    $params[] = $category_id;
}

$stmt = $connect->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Calculate summary statistics
$total_orders = 0;
$total_material_cost = 0;
$total_wastage_cost = 0;
$total_units_produced = 0;
$total_orders_with_wastage = 0;

while ($row = $result->fetch_assoc()) {
    $total_orders++;
    $total_material_cost += $row['material_cost'];
    $total_wastage_cost += $row['wastage_cost'];
    $total_units_produced += $row['units_produced'];
    if ($row['wastage_cost'] > 0) {
        $total_orders_with_wastage++;
    }
}

$avg_cost_per_unit = $total_units_produced > 0 ? $total_material_cost / $total_units_produced : 0;
$avg_wastage_percentage = $total_material_cost > 0 ? ($total_wastage_cost / $total_material_cost) * 100 : 0;

// Reset result pointer
$result->data_seek(0);

// Get products for dropdown
$products_query = "SELECT product_id, name FROM products WHERE status = 'active' ORDER BY name";
$products = $connect->query($products_query);

// Get categories for dropdown
$categories_query = "SELECT id, name FROM production_categories WHERE status = 'active' ORDER BY name";
$categories = $connect->query($categories_query);
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Cost Analysis</li>
        </ol>

        <!-- Filters -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><i class="fa fa-filter"></i> Filter Analysis</h3>
            </div>
            <div class="panel-body">
                <form method="GET" class="form-horizontal">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Start Date</label>
                                <div class="col-sm-8">
                                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">End Date</label>
                                <div class="col-sm-8">
                                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
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
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="col-sm-4 control-label">Category</label>
                                <div class="col-sm-8">
                                    <select name="category_id" class="form-control">
                                        <option value="all">All Categories</option>
                                        <?php while($category = $categories->fetch_assoc()): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo $category['name']; ?>
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
                            <a href="cost_analysis.php" class="btn btn-default">Reset</a>
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
                                <i class="fa fa-money fa-4x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo number_format($total_material_cost, 2); ?></div>
                                <div>Total Material Cost</div>
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
                                <i class="fa fa-warning fa-4x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo number_format($total_wastage_cost, 2); ?></div>
                                <div>Total Wastage Cost</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-calculator fa-4x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge"><?php echo number_format($avg_cost_per_unit, 2); ?></div>
                                <div>Avg. Cost per Unit</div>
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
                                <div class="huge"><?php echo number_format($avg_wastage_percentage, 2); ?>%</div>
                                <div>Avg. Wastage Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cost Analysis Table -->
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-md-6">
                        <h3 class="panel-title"><i class="fa fa-table"></i> Production Cost Details</h3>
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
                                <th>Category</th>
                                <th>Material Cost</th>
                                <th>Wastage Cost</th>
                                <th>Cost per Unit</th>
                                <th>Wastage %</th>
                                <th>Units Produced</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['order_number']; ?></td>
                                    <td><?php echo $row['product_name']; ?></td>
                                    <td><?php echo $row['category_name']; ?></td>
                                    <td><?php echo number_format($row['material_cost'], 2); ?></td>
                                    <td><?php echo number_format($row['wastage_cost'], 2); ?></td>
                                    <td><?php echo number_format($row['cost_per_unit'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $wastage_percentage = $row['wastage_percentage'];
                                        $wastage_class = $wastage_percentage > 10 ? 'danger' : ($wastage_percentage > 5 ? 'warning' : 'success');
                                        ?>
                                        <span class="label label-<?php echo $wastage_class; ?>">
                                            <?php echo number_format($wastage_percentage, 2); ?>%
                                        </span>
                                    </td>
                                    <td><?php echo number_format($row['units_produced']); ?></td>
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
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/cost_analysis.js"></script>

<?php 
$stmt->close();
require_once 'includes/footer.php'; 
?> 