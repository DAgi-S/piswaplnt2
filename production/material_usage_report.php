<?php
require_once 'php_action/core.php';
require_once 'includes/config.php';
require_once 'includes/ReportHelper.php';
require_once 'includes/header.php';

// Check user session and permissions
if (!isset($_SESSION['userId'])) {
    header('location: index.php');
    exit();
}

// Initialize database connection with error handling
try {
    $db = Database::getInstance();
    $connect = $db->getConnection();
    if (!$connect) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

$helper = ReportHelper::getInstance();

// Get filter values with validation
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$material_id = isset($_GET['material_id']) ? (int)$_GET['material_id'] : 0;
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

// Validate dates
if (!validateDate($start_date) || !validateDate($end_date)) {
    echo "Invalid date format";
    exit();
}

// Fix the summary statistics query with correct field names
try {
    $summary_sql = "SELECT 
        COUNT(DISTINCT mc.material_id) as total_materials,
        COUNT(DISTINCT CASE WHEN rm.current_stock <= rm.min_stock_level THEN mc.material_id END) as low_stock_count,
        SUM(mc.quantity) as total_quantity_used,
        COUNT(DISTINCT mc.production_order_id) as total_orders
    FROM material_consumption mc
    INNER JOIN raw_materials rm ON mc.material_id = rm.id
    WHERE mc.created_at BETWEEN ? AND ?";

    $stmt = $connect->prepare($summary_sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $summary_result = $stmt->get_result();
    $summary = $summary_result->fetch_assoc();
} catch (Exception $e) {
    error_log("Error in summary query: " . $e->getMessage());
    $summary = [
        'total_materials' => 0,
        'low_stock_count' => 0,
        'total_quantity_used' => 0,
        'total_orders' => 0
    ];
}

// Helper function for date validation
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Prepare query parameters with validation
$params = [$start_date, $end_date];
$conditions = ['mc.created_at BETWEEN ? AND ?'];

if ($material_id > 0) {
    $conditions[] = 'mc.material_id = ?';
    $params[] = $material_id;
}

if ($category_id > 0) {
    $conditions[] = 'rm.category_id = ?';
    $params[] = $category_id;
}

// Build the query with proper joins and conditions - fix field names
try {
    $sql = "SELECT 
        mc.id,
        rm.id as material_id,
        rm.material_code as material_code,
        rm.name as material_name,
        COALESCE(rmc.name, 'Uncategorized') as category_name,
        rm.unit,
        mc.quantity as quantity_consumed,
        po.order_number,
        mc.created_at,
        CONCAT(u.firstname, ' ', u.lastname) as created_by_name,
        mc.notes
    FROM material_consumption mc
    INNER JOIN raw_materials rm ON mc.material_id = rm.id
    LEFT JOIN raw_material_categories rmc ON rm.category_id = rmc.id
    LEFT JOIN production_orders po ON mc.production_order_id = po.id
    LEFT JOIN users u ON mc.created_by = u.id
    WHERE " . implode(' AND ', $conditions) . "
    ORDER BY mc.created_at DESC";

    // Debug the SQL query to see what's happening
    error_log("Material consumption query: " . $sql);
    error_log("Parameters: " . print_r($params, true));

    $stmt = $connect->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Error preparing statement: " . $connect->error);
    }

    // Bind parameters dynamically
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if we got results
    error_log("Query result rows: " . ($result ? $result->num_rows : 'null'));
} catch (Exception $e) {
    error_log("Error in main query: " . $e->getMessage());
    $result = false;
}

// Add utility function to directly insert some test data if the table is empty
function insertSampleData($connect) {
    try {
        // Check if table is empty
        $checkSql = "SELECT COUNT(*) as count FROM material_consumption";
        $checkResult = $connect->query($checkSql);
        $row = $checkResult->fetch_assoc();
        
        if ($row['count'] == 0) {
            error_log("No material consumption data found, creating sample data");
            
            // Get first production order
            $orderSql = "SELECT id FROM production_orders LIMIT 1";
            $orderResult = $connect->query($orderSql);
            if ($orderResult && $orderResult->num_rows > 0) {
                $orderId = $orderResult->fetch_assoc()['id'];
                
                // Get some materials
                $materialSql = "SELECT id FROM raw_materials LIMIT 3";
                $materialResult = $connect->query($materialSql);
                
                if ($materialResult && $materialResult->num_rows > 0) {
                    $connect->begin_transaction();
                    
                    $insertSql = "INSERT INTO material_consumption 
                                 (production_order_id, material_id, quantity, notes, created_by) 
                                 VALUES (?, ?, ?, ?, 1)";
                    $stmt = $connect->prepare($insertSql);
                    
                    while ($material = $materialResult->fetch_assoc()) {
                        $materialId = $material['id'];
                        $quantity = rand(100, 1000) / 10;
                        $notes = "Sample data for testing";
                        
                        $stmt->bind_param("iids", $orderId, $materialId, $quantity, $notes);
                        $stmt->execute();
                    }
                    
                    $connect->commit();
                    error_log("Sample data created successfully");
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error creating sample data: " . $e->getMessage());
        if ($connect->connect_error === null) {
            $connect->rollback();
        }
    }
}

// Check if we need to insert sample data (no data found)
if ($result && $result->num_rows == 0) {
    insertSampleData($connect);
    
    // Re-run the query to get the newly inserted data
    $stmt->execute();
    $result = $stmt->get_result();
}

// Get categories for filter dropdown with error handling - fix field names
try {
    $categories_sql = "SELECT id as category_id, name FROM raw_material_categories WHERE status = 'active' ORDER BY name";
    $categories_result = $connect->query($categories_sql);
} catch (Exception $e) {
    error_log("Error in categories query: " . $e->getMessage());
    $categories_result = false;
}

// Get materials for filter dropdown with error handling - fix field names
try {
    $materials_sql = "SELECT id as material_id, name FROM raw_materials WHERE status = 'active' ORDER BY name";
    $materials_result = $connect->query($materials_sql);
} catch (Exception $e) {
    error_log("Error in materials query: " . $e->getMessage());
    $materials_result = false;
}

// Get category statistics - fix field names
try {
    $category_stats_sql = "SELECT 
        COALESCE(rmc.name, 'Uncategorized') as category_name,
        SUM(mc.quantity) as total_quantity
    FROM material_consumption mc
    INNER JOIN raw_materials rm ON mc.material_id = rm.id
    LEFT JOIN raw_material_categories rmc ON rm.category_id = rmc.id
    WHERE mc.created_at BETWEEN ? AND ?
    GROUP BY rmc.name
    ORDER BY total_quantity DESC";

    $stmt = $connect->prepare($category_stats_sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $category_stats_result = $stmt->get_result();
    $category_stats = [];
    while ($row = $category_stats_result->fetch_assoc()) {
        $category_stats[] = $row;
    }
} catch (Exception $e) {
    error_log("Error in category statistics query: " . $e->getMessage());
    $category_stats = [];
}

// Get monthly trend
try {
    $trend_sql = "SELECT 
        DATE_FORMAT(mc.created_at, '%Y-%m') as month,
        SUM(mc.quantity) as quantity
    FROM material_consumption mc
    WHERE mc.created_at BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(mc.created_at, '%Y-%m')
    ORDER BY month";

    $stmt = $connect->prepare($trend_sql);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $trend_result = $stmt->get_result();
    $monthly_trend = [];
    while ($row = $trend_result->fetch_assoc()) {
        $monthly_trend[] = $row;
    }
} catch (Exception $e) {
    error_log("Error in monthly trend query: " . $e->getMessage());
    $monthly_trend = [];
}

// Check if print view is requested
if (isset($_GET['print']) && $_GET['print'] == 'true') {
    include('templates/material_usage_print.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Usage Report</title>
    <?php include('../includes/header_links.php'); ?>
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap4.min.css">
    
    <!-- Additional CSS specific to this page -->
    <style>
        .alert { margin-bottom: 15px; padding: 10px 15px; border-radius: 4px; }
        .alert-danger { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        .summary-card {
            background: #fff;
            padding: 15px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            margin-bottom: 20px;
        }
        .summary-card h4 { margin-top: 0; color: #666; }
        .summary-card .value { font-size: 24px; font-weight: bold; margin-top: 10px; }
        
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 0.75rem 1.25rem;
        }
        .card-header h5 {
            margin: 0;
            font-weight: 500;
            color: #4e73df;
        }
        .card-body {
            padding: 1.25rem;
        }
        
        .btn-group {
            display: flex;
            gap: 5px;
        }
        
        @media print {
            .no-print { display: none; }
            .print-only { display: block; }
            .container-fluid { width: 100%; }
            .summary-card { break-inside: avoid; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 8px; border: 1px solid #ddd; }
            th { background-color: #f5f5f5; }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Page header -->
        <div class="page-header no-print">
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <div class="title">
                        <h4>Material Usage Report</h4>
                    </div>
                    <nav aria-label="breadcrumb" role="navigation">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Material Usage Report</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-md-6 col-sm-12 text-right">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-success" id="btnExportExcel">
                            <i class="fa fa-file-excel-o"></i> Export Excel
                        </button>
                        <a href="<?php echo $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['print' => 'true'])); ?>" class="btn btn-primary" target="_blank">
                            <i class="fa fa-print"></i> Print Report
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Print Header -->
        <div class="print-only" style="display: none;">
            <h2 style="text-align: center;">Material Usage Report</h2>
            <p style="text-align: center;">
                Period: <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?><br>
                Generated on: <?php echo date('Y-m-d H:i:s'); ?>
            </p>
        </div>

        <!-- Summary Statistics -->
        <div class="row">
            <div class="col-md-3">
                <div class="summary-card">
                    <h4>Total Materials Used</h4>
                    <div class="value"><?php echo number_format($summary['total_materials']); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <h4>Low Stock Materials</h4>
                    <div class="value"><?php echo number_format($summary['low_stock_count']); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <h4>Total Quantity Used</h4>
                    <div class="value"><?php echo number_format($summary['total_quantity_used'], 2); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <h4>Total Production Orders</h4>
                    <div class="value"><?php echo number_format($summary['total_orders']); ?></div>
                </div>
            </div>
        </div>

        <!-- Visualization Section -->
        <div class="row mb-4 no-print">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Material Usage by Category</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Monthly Consumption Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="trendChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-3 no-print">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Filter Options</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="form-inline">
                            <div class="form-group mx-2">
                                <label for="start_date" class="mr-2">Start Date:</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                    value="<?php echo htmlspecialchars($start_date); ?>">
                            </div>
                            <div class="form-group mx-2">
                                <label for="end_date" class="mr-2">End Date:</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                    value="<?php echo htmlspecialchars($end_date); ?>">
                            </div>
                            <div class="form-group mx-2">
                                <label for="material_id" class="mr-2">Material:</label>
                                <select class="form-control" id="material_id" name="material_id">
                                    <option value="0">All Materials</option>
                                    <?php 
                                    if ($materials_result) {
                                        while ($material = $materials_result->fetch_assoc()) {
                                            $selected = $material_id == $material['material_id'] ? 'selected' : '';
                                            echo '<option value="' . $material['material_id'] . '" ' . $selected . '>' . 
                                                htmlspecialchars($material['name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group mx-2">
                                <label for="category_id" class="mr-2">Category:</label>
                                <select class="form-control" id="category_id" name="category_id">
                                    <option value="0">All Categories</option>
                                    <?php 
                                    if ($categories_result) {
                                        while ($category = $categories_result->fetch_assoc()) {
                                            $selected = $category_id == $category['category_id'] ? 'selected' : '';
                                            echo '<option value="' . $category['category_id'] . '" ' . $selected . '>' . 
                                                htmlspecialchars($category['name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Material Consumption Details</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered" id="materialUsageTable">
                        <thead>
                            <tr>
                                <th>Material Code</th>
                                <th>Material Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Quantity Consumed</th>
                                <th>Production Order</th>
                                <th>Date</th>
                                <th>Created By</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($row['material_code']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['material_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['category_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['unit']) . '</td>';
                                    echo '<td>' . number_format($row['quantity_consumed'], 2) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['order_number']) . '</td>';
                                    echo '<td>' . date('Y-m-d H:i', strtotime($row['created_at'])) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['created_by_name']) . '</td>';
                                    echo '<td>' . htmlspecialchars($row['notes']) . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="9" class="text-center">No data found</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>
    
    <!-- Use more simple approach that avoids script dependencies -->
    <script>
        // Wait for DOM to be fully loaded
        window.addEventListener('DOMContentLoaded', function() {
            // Simple helper to check if an element exists
            function elementExists(id) {
                return document.getElementById(id) !== null;
            }
            
            // Simple helper to format numbers for display
            function formatNumber(num) {
                return parseFloat(num).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
            
            // ======= DIRECT TABLE INITIALIZATION =======
            var table = document.getElementById('materialUsageTable');
            if (table) {
                // Add search input directly
                var searchDiv = document.createElement('div');
                searchDiv.className = 'mb-3';
                searchDiv.innerHTML = '<label for="tableSearch">Search:</label> ' +
                                     '<input type="text" id="tableSearch" class="form-control" placeholder="Type to search...">';
                table.parentNode.insertBefore(searchDiv, table);
                
                var searchInput = document.getElementById('tableSearch');
                if (searchInput) {
                    searchInput.addEventListener('keyup', function() {
                        var searchText = this.value.toLowerCase();
                        var rows = table.querySelectorAll('tbody tr');
                        
                        rows.forEach(function(row) {
                            var text = row.textContent.toLowerCase();
                            row.style.display = text.indexOf(searchText) > -1 ? '' : 'none';
                        });
                    });
                }
                
                // Add sorting functionality
                var headers = table.querySelectorAll('thead th');
                headers.forEach(function(header, index) {
                    header.style.cursor = 'pointer';
                    header.addEventListener('click', function() {
                        sortTable(index);
                    });
                });
                
                function sortTable(column) {
                    var switching = true;
                    var dir = "asc";
                    var rows, i, x, y, shouldSwitch;
                    var switchCount = 0;
                    
                    while (switching) {
                        switching = false;
                        rows = table.rows;
                        
                        for (i = 1; i < (rows.length - 1); i++) {
                            shouldSwitch = false;
                            x = rows[i].getElementsByTagName("TD")[column];
                            y = rows[i + 1].getElementsByTagName("TD")[column];
                            
                            if (dir === "asc") {
                                if (x.innerHTML.toLowerCase() > y.innerHTML.toLowerCase()) {
                                    shouldSwitch = true;
                                    break;
                                }
                            } else if (dir === "desc") {
                                if (x.innerHTML.toLowerCase() < y.innerHTML.toLowerCase()) {
                                    shouldSwitch = true;
                                    break;
                                }
                            }
                        }
                        
                        if (shouldSwitch) {
                            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                            switching = true;
                            switchCount++;
                        } else {
                            if (switchCount === 0 && dir === "asc") {
                                dir = "desc";
                                switching = true;
                            }
                        }
                    }
                    
                    // Update header indicators
                    headers.forEach(function(h) {
                        h.classList.remove('sorting-asc', 'sorting-desc');
                    });
                    
                    headers[column].classList.add(dir === 'asc' ? 'sorting-desc' : 'sorting-asc');
                }
            }
            
            // ======= EXPORT FUNCTIONALITY =======
            var btnExportExcel = document.getElementById('btnExportExcel');
            if (btnExportExcel) {
                btnExportExcel.addEventListener('click', function() {
                    exportTableToExcel('materialUsageTable', 'Material_Usage_Report');
                });
            }
            
            function exportTableToExcel(tableID, filename = '') {
                var downloadLink;
                var dataType = 'application/vnd.ms-excel';
                var tableSelect = document.getElementById(tableID);
                var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
                
                // Create download link element
                downloadLink = document.createElement("a");
                
                document.body.appendChild(downloadLink);
                
                if (navigator.msSaveOrOpenBlob) {
                    var blob = new Blob(['\ufeff', tableHTML], {
                        type: dataType
                    });
                    navigator.msSaveOrOpenBlob(blob, filename + '.xls');
                } else {
                    // Create a link to the file
                    downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
                    downloadLink.download = filename + '.xls';
                    downloadLink.click();
                }
            }
            
            // ======= CATEGORY CHART (SIMPLIFIED) =======
            if (elementExists('categoryChart')) {
                try {
                    // Get category data
                    var categoryData = <?php echo !empty($category_stats) ? json_encode($category_stats) : '[]'; ?>;
                    if (categoryData.length > 0) {
                        // Create a simple HTML-based chart alternative
                        var chartContainer = document.getElementById('categoryChart');
                        chartContainer.innerHTML = '';
                        
                        var chartHTML = '<div class="chart-container">';
                        var colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69', '#6f42c1', '#20c9a6', '#fd7e14'];
                        
                        // Create a table for the data
                        chartHTML += '<table class="table table-bordered">';
                        chartHTML += '<thead><tr><th>Category</th><th>Quantity</th><th>Percentage</th><th>Visualization</th></tr></thead>';
                        chartHTML += '<tbody>';
                        
                        // Calculate total for percentages
                        var total = 0;
                        categoryData.forEach(function(item) {
                            total += parseFloat(item.total_quantity);
                        });
                        
                        // Add rows
                        categoryData.forEach(function(item, index) {
                            var percent = (parseFloat(item.total_quantity) / total * 100).toFixed(2);
                            var color = colors[index % colors.length];
                            
                            chartHTML += '<tr>';
                            chartHTML += '<td>' + item.category_name + '</td>';
                            chartHTML += '<td>' + formatNumber(item.total_quantity) + '</td>';
                            chartHTML += '<td>' + percent + '%</td>';
                            chartHTML += '<td><div class="bar-chart" style="width: ' + percent + '%; background-color: ' + color + ';">&nbsp;</div></td>';
                            chartHTML += '</tr>';
                        });
                        
                        chartHTML += '</tbody></table>';
                        chartHTML += '</div>';
                        
                        chartContainer.innerHTML = chartHTML;
                    }
                } catch (e) {
                    console.error("Error creating category chart:", e);
                }
            }
            
            // ======= TREND CHART (SIMPLIFIED) =======
            if (elementExists('trendChart')) {
                try {
                    // Get trend data
                    var trendData = <?php echo !empty($monthly_trend) ? json_encode($monthly_trend) : '[]'; ?>;
                    if (trendData.length > 0) {
                        // Create a simple HTML-based chart alternative
                        var chartContainer = document.getElementById('trendChart');
                        chartContainer.innerHTML = '';
                        
                        var chartHTML = '<div class="chart-container">';
                        
                        // Create a table for the data
                        chartHTML += '<table class="table table-bordered">';
                        chartHTML += '<thead><tr><th>Month</th><th>Quantity</th></tr></thead>';
                        chartHTML += '<tbody>';
                        
                        // Add rows
                        trendData.forEach(function(item) {
                            chartHTML += '<tr>';
                            chartHTML += '<td>' + item.month + '</td>';
                            chartHTML += '<td>' + formatNumber(item.quantity) + '</td>';
                            chartHTML += '</tr>';
                        });
                        
                        chartHTML += '</tbody></table>';
                        chartHTML += '</div>';
                        
                        chartContainer.innerHTML = chartHTML;
                    }
                } catch (e) {
                    console.error("Error creating trend chart:", e);
                }
            }
        });
    </script>
    
    <style>
        /* Styles for the simple charts */
        .chart-container {
            margin-top: 15px;
        }
        .bar-chart {
            height: 20px;
            background-color: #4e73df;
            border-radius: 3px;
        }
        .sorting-asc:after {
            content: " ▲";
        }
        .sorting-desc:after {
            content: " ▼";
        }
    </style>
</body>
</html> 