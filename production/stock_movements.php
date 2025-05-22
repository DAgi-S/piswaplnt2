<?php require_once 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Stock Movements</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-exchange"></i> Stock Movements
                    <div class="pull-right">
                        <button class="btn btn-success" data-toggle="modal" data-target="#addStockInModal">
                            <i class="fa fa-plus"></i> Add Stock In
                        </button>
                        <button class="btn btn-warning" data-toggle="modal" data-target="#addStockOutModal">
                            <i class="fa fa-minus"></i> Add Stock Out
                        </button>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <div class="remove-messages"></div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="div-action pull-right" style="padding-bottom:20px;">
                            <button class="btn btn-default" id="exportCSV">CSV</button>
                            <button class="btn btn-default" id="exportExcel">Excel</button>
                            <button class="btn btn-default" id="exportPDF">PDF</button>
                            <button class="btn btn-default" id="print">Print</button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table id="manageStockMovementsTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 12%;">Date</th>
                                        <th style="width: 12%;">Item</th>
                                        <th style="width: 8%;">Quantity</th>
                                        <th style="width: 6%;">Type</th>
                                        <th style="width: 10%;">Ref Type</th>
                                        <th style="width: 8%;">Ref ID</th>
                                        <th style="width: 24%;">Notes</th>
                                        <th style="width: 10%;">Created By</th>
                                        <th style="width: 10%;">Current Stock</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Stock In Modal -->
<div class="modal fade" id="addStockInModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="createStockInForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Stock In</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Item</label>
                        <select class="form-control select-item" name="item_id" required></select>
                    </div>
                    <div class="form-group">
                        <label>Destination Warehouse</label>
                        <select class="form-control" name="destination_id" required>
                            <?php
                            $sql = "SELECT id, name FROM warehouses WHERE status = 'active'";
                            $result = $connect->query($sql);
                            while($row = $result->fetch_array()) {
                                echo "<option value='".$row['id']."'>".$row['name']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Source Type</label>
                        <select class="form-control" name="source_type" required>
                            <option value="supplier">Supplier</option>
                            <option value="production">Production</option>
                            <option value="customer">Customer Return</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reference Type</label>
                        <select class="form-control" name="reference_type" required>
                            <option value="purchase">Purchase</option>
                            <option value="return">Return</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reference ID</label>
                        <input type="text" class="form-control" name="reference_id" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" class="form-control" name="quantity" min="0.01" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                    <input type="hidden" name="movement_type" value="in">
                    <input type="hidden" name="source_id" value="1">
                    <input type="hidden" name="destination_type" value="warehouse">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Stock Out Modal -->
<div class="modal fade" id="addStockOutModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="createStockOutForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-minus"></i> Add Stock Out</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Item</label>
                        <select class="form-control select-item" name="item_id" required></select>
                    </div>
                    <div class="form-group">
                        <label>Source Warehouse</label>
                        <select class="form-control" name="source_id" required>
                            <?php
                            $sql = "SELECT id, name FROM warehouses WHERE status = 'active'";
                            $result = $connect->query($sql);
                            while($row = $result->fetch_array()) {
                                echo "<option value='".$row['id']."'>".$row['name']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Current Stock</label>
                        <p class="form-control-static" id="currentStock">0</p>
                    </div>
                    <div class="form-group">
                        <label>Destination Type</label>
                        <select class="form-control" name="destination_type" required>
                            <option value="customer">Customer</option>
                            <option value="production">Production</option>
                            <option value="supplier">Return to Supplier</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reference Type</label>
                        <select class="form-control" name="reference_type" required>
                            <option value="sale">Sale</option>
                            <option value="damage">Damage</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reference ID</label>
                        <input type="text" class="form-control" name="reference_id" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" class="form-control" name="quantity" min="0.01" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                    <input type="hidden" name="movement_type" value="out">
                    <input type="hidden" name="destination_id" value="1">
                    <input type="hidden" name="source_type" value="warehouse">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Remove Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Stock Movement Modal -->
<div class="modal fade" id="viewMovementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="fa fa-eye"></i> View Stock Movement</h4>
            </div>
            <div class="modal-body">
                <!-- Content will be dynamically loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Stock Movement Modal -->
<div class="modal fade" id="editMovementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editMovementForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Stock Movement</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="movement_id">
                    <div class="form-group">
                        <label>Product</label>
                        <select class="form-control select-product" name="product_id" required></select>
                    </div>
                    <div class="form-group">
                        <label>Reference Type</label>
                        <select class="form-control" name="reference_type" required>
                            <option value="purchase">Purchase</option>
                            <option value="return">Return</option>
                            <option value="sale">Sale</option>
                            <option value="damage">Damage</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Reference ID</label>
                        <input type="text" class="form-control" name="reference_id" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" class="form-control" name="quantity" min="0.01" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
    .label {
        display: inline-block;
        min-width: 50px;
        text-align: center;
        padding: 3px 6px;
        font-size: 11px;
    }
    .label-out {
        background-color: #d9534f;
        color: white;
    }
    .label-in {
        background-color: #5cb85c;
        color: white;
    }
    .current-stock-zero {
        color: #d9534f;
        font-weight: bold;
    }
    .div-action {
        margin-bottom: 15px;
    }
    .div-action button {
        margin-left: 5px;
    }

    /* Updated DataTable Styling */
    #manageStockMovementsTable {
        width: 100% !important;
        table-layout: fixed;
    }
    #manageStockMovementsTable th, 
    #manageStockMovementsTable td {
        vertical-align: middle;
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    #manageStockMovementsTable td {
        padding: 8px;
        font-size: 13px;
    }
    
    /* Column specific styles */
    #manageStockMovementsTable td:nth-child(7) { /* Notes column */
        white-space: normal;
        word-break: break-word;
    }
    #manageStockMovementsTable td:nth-child(3), /* Quantity */
    #manageStockMovementsTable td:nth-child(4), /* Type */
    #manageStockMovementsTable td:nth-child(6), /* Ref ID */
    #manageStockMovementsTable td:nth-child(9) { /* Current Stock */
        text-align: center;
    }
    
    /* Ensure table fits container */
    .table-responsive {
        border: none;
        margin-bottom: 0;
        overflow-x: hidden;
    }
    
    /* Adjust label sizes */
    .label {
        display: inline-block;
        min-width: 50px;
        text-align: center;
        padding: 3px 6px;
        font-size: 11px;
    }

    /* Quantity colors */
    .text-success {
        color: #5cb85c;
        font-weight: bold;
    }
    .text-danger {
        color: #d9534f;
        font-weight: bold;
    }
</style>

<!-- Include DataTables -->
<link rel="stylesheet" type="text/css" href="assets/plugin/datatables/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="assets/plugin/datatables/buttons.dataTables.min.css">
<script src="assets/plugin/datatables/jquery.dataTables.min.js"></script>
<script src="assets/plugin/datatables/dataTables.buttons.min.js"></script>
<script src="assets/plugin/datatables/buttons.html5.min.js"></script>
<script src="assets/plugin/datatables/buttons.print.min.js"></script>

<!-- Custom JavaScript -->
<script>
$(document).ready(function() {
    var stockMovementsTable = $('#manageStockMovementsTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchRawMaterialMovements.php',
            'type': 'POST'
        },
        'order': [[0, 'desc']],
        'columns': [
            { 
                'data': 'created_at',
                'render': function(data) {
                    return moment(data).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            { 'data': 'item_name' },
            {
                'data': 'quantity',
                'render': function(data, type, row) {
                    var color = row.movement_type.toLowerCase() === 'out' ? 'text-danger' : 'text-success';
                    var prefix = row.movement_type.toLowerCase() === 'out' ? '-' : '+';
                    return '<span class="' + color + '">' + prefix + parseFloat(data).toFixed(2) + '</span>';
                }
            },
            {
                'data': 'movement_type',
                'render': function(data) {
                    var type = data.toLowerCase();
                    return '<span class="label label-' + type + '">' + data.toUpperCase() + '</span>';
                }
            },
            { 
                'data': 'reference_type',
                'render': function(data) {
                    return data ? data.charAt(0).toUpperCase() + data.slice(1) : '';
                }
            },
            { 'data': 'reference_id' },
            { 
                'data': 'notes',
                'render': function(data) {
                    return data || '<em>No notes</em>';
                }
            },
            { 'data': 'created_by' },
            {
                'data': 'current_stock',
                'render': function(data) {
                    var stock = parseFloat(data).toFixed(2);
                    var colorClass = parseFloat(stock) <= 0 ? 'text-danger' : 'text-success';
                    return '<span class="' + colorClass + '">' + stock + '</span>';
                }
            }
        ],
        'pageLength': 10,
        'responsive': true,
        'dom': '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip',
        'language': {
            'search': '_INPUT_',
            'searchPlaceholder': 'Search records...',
            'lengthMenu': '_MENU_ records per page',
            'info': 'Showing _START_ to _END_ of _TOTAL_ records',
            'infoEmpty': 'Showing 0 to 0 of 0 records',
            'infoFiltered': '(filtered from _MAX_ total records)',
            'zeroRecords': 'No matching records found'
        },
        'initComplete': function() {
            // Hide the default buttons since we have custom ones
            $('.dt-buttons').hide();
        }
    });

    // Export buttons functionality
    $('#exportCSV').on('click', function() {
        window.location = 'php_action/exportStockMovements.php?type=csv';
    });

    $('#exportExcel').on('click', function() {
        window.location = 'php_action/exportStockMovements.php?type=excel';
    });

    $('#exportPDF').on('click', function() {
        window.location = 'php_action/exportStockMovements.php?type=pdf';
    });

    $('#print').on('click', function() {
        window.print();
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>