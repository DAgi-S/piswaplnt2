<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';

// Get item details from URL
$itemId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch finished goods details
$sql = "SELECT 
            p.*, 
            p.product_code as code,
            p.unit,
            p.current_stock,
            p.min_stock_level,
            p.production_cost,
            p.selling_price,
            p.description,
            p.status,
            COALESCE(u.name, p.unit) as unit_name
        FROM products p
        LEFT JOIN units u ON p.unit = u.id
        WHERE p.product_id = ?";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $itemId);
$stmt->execute();
$result = $stmt->get_result();
$itemDetails = $result->fetch_assoc();

if (!$itemDetails) {
    echo "<script>window.location.href = 'warehouses.php';</script>";
    exit();
}

// Ensure all required fields have default values
$itemDetails['code'] = $itemDetails['product_code'] ?? '';
$itemDetails['name'] = $itemDetails['name'] ?? '';
$itemDetails['category_id'] = $itemDetails['category_id'] ?? '0';
$itemDetails['unit_name'] = $itemDetails['unit_name'] ?? '-';
$itemDetails['status'] = $itemDetails['status'] ?? 'inactive';
$itemDetails['min_stock_level'] = $itemDetails['min_stock_level'] ?? 0;
$itemDetails['production_cost'] = $itemDetails['production_cost'] ?? 0;
$itemDetails['selling_price'] = $itemDetails['selling_price'] ?? 0;

// Get current stock and movement totals in a single query
$stockSql = "SELECT 
    p.production_cost as cost_per_unit,
    COALESCE(ws.quantity, 0) as warehouse_stock,
    COALESCE(
        (SELECT SUM(
            CASE 
                WHEN movement_type = 'in' THEN quantity 
                WHEN movement_type = 'out' THEN -quantity 
            END
        )
        FROM stock_movements 
        WHERE product_id = p.product_id
        AND status = 'active'
        ), 0
    ) as movement_based_stock,
    COALESCE(
        (SELECT SUM(quantity)
        FROM stock_movements 
        WHERE product_id = p.product_id 
        AND movement_type = 'in'
        AND status = 'active'
        ), 0
    ) as total_produced,
    COALESCE(
        (SELECT SUM(quantity)
        FROM stock_movements 
        WHERE product_id = p.product_id 
        AND movement_type = 'out'
        AND status = 'active'
        ), 0
    ) as total_sold
FROM products p
LEFT JOIN warehouse_stock ws ON ws.item_id = p.product_id 
    AND ws.item_type = 'finished_good'
WHERE p.product_id = ?";

$stmt = $connect->prepare($stockSql);
$stmt->bind_param("i", $itemId);
$stmt->execute();
$stockResult = $stmt->get_result()->fetch_assoc();

// Set the statistics
$warehouseStock = $stockResult['warehouse_stock'] ?? 0;
$movementBasedStock = $stockResult['movement_based_stock'] ?? 0;
$currentStock = $movementBasedStock; // Use movement-based calculation as primary
$totalProduced = $stockResult['total_produced'] ?? 0;
$totalSold = $stockResult['total_sold'] ?? 0;
$averageCost = $stockResult['cost_per_unit'] ?? 0;

// Get stock movements for history
$movementsSql = "SELECT 
    DATE_FORMAT(sm.created_at, '%Y-%m-%d %H:%i:%s') as movement_date,
    sm.movement_type,
    CONCAT(FORMAT(sm.quantity, 2), ' ', CONVERT(p.unit USING utf8mb4) COLLATE utf8mb4_general_ci) as quantity_display,
    'Main Warehouse' as warehouse_name,
    sm.reference_type,
    CONCAT(
        CASE 
            WHEN sm.reference_type = 'production_order' THEN 'Production Order'
            WHEN sm.reference_type = 'sale' THEN 'Sales Order'
            WHEN sm.reference_type = 'adjustment' THEN 'Stock Adjustment'
            ELSE sm.reference_type
        END,
        ' #',
        sm.reference_id
    ) as reference_info,
    CONVERT(sm.notes USING utf8mb4) COLLATE utf8mb4_general_ci as details,
    COALESCE(CONVERT(u.username USING utf8mb4) COLLATE utf8mb4_general_ci, 'system') as created_by,
    sm.quantity as raw_quantity,
    sm.status as movement_status
FROM stock_movements sm
LEFT JOIN products p ON sm.product_id = p.product_id
LEFT JOIN users u ON sm.created_by = u.user_id
WHERE sm.product_id = ? 
AND sm.status = 'active'
ORDER BY sm.created_at DESC";

$stmt = $connect->prepare($movementsSql);
$stmt->bind_param("i", $itemId);
$stmt->execute();
$movements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Update itemDetails with current stock
$itemDetails['current_stock'] = $currentStock;
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
        background: #f8f9fa;
        border-radius: 8px;
    }
    .flow-node {
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .flow-node:hover circle {
        filter: brightness(95%);
    }
    .flow-link {
        transition: all 0.3s ease;
    }
    .flow-link:hover {
        stroke-width: 3px;
    }
    .flow-label {
        font-family: Arial, sans-serif;
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
    
    /* Add styles for movement history table */
    #movementTable th, #movementTable td {
        vertical-align: middle !important;
    }
    
    .badge-movement-type {
        padding: 5px 10px;
        border-radius: 3px;
        font-weight: normal;
    }
    
    .badge-in {
        background-color: #28a745;
        color: white;
    }
    
    .badge-out {
        background-color: #dc3545;
        color: white;
    }
    
    .badge-adjustment {
        background-color: #ffc107;
        color: black;
    }

    /* Enhanced table styles */
    #movementTable {
        font-size: 13px;
    }

    #movementTable th {
        background-color: #f5f5f5;
        font-weight: 600;
    }

    #movementTable td {
        vertical-align: middle;
    }

    .label {
        display: inline-block;
        min-width: 70px;
        text-align: center;
        padding: 4px 8px;
        font-size: 12px;
        font-weight: normal;
        line-height: 1.4;
    }

    .label-success {
        background-color: #28a745;
    }

    .label-danger {
        background-color: #dc3545;
    }

    .label-warning {
        background-color: #ffc107;
        color: #000;
    }

    .label-default {
        background-color: #6c757d;
    }

    /* Responsive table adjustments */
    @media screen and (max-width: 992px) {
        #movementTable {
            font-size: 12px;
        }
        
        .table-responsive {
            border: none;
            margin-bottom: 0;
        }
    }
</style>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="warehouses.php">Warehouses</a></li>
            <li class="active">Finished Good Details</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-info-circle"></i> Finished Good Details - <?php echo htmlspecialchars($itemDetails['name']); ?>
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
                                    <th>Product Code</th>
                                    <td><?php echo htmlspecialchars($itemDetails['code']); ?></td>
                                </tr>
                                <tr>
                                    <th>Name</th>
                                    <td><?php echo htmlspecialchars($itemDetails['name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Category ID</th>
                                    <td><?php echo htmlspecialchars($itemDetails['category_id']); ?></td>
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
                                <tr>
                                    <th>Minimum Stock Level</th>
                                    <td><?php echo htmlspecialchars($itemDetails['min_stock_level']); ?></td>
                                </tr>
                                <tr>
                                    <th>Cost Per Unit</th>
                                    <td><?php echo htmlspecialchars(number_format($itemDetails['production_cost'], 2)); ?></td>
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
                                        <h4>Total Produced</h4>
                                        <div class="stat-value"><?php echo number_format($totalProduced, 2); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-box">
                                        <h4>Total Sold</h4>
                                        <div class="stat-value"><?php echo number_format($totalSold, 2); ?></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="stat-box">
                                        <h4>Current Stock</h4>
                                        <div class="stat-value"><?php echo number_format($currentStock, 2); ?></div>
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
                            <div class="table-responsive">
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="col-sm-6">
                                        Show 
                                        <select id="movementPageLength" class="form-control input-sm" style="width: auto; display: inline-block; margin: 0 5px;">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="-1">All</option>
                                        </select>
                                        movements per page
                                    </div>
                                    <div class="col-sm-6 text-right">
                                        <label>Search movements: 
                                            <input type="search" id="movementSearch" class="form-control input-sm" placeholder="">
                                        </label>
                                    </div>
                                </div>
                                <table class="table table-striped table-bordered" id="movementTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Quantity</th>
                                            <th>Warehouse</th>
                                            <th>Reference</th>
                                            <th>Details</th>
                                            <th>Notes</th>
                                            <th>By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($movements)): ?>
                                            <?php foreach ($movements as $movement): ?>
                                                <tr>
                                                    <td><?php echo $movement['movement_date']; ?></td>
                                                    <td>
                                                        <span class="label label-<?php echo $movement['movement_type'] === 'in' ? 'success' : 'danger'; ?>">
                                                            <?php echo strtoupper($movement['movement_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $movement['quantity_display']; ?></td>
                                                    <td><?php echo $movement['warehouse_name'] ? htmlspecialchars($movement['warehouse_name']) : 'Main Warehouse'; ?></td>
                                                    <td><?php echo $movement['reference_info'] ? htmlspecialchars($movement['reference_info']) : '-'; ?></td>
                                                    <td>
                                                        <?php 
                                                        if (!empty($movement['reference_info']) && strpos($movement['reference_info'], 'SO #') === 0) {
                                                            $soRef = substr($movement['reference_info'], 0, strpos($movement['reference_info'], ' - '));
                                                            echo 'Sales Order: ' . htmlspecialchars($soRef);
                                                        } else {
                                                            echo '-';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo $movement['details'] ? htmlspecialchars($movement['details']) : '-'; ?></td>
                                                    <td><?php echo $movement['created_by'] ? htmlspecialchars($movement['created_by']) : 'System'; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
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

<!-- D3.js for SVG visualization -->
<script src="https://d3js.org/d3.v7.min.js"></script>
<script>
// Flow diagram visualization
document.addEventListener('DOMContentLoaded', function() {
    const data = {
        produced: <?php echo $totalProduced; ?>,
        sold: <?php echo $totalSold; ?>,
        current: <?php echo $currentStock; ?>
    };

    const svg = d3.select('#flowDiagram');
    const width = svg.node().getBoundingClientRect().width;
    const height = 300;
    const margin = { top: 40, right: 60, bottom: 40, left: 60 };
    const innerWidth = width - margin.left - margin.right;
    const innerHeight = height - margin.top - margin.bottom;

    // Clear existing content
    svg.selectAll('*').remove();

    // Set SVG background
    svg.append('rect')
        .attr('width', width)
        .attr('height', height)
        .attr('fill', '#f8f9fa')
        .attr('rx', 8);

    // Create the flow diagram
    const g = svg.append('g')
        .attr('transform', `translate(${margin.left},${margin.top})`);

    // Calculate maximum value for scaling
    const maxValue = Math.max(data.produced, data.current, data.sold);
    
    // Set up scales
    const barScale = d3.scaleLinear()
        .domain([0, maxValue])
        .range([0, innerHeight * 0.6]);

    // Define node positions and data
    const nodeSpacing = innerWidth / 4;
    const nodes = [
        { id: 'produced', label: 'Produced', value: data.produced, x: nodeSpacing, color: '#28a745', icon: '⬇️' },
        { id: 'inventory', label: 'Current Stock', value: data.current, x: 2 * nodeSpacing, color: '#007bff', icon: '📦' },
        { id: 'sold', label: 'Sold', value: data.sold, x: 3 * nodeSpacing, color: '#dc3545', icon: '⬆️' }
    ];

    // Draw connecting arrows
    const arrowPath = (startX, endX) => {
        const y = innerHeight * 0.7;
        const controlY = y + 20;
        return `M${startX},${y} C${startX + (endX-startX)/3},${controlY} ${endX - (endX-startX)/3},${controlY} ${endX},${y}`;
    };

    // Add arrows with gradients
    nodes.slice(0, -1).forEach((node, i) => {
        const nextNode = nodes[i + 1];
        const gradientId = `flow-gradient-${i}`;
        
        // Create gradient
        const gradient = svg.append('defs')
            .append('linearGradient')
            .attr('id', gradientId)
            .attr('gradientUnits', 'userSpaceOnUse')
            .attr('x1', node.x)
            .attr('x2', nextNode.x);

        gradient.append('stop')
            .attr('offset', '0%')
            .attr('stop-color', node.color)
            .attr('stop-opacity', 0.8);

        gradient.append('stop')
            .attr('offset', '100%')
            .attr('stop-color', nextNode.color)
            .attr('stop-opacity', 0.8);

        // Draw arrow
        g.append('path')
            .attr('d', arrowPath(node.x, nextNode.x))
            .style('fill', 'none')
            .style('stroke', `url(#${gradientId})`)
            .style('stroke-width', 2)
            .attr('marker-end', 'url(#arrow)');
    });

    // Add tooltip div
    const tooltip = d3.select('body').append('div')
        .attr('class', 'tooltip')
        .style('opacity', 0)
        .style('position', 'absolute')
        .style('padding', '10px')
        .style('background', 'rgba(0, 0, 0, 0.8)')
        .style('color', '#fff')
        .style('border-radius', '6px')
        .style('font-size', '12px')
        .style('pointer-events', 'none')
        .style('z-index', '1000')
        .style('box-shadow', '0 2px 4px rgba(0,0,0,0.2)')
        .style('min-width', '120px')
        .style('text-align', 'center');

    // Draw bars and add hover effect
    nodes.forEach((node, i) => {
        const barGroup = g.append('g')
            .attr('transform', `translate(${node.x - 30},${innerHeight * 0.7 - barScale(node.value)})`);

        // Bar background
        barGroup.append('rect')
            .attr('width', 60)
            .attr('height', barScale(node.value))
            .attr('fill', '#fff')
            .attr('stroke', node.color)
            .attr('stroke-width', 1)
            .attr('rx', 4);

        // Bar fill with gradient
        const gradientId = `bar-gradient-${i}`;
        const gradient = svg.append('defs')
            .append('linearGradient')
            .attr('id', gradientId)
            .attr('gradientUnits', 'userSpaceOnUse')
            .attr('x1', '0%')
            .attr('x2', '0%')
            .attr('y1', '100%')
            .attr('y2', '0%');

        gradient.append('stop')
            .attr('offset', '0%')
            .attr('stop-color', node.color)
            .attr('stop-opacity', 0.3);

        gradient.append('stop')
            .attr('offset', '100%')
            .attr('stop-color', node.color)
            .attr('stop-opacity', 0.8);

        barGroup.append('rect')
            .attr('width', 60)
            .attr('height', barScale(node.value))
            .attr('fill', `url(#${gradientId})`)
            .attr('rx', 4)
            .style('cursor', 'pointer')
            .on('mouseover', function(event) {
                d3.select(this)
                    .transition()
                    .duration(200)
                    .attr('fill-opacity', 0.8);
                
                // Calculate tooltip position
                const tooltipWidth = 120; // Estimated tooltip width
                const tooltipHeight = 50; // Estimated tooltip height
                const svgRect = svg.node().getBoundingClientRect();
                const barRect = this.getBoundingClientRect();
                
                // Center tooltip above the bar
                let tooltipX = barRect.left + (barRect.width / 2) - (tooltipWidth / 2);
                let tooltipY = barRect.top - tooltipHeight - 10; // 10px padding above bar
                
                // Ensure tooltip stays within viewport
                tooltipX = Math.max(10, Math.min(tooltipX, window.innerWidth - tooltipWidth - 10));
                tooltipY = Math.max(10, tooltipY);
                
                tooltip.transition()
                    .duration(200)
                    .style('opacity', 1);
                    
                tooltip.html(`<strong>${node.label}</strong><br>${node.value.toFixed(2)}`)
                    .style('left', `${tooltipX}px`)
                    .style('top', `${tooltipY}px`);
            })
            .on('mouseout', function() {
                d3.select(this)
                    .transition()
                    .duration(200)
                    .attr('fill-opacity', 1);
                
                tooltip.transition()
                    .duration(500)
                    .style('opacity', 0);
            });

        // Add icon
        barGroup.append('text')
            .attr('x', 30)
            .attr('y', -25)
            .attr('text-anchor', 'middle')
            .style('font-size', '20px')
            .text(node.icon);

        // Add label
        barGroup.append('text')
            .attr('x', 30)
            .attr('y', barScale(node.value) + 20)
            .attr('text-anchor', 'middle')
            .style('font-size', '12px')
            .style('font-weight', 'bold')
            .style('fill', node.color)
            .text(node.label);

        // Add value
        barGroup.append('text')
            .attr('x', 30)
            .attr('y', barScale(node.value) + 35)
            .attr('text-anchor', 'middle')
            .style('font-size', '12px')
            .style('fill', '#666')
            .text(node.value.toFixed(2));
    });
});

// Initialize DataTable for movement history
$(document).ready(function() {
    var movementTable = $('#movementTable').DataTable({
        "order": [[0, "desc"]],
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "responsive": true,
        "dom": "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        "language": {
            "emptyTable": "No movement history found",
            "zeroRecords": "No matching movements found",
            "info": "Showing _START_ to _END_ of _TOTAL_ movements",
            "infoEmpty": "Showing 0 to 0 of 0 movements"
        }
    });

    // Custom search and page length controls
    $('#movementSearch').on('keyup', function() {
        movementTable.search(this.value).draw();
    });

    $('#movementPageLength').on('change', function() {
        movementTable.page.len(this.value).draw();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 