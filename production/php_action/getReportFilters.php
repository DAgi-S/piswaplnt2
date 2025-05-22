<?php
require_once 'core.php';
require_once 'classes/Database.php';

// Set proper content type for JSON response
header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    echo json_encode([
        'status' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

try {
    if (!isset($_POST['report_type'])) {
        throw new Exception('Report type is required');
    }

    $db = new Database();
    $reportType = $_POST['report_type'];
    $html = '';
    
    // Generate filter HTML based on report type
    switch ($reportType) {
        case 'inventory':
            // Get warehouses for filter
            $warehouses = $db->fetchAll("SELECT id, name FROM warehouses WHERE status = 'active'");
            
            $html = '
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="warehouse_id">Warehouse</label>
                            <select class="form-control select2" name="warehouse_id">
                                <option value="">All Warehouses</option>';
            foreach ($warehouses as $warehouse) {
                $html .= '<option value="' . $warehouse['id'] . '">' . htmlspecialchars($warehouse['name']) . '</option>';
            }
            $html .= '
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="item_type">Item Type</label>
                            <select class="form-control" name="item_type">
                                <option value="">All Types</option>
                                <option value="raw_material">Raw Materials</option>
                                <option value="finished_good">Finished Goods</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="below_min_stock" value="1"> Show Below Minimum Stock
                                </label>
                            </div>
                        </div>
                    </div>
                </div>';
            break;

        case 'stock_movement':
            $html = '
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date">From Date</label>
                            <input type="text" class="form-control datepicker" name="start_date">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date">To Date</label>
                            <input type="text" class="form-control datepicker" name="end_date">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="movement_type">Movement Type</label>
                            <select class="form-control" name="movement_type">
                                <option value="">All Types</option>
                                <option value="in">Stock In</option>
                                <option value="out">Stock Out</option>
                                <option value="transfer">Transfer</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="reference_type">Reference Type</label>
                            <select class="form-control" name="reference_type">
                                <option value="">All References</option>
                                <option value="production_order">Production Order</option>
                                <option value="purchase">Purchase</option>
                                <option value="sale">Sale</option>
                                <option value="adjustment">Adjustment</option>
                            </select>
                        </div>
                    </div>
                </div>';
            break;

        case 'production':
            $html = '
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date">From Date</label>
                            <input type="text" class="form-control datepicker" name="start_date">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date">To Date</label>
                            <input type="text" class="form-control datepicker" name="end_date">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" name="status">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="include_cost" value="1"> Include Cost Analysis
                                </label>
                            </div>
                        </div>
                    </div>
                </div>';
            break;

        case 'material_usage':
            // Get raw materials for filter
            $materials = $db->query("SELECT id, material_code, name FROM raw_materials WHERE status = 'active'")->fetch_all(MYSQLI_ASSOC);
            
            $html = '
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_from">From Date</label>
                            <input type="text" class="form-control datepicker" name="date_from">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_to">To Date</label>
                            <input type="text" class="form-control datepicker" name="date_to">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="material_id">Raw Material</label>
                            <select class="form-control select2" name="material_id">
                                <option value="">All Materials</option>';
            foreach ($materials as $material) {
                $html .= '<option value="' . $material['id'] . '">' . htmlspecialchars($material['material_code'] . ' - ' . $material['name']) . '</option>';
            }
            $html .= '
                            </select>
                        </div>
                    </div>
                </div>';
            break;

        case 'quality':
            $html = '
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_from">From Date</label>
                            <input type="text" class="form-control datepicker" name="date_from">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_to">To Date</label>
                            <input type="text" class="form-control datepicker" name="date_to">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="quality_status">Quality Status</label>
                            <select class="form-control" name="quality_status">
                                <option value="">All Status</option>
                                <option value="passed">Passed</option>
                                <option value="failed">Failed</option>
                                <option value="pending">Pending Review</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="include_defects"> Include Defect Details
                                </label>
                            </div>
                        </div>
                    </div>
                </div>';
            break;

        case 'cost_analysis':
            $html = '
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_from">From Date</label>
                            <input type="text" class="form-control datepicker" name="date_from">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_to">To Date</label>
                            <input type="text" class="form-control datepicker" name="date_to">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="cost_type">Cost Type</label>
                            <select class="form-control" name="cost_type">
                                <option value="">All Types</option>
                                <option value="material">Material Cost</option>
                                <option value="production">Production Cost</option>
                                <option value="overhead">Overhead Cost</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="include_comparison"> Include YoY Comparison
                                </label>
                            </div>
                        </div>
                    </div>
                </div>';
            break;

        default:
            throw new Exception('Invalid report type: ' . $reportType);
    }

    echo json_encode([
        'status' => true,
        'data' => [
            'html' => $html
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Error in getReportFilters.php: " . $e->getMessage());
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
} 