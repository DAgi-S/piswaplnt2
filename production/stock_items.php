<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';

// Get item details from URL
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';

// Validate parameters
if (empty($itemId) || empty($type) || $type !== 'finished_good') {
    echo "<script>window.location.href = 'warehouses.php';</script>";
    exit();
}

// Fetch item details based on type
$sql = "SELECT 
            p.*, 
            p.product_code as code,
            p.unit as unit_name,
            pc.name as category_name,
            pb.name as brand_name
        FROM production_products p
        LEFT JOIN production_categories pc ON p.category_id = pc.id
        LEFT JOIN production_brands pb ON p.brand_id = pb.id
        WHERE p.id = ?";

$stmt = $connect->prepare($sql);
if (!$stmt) {
    echo "<script>window.location.href = 'warehouses.php';</script>";
    exit();
}

$stmt->bind_param("i", $itemId);
$stmt->execute();
$result = $stmt->get_result();
$itemDetails = $result->fetch_assoc();

// Only redirect if no item found
if (!$itemDetails) {
    echo "<script>window.location.href = 'warehouses.php';</script>";
    exit();
}

// Ensure all required fields have default values
$itemDetails['code'] = $itemDetails['code'] ?? '';
$itemDetails['name'] = $itemDetails['name'] ?? '';
$itemDetails['category_name'] = $itemDetails['category_name'] ?? 'Uncategorized';
$itemDetails['unit_name'] = $itemDetails['unit_name'] ?? '-';
$itemDetails['status'] = $itemDetails['status'] ?? 'inactive';

// Get stock movements
$movementsSql = "SELECT 
    wsm.*,
    w.name as warehouse_name,
    CASE 
        WHEN wsm.reference_type = 'purchase' THEN p.purchase_date
        WHEN wsm.reference_type = 'production' THEN po.created_at
        ELSE wsm.created_at
    END as transaction_date,
    CASE 
        WHEN wsm.reference_type = 'purchase' THEN COALESCE(sup.company_name, 'Unknown Supplier')
        WHEN wsm.reference_type = 'production' THEN 'Production'
        ELSE 'Internal Transfer'
    END as source,
    NULL as unit_cost
FROM warehouse_stock_movements wsm
LEFT JOIN warehouses w ON wsm.warehouse_id = w.id
LEFT JOIN purchases p ON wsm.reference_type = 'purchase' AND wsm.reference_id = p.id
LEFT JOIN suppliers sup ON p.supplier_id = sup.id
LEFT JOIN production_orders po ON wsm.reference_type = 'production' AND wsm.reference_id = po.id
WHERE wsm.item_id = ? AND wsm.item_type = ?
ORDER BY wsm.created_at DESC";

$stmt = $connect->prepare($movementsSql);
$stmt->bind_param("is", $itemId, $type);
$stmt->execute();
$movements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$totalPurchased = 0;
$totalProduced = 0;
$totalConsumed = 0;
$averageCost = 0;
$costCount = 0;
$totalCost = 0;

foreach ($movements as $movement) {
    if ($movement['movement_type'] === 'in') {
        if ($movement['reference_type'] === 'purchase') {
            $totalPurchased += $movement['quantity'];
        } elseif ($movement['reference_type'] === 'production') {
            $totalProduced += $movement['quantity'];
        }
        if ($movement['unit_cost']) {
            $totalCost += $movement['unit_cost'] * $movement['quantity'];
            $costCount += $movement['quantity'];
        }
    } else {
        $totalConsumed += $movement['quantity'];
    }
}
$averageCost = $costCount > 0 ? $totalCost / $costCount : 0;
?>

<!-- Custom CSS for visualizations -->
<style>
    .stat-card {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stat-card h3 {
        margin-top: 0;
        color: #333;
    }
    .stat-value {
        font-size: 24px;
        font-weight: bold;
        color: #007bff;
    }
    .flow-diagram {
        width: 100%;
        height: 300px;
        margin: 20px 0;
    }
    .timeline {
        position: relative;
        margin: 20px 0;
    }
    .timeline-item {
        padding: 10px;
        border-left: 2px solid #007bff;
        margin-bottom: 10px;
    }
    .movement-in { border-left-color: #28a745; }
    .movement-out { border-left-color: #dc3545; }
</style>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="warehouses.php">Warehouses</a></li>
            <li class="active">Stock Item Details</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-info-circle"></i> Item Details - <?php echo htmlspecialchars($itemDetails['name']); ?>
                    <a href="warehouses.php" class="btn btn-default pull-right">
                        <i class="fa fa-arrow-left"></i> Back to Warehouses
                    </a>
                </div>
            </div>

            <div class="panel-body">
                <!-- Item Information -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="stat-card">
                            <h3>Basic Information</h3>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Code</th>
                                    <td><?php echo htmlspecialchars($itemDetails['code']); ?></td>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <td><?php echo htmlspecialchars($itemDetails['name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Category</th>
                                    <td><?php echo htmlspecialchars($itemDetails['category_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Unit</th>
                                    <td><?php echo htmlspecialchars($itemDetails['unit_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td><span class="label label-<?php echo $itemDetails['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($itemDetails['status']); ?>
                                    </span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-card">
                            <h3>Stock Statistics</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="stat-box">
                                        <h4>Total Purchased</h4>
                                        <div class="stat-value"><?php echo number_format($totalPurchased, 2); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-box">
                                        <h4>Total Produced</h4>
                                        <div class="stat-value"><?php echo number_format($totalProduced, 2); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-box">
                                        <h4>Total Consumed</h4>
                                        <div class="stat-value"><?php echo number_format($totalConsumed, 2); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-box">
                                        <h4>Average Cost</h4>
                                        <div class="stat-value"><?php echo number_format($averageCost, 2); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SVG Flow Diagram -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="stat-card">
                            <h3>Stock Flow Visualization</h3>
                            <svg class="flow-diagram" id="flowDiagram">
                                <!-- SVG content will be added via JavaScript -->
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Movement History -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="stat-card">
                            <h3>Movement History</h3>
                            <table class="table table-striped" id="movementTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Warehouse</th>
                                        <th>Source</th>
                                        <th>Quantity</th>
                                        <th>Unit Cost</th>
                                        <th>Total Cost</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movements as $movement): ?>
                                    <tr class="movement-<?php echo $movement['movement_type']; ?>">
                                        <td><?php echo date('Y-m-d H:i', strtotime($movement['transaction_date'])); ?></td>
                                        <td>
                                            <span class="label label-<?php echo $movement['movement_type'] === 'in' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($movement['movement_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($movement['warehouse_name']); ?></td>
                                        <td><?php echo htmlspecialchars($movement['source']); ?></td>
                                        <td><?php echo number_format($movement['quantity'], 2); ?></td>
                                        <td><?php echo $movement['unit_cost'] ? number_format($movement['unit_cost'], 2) : '-'; ?></td>
                                        <td><?php echo $movement['unit_cost'] ? number_format($movement['unit_cost'] * $movement['quantity'], 2) : '-'; ?></td>
                                        <td><?php echo htmlspecialchars($movement['reference_type'] . ' #' . $movement['reference_id']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- D3.js for SVG visualization -->
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
// Flow diagram visualization
document.addEventListener('DOMContentLoaded', function() {
    const data = {
        purchased: <?php echo $totalPurchased; ?>,
        produced: <?php echo $totalProduced; ?>,
        consumed: <?php echo $totalConsumed; ?>
    };

    const svg = d3.select('#flowDiagram');
    const width = svg.node().getBoundingClientRect().width;
    const height = 300;
    const margin = { top: 20, right: 20, bottom: 20, left: 20 };

    // Clear existing content
    svg.selectAll('*').remove();

    // Create the flow diagram
    const g = svg.append('g')
        .attr('transform', `translate(${margin.left},${margin.top})`);

    // Add nodes
    const nodes = [
        { id: 'purchase', label: 'Purchased', value: data.purchased, x: width * 0.2, y: height * 0.3 },
        { id: 'production', label: 'Produced', value: data.produced, x: width * 0.2, y: height * 0.7 },
        { id: 'inventory', label: 'Current Stock', value: data.purchased + data.produced - data.consumed, x: width * 0.5, y: height * 0.5 },
        { id: 'consumed', label: 'Consumed', value: data.consumed, x: width * 0.8, y: height * 0.5 }
    ];

    // Add links
    const links = [
        { source: nodes[0], target: nodes[2], value: data.purchased },
        { source: nodes[1], target: nodes[2], value: data.produced },
        { source: nodes[2], target: nodes[3], value: data.consumed }
    ];

    // Draw links
    g.selectAll('.link')
        .data(links)
        .enter()
        .append('path')
        .attr('class', 'link')
        .attr('d', d => {
            const dx = d.target.x - d.source.x;
            const dy = d.target.y - d.source.y;
            return `M${d.source.x},${d.source.y}C${d.source.x + dx/2},${d.source.y} ${d.target.x - dx/2},${d.target.y} ${d.target.x},${d.target.y}`;
        })
        .style('fill', 'none')
        .style('stroke', '#aaa')
        .style('stroke-width', d => Math.sqrt(d.value));

    // Draw nodes
    const nodeGroups = g.selectAll('.node')
        .data(nodes)
        .enter()
        .append('g')
        .attr('class', 'node')
        .attr('transform', d => `translate(${d.x},${d.y})`);

    nodeGroups.append('circle')
        .attr('r', 30)
        .style('fill', '#fff')
        .style('stroke', '#007bff')
        .style('stroke-width', 2);

    nodeGroups.append('text')
        .attr('dy', '.35em')
        .attr('text-anchor', 'middle')
        .text(d => d.label)
        .style('font-size', '12px');

    nodeGroups.append('text')
        .attr('dy', '1.5em')
        .attr('text-anchor', 'middle')
        .text(d => d.value.toFixed(2))
        .style('font-size', '10px')
        .style('fill', '#666');
});

// Initialize DataTable for movement history
$(document).ready(function() {
    $('#movementTable').DataTable({
        'order': [[0, 'desc']],
        'pageLength': 10,
        'responsive': true
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 