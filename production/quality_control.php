<?php 
require_once 'includes/header.php';
?>

<!-- Required CSS -->
<link rel="stylesheet" href="../assests/plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="../assests/plugins/datatables/css/dataTables.bootstrap.min.css">
<link rel="stylesheet" href="../assests/plugins/sweetalert2/sweetalert2.min.css">

<!-- Required JavaScript at bottom -->
</head>
<body>

<!-- Debug message container - place right after body for better visibility -->

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Quality Control</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-check-square"></i> Quality Control
                    <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#addQCModal">
                        <i class="fa fa-plus"></i> Add QC Entry
                    </button>
                </div>
            </div>

            <div class="panel-body">
                <div class="remove-messages"></div>

                <table class="table table-striped" id="qcTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Production Order</th>
                            <th>Product</th>
                            <th>Qty Checked</th>
                            <th>Qty Passed</th>
                            <th>Qty Failed</th>
                            <th>Defect Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add QC Modal -->
<div class="modal fade" id="addQCModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitQCForm" action="php_action/createQC.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Quality Control Entry</h4>
                </div>
                <div class="modal-body">
                    <div id="add-qc-messages"></div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Production Order:<span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <select class="form-control select2" id="productionOrderId" name="productionOrderId" style="width: 100%;" required>
                                <option value="">Select Production Order</option>
                                <?php
                                $sql = "SELECT po.id, po.order_number, pp.name as product_name, po.target_quantity 
                                       FROM production_orders po 
                                       JOIN production_products pp ON po.product_id = pp.id 
                                       WHERE po.status IN ('inprogress', 'completed')
                                       ORDER BY po.created_at DESC";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_array()) {
                                    echo "<option value='".$row['id']."' data-target='".$row['target_quantity']."'>"
                                        .$row['order_number']." - ".$row['product_name']
                                        ." (Target: ".$row['target_quantity'].")</option>";
                                }
                                ?>
                            </select>
                            <small class="help-block">Only in-progress and completed orders are shown</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Inspection Date:<span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="inspectionDate" name="inspectionDate" 
                                   value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                            <small class="help-block">Cannot be a future date</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Quantity Checked:<span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <input type="number" class="form-control" id="quantityChecked" name="quantityChecked" 
                                       placeholder="Enter quantity checked" step="0.01" min="0.01" required>
                                <span class="input-group-addon">units</span>
                            </div>
                            <small class="help-block">Must be greater than 0</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Quantity Passed:<span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <input type="number" class="form-control" id="quantityPassed" name="quantityPassed" 
                                       placeholder="Enter quantity passed" step="0.01" min="0" required>
                                <span class="input-group-addon">units</span>
                            </div>
                            <small class="help-block quantity-validation"></small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Quantity Failed:</label>
                        <div class="col-sm-9">
                            <div class="input-group">
                                <input type="number" class="form-control" id="quantityFailed" name="quantityFailed" 
                                       placeholder="Auto-calculated" step="0.01" min="0" readonly>
                                <span class="input-group-addon">units</span>
                            </div>
                            <small class="help-block">Automatically calculated (Checked - Passed)</small>
                        </div>
                    </div>

                    <div class="form-group" id="defectTypeGroup" style="display: none;">
                        <label class="control-label col-sm-3">Defect Type:</label>
                        <div class="col-sm-9">
                            <select class="form-control select2" id="defectType" name="defectType" style="width: 100%;">
                                <option value="">Select Defect Type</option>
                                <?php
                                $sql = "SELECT defect_name, severity_level FROM quality_defect_types ORDER BY severity_level DESC";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_array()) {
                                    echo "<option value='".$row['defect_name']."'>".$row['defect_name']." (".$row['severity_level'].")</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Notes:</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Enter additional notes or observations"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createQCBtn">
                        <i class="fa fa-plus"></i> Create Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit QC Modal -->
<div class="modal fade" id="editQCModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="editQCForm" action="php_action/editQC.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Quality Control Entry</h4>
                </div>
                <div class="modal-body">
                    <div id="edit-qc-messages"></div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Production Order:</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="editProductionOrderId" name="editProductionOrderId" required>
                                <option value="">Select Production Order</option>
                                <?php
                                $sql = "SELECT po.id, po.order_number, pp.name as product_name 
                                       FROM production_orders po 
                                       JOIN production_products pp ON po.product_id = pp.id 
                                       WHERE po.status IN ('inprogress', 'completed')
                                       ORDER BY po.created_at DESC";
                                $result = $connect->query($sql);
                                while($row = $result->fetch_array()) {
                                    echo "<option value='".$row['id']."'>".$row['order_number']." - ".$row['product_name']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Inspection Date:</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="editInspectionDate" name="editInspectionDate" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Quantity Checked:</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="editQuantityChecked" name="editQuantityChecked" placeholder="Quantity Checked" step="0.01" min="0.01" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Quantity Passed:</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="editQuantityPassed" name="editQuantityPassed" placeholder="Quantity Passed" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Quantity Failed:</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="editQuantityFailed" name="editQuantityFailed" placeholder="Quantity Failed" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Defect Type:</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="editDefectType" name="editDefectType" placeholder="Type of Defect (if any)">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-3">Notes:</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="editNotes" name="editNotes" rows="3" placeholder="Additional Notes"></textarea>
                        </div>
                    </div>
                    <input type="hidden" name="qcId" id="qcId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="editQCBtn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewQCModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-eye"></i> Quality Control Details</h4>
            </div>
            <div class="modal-body">
                <div id="qcDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
    .label-passed {
        background-color: #5cb85c;
    }
    .label-failed {
        background-color: #d9534f;
    }
    .label-partially-passed {
        background-color: #f0ad4e;
    }
    .select2-container {
        width: 100% !important;
    }
    /* DataTables sorting icons fix */
    table.dataTable thead .sorting,
    table.dataTable thead .sorting_asc,
    table.dataTable thead .sorting_desc {
        background-image: none !important;
        position: relative;
    }
    
    table.dataTable thead .sorting:after {
        content: "↕";
        position: absolute;
        right: 8px;
        color: #888;
    }
    
    table.dataTable thead .sorting_asc:after {
        content: "↑";
        position: absolute;
        right: 8px;
        color: #000;
    }
    
    table.dataTable thead .sorting_desc:after {
        content: "↓";
        position: absolute;
        right: 8px;
        color: #000;
    }
    
    /* Responsive table styling */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Adding debug message styling */
    #debug-message {
        margin-top: 15px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: #f9f9f9;
        display: none;
    }

    /* Custom styles for the development notice */
    .custom-swal-popup {
        font-family: 'Arial', sans-serif;
        border-radius: 10px;
    }

    .custom-swal-title {
        color: #2196F3;
        font-size: 24px;
    }

    .custom-swal-content {
        font-size: 16px;
        color: #555;
    }
</style>

<!-- Required JavaScript -->
<script src="../assests/plugins/select2/js/select2.min.js"></script>
<script src="../assests/plugins/datatables/js/jquery.dataTables.min.js"></script>
<script src="../assests/plugins/datatables/js/dataTables.bootstrap.min.js"></script>
<script src="../assests/plugins/sweetalert2/js/sweetalert2.min.js"></script>
<script src="../assests/plugins/moment/moment.min.js"></script>

<!-- Custom JavaScript for Quality Control -->
<script>
// Debug function to display messages
function showDebug(message) {
    var $debug = $('#debug-message');
    
    // Format objects for better display
    if (typeof message === 'object') {
        message = JSON.stringify(message, null, 2);
    }
    
    // Show in debug div with timestamp
    $debug.show().append('<div class="debug-entry">' + 
        '<span class="debug-time">' + new Date().toLocaleTimeString() + ':</span> ' + 
        '<span class="debug-msg">' + message + '</span>' +
        '</div>');
    console.log('Debug:', message);
}

// Test directly fetching data
fetch('php_action/fetchQC.php', {
    method: 'POST'
})
.then(response => {
    showDebug('Fetch response status: ' + response.status);
    return response.text();
})
.then(text => {
    showDebug('Fetch raw response: ' + text);
    try {
        const data = JSON.parse(text);
        if (data.debug) {
            showDebug('Server debug info: ' + data.debug.join('\n'));
        }
        showDebug('Parse success, rows: ' + (data.data ? data.data.length : 0));
    } catch (e) {
        showDebug('JSON parse error: ' + e.message);
    }
})
.catch(error => {
    showDebug('Fetch error: ' + error);
});

// Initial debugging check
$(document).ready(function() {
    console.log('Document ready event fired');
    
    // Handle jQuery AJAX error globally
    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        console.error('AJAX Error:', thrownError, jqxhr.status, jqxhr.responseText);
        showDebug('Global AJAX Error: ' + thrownError + ' ' + jqxhr.status);
    });
    
    // Properly set DataTables image path
    $.fn.dataTable.ext.errMode = 'none'; // Set error handling to none to use our custom handler
    
    // Check for required libraries
    if (typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
    } else {
        console.log('jQuery is loaded: ' + $.fn.jquery);
    }
    
    if (typeof $.fn.DataTable === 'undefined') {
        console.error('DataTables is not loaded');
    } else {
        console.log('DataTables is loaded');
    }
    
    // Initialize DataTable with error handling
    try {
        // Initialize DataTable with direct options to avoid relying on defaults
        var qcTable = $('#qcTable').DataTable({
            'processing': true,
            'language': {
                'processing': 'Loading...',
                'emptyTable': 'No quality control entries found',
                'zeroRecords': 'No matching records found',
                'loadingRecords': 'Loading...'
            },
            'ajax': {
                'url': 'php_action/fetchQC.php',
                'type': 'POST',
                'dataSrc': function(json) {
                    console.log('DataTable response received');
                    
                    if (typeof json === 'undefined') {
                        console.error('Response is undefined');
                        return [];
                    }
                    
                    if (json.error) {
                        console.error('Error from server:', json.error);
                        // Show error message in the table container
                        $('.remove-messages').html(
                            '<div class="alert alert-danger">' + 
                                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                                'Error: ' + json.error + 
                            '</div>'
                        );
                        return [];
                    }
                    
                    if (!json.data) {
                        console.error('No data property in response');
                        return [];
                    }
                    
                    console.log('Data rows:', json.data.length);
                    return json.data;
                },
                'error': function(xhr, error, thrown) {
                    console.error('DataTables AJAX error:', error, thrown);
                    console.log('Response text:', xhr.responseText);
                    
                    // Show error message in the table container
                    $('.remove-messages').html(
                        '<div class="alert alert-danger">' + 
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                            'Ajax Error: ' + error + ' - ' + thrown + 
                        '</div>'
                    );
                }
            },
            'order': [[0, 'desc']],
            'columns': [
                { 
                    data: 'inspection_date',
                    render: function(data) {
                        try {
                            return moment(data).format('DD/MM/YYYY');
                        } catch (e) {
                            console.error('Date formatting error:', e);
                            return data || '';
                        }
                    }
                },
                { data: 'order_number', defaultContent: '' },
                { data: 'product_name', defaultContent: '' },
                { 
                    data: 'quantity_checked',
                    render: function(data) {
                        try {
                            return parseFloat(data).toFixed(2);
                        } catch (e) {
                            return data || '0.00';
                        }
                    }
                },
                { 
                    data: 'quantity_passed',
                    render: function(data) {
                        try {
                            return parseFloat(data).toFixed(2);
                        } catch (e) {
                            return data || '0.00';
                        }
                    }
                },
                { 
                    data: 'quantity_failed',
                    render: function(data) {
                        try {
                            return parseFloat(data).toFixed(2);
                        } catch (e) {
                            return data || '0.00';
                        }
                    }
                },
                { data: 'defect_type', defaultContent: '' },
                { 
                    data: 'status',
                    render: function(data) {
                        if (!data) return '';
                        
                        let badge = '';
                        switch(data) {
                            case 'passed':
                                badge = 'label-passed';
                                break;
                            case 'failed':
                                badge = 'label-failed';
                                break;
                            case 'partially_passed':
                                badge = 'label-partially-passed';
                                break;
                        }
                        return '<span class="label ' + badge + '">' + data.replace('_', ' ').toUpperCase() + '</span>';
                    },
                    defaultContent: ''
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        if (!row || !row.id) return '';
                        
                        return `
                            <div class="btn-group">
                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                    Action <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li><a href="#" onclick="editQC(${row.id})"><i class="fa fa-edit"></i> Edit</a></li>
                                    <li><a href="#" onclick="viewQC(${row.id})"><i class="fa fa-eye"></i> View Details</a></li>
                                    <li><a href="#" onclick="removeQC(${row.id})"><i class="fa fa-trash"></i> Remove</a></li>
                                </ul>
                            </div>`;
                    },
                    orderable: false
                }
            ],
            'pageLength': 10,
            'responsive': true,
            'dom': "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            'language': {
                'processing': 'Loading...',
                'emptyTable': 'No quality control entries found',
                'zeroRecords': 'No matching records found',
                'loadingRecords': 'Loading...',
                'info': 'Showing _START_ to _END_ of _TOTAL_ entries',
                'infoEmpty': 'Showing 0 to 0 of 0 entries',
                'infoFiltered': '(filtered from _MAX_ total entries)',
                'search': 'Search:',
                'paginate': {
                    'first': 'First',
                    'last': 'Last',
                    'next': 'Next',
                    'previous': 'Previous'
                }
            }
        });
        
        console.log('DataTable initialized successfully');
    } catch (e) {
        console.error('Error initializing DataTable:', e);
        
        // Show error in the table container
        $('.remove-messages').html(
            '<div class="alert alert-danger">' + 
                '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                'DataTable Error: ' + e.message + 
            '</div>'
        );
    }
    
    // Initialize Select2 for dropdowns
    try {
        // Direct method to initialize Select2
        if (typeof $.fn.select2 === 'function') {
            $('#productionOrderId').select2({
                placeholder: 'Select Production Order',
                allowClear: true,
                width: '100%'
            });
            
            $('#editProductionOrderId').select2({
                placeholder: 'Select Production Order',
                allowClear: true,
                width: '100%'
            });
            
            console.log('Select2 initialized successfully');
        } else {
            console.error('Select2 function not available - attempting fallback');
            
            // Fallback to direct CSS styling for dropdowns if Select2 isn't available
            $('#productionOrderId, #editProductionOrderId').css({
                'width': '100%',
                'padding': '6px 12px',
                'border': '1px solid #ccc',
                'border-radius': '4px'
            });
        }
    } catch (e) {
        console.error('Error initializing Select2:', e);
    }
    
    // Auto-calculate failed quantity
    $('#quantityChecked, #quantityPassed').on('input', function() {
        var checked = parseFloat($('#quantityChecked').val()) || 0;
        var passed = parseFloat($('#quantityPassed').val()) || 0;
        var failed = Math.max(0, checked - passed);
        $('#quantityFailed').val(failed.toFixed(2));
    });

    $('#editQuantityChecked, #editQuantityPassed').on('input', function() {
        var checked = parseFloat($('#editQuantityChecked').val()) || 0;
        var passed = parseFloat($('#editQuantityPassed').val()) || 0;
        var failed = Math.max(0, checked - passed);
        $('#editQuantityFailed').val(failed.toFixed(2));
    });

    // Form submission for creating QC entry
    $('#submitQCForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        var isValid = true;
        var $form = $(this);
        
        // Check required fields
        $form.find('[required]').each(function() {
            if(!$(this).val()) {
                isValid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
        
        if(!isValid) {
            toastr.error('Please fill in all required fields');
            return false;
        }
        
        // Validate quantities
        var checked = parseFloat($('#quantityChecked').val());
        var passed = parseFloat($('#quantityPassed').val());
        var failed = parseFloat($('#quantityFailed').val());
        
        if(passed + failed !== checked) {
            toastr.error('Sum of passed and failed quantities must equal checked quantity');
            return false;
        }
        
        // Show loading state
        $('#createQCBtn')
            .prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin"></i> Creating...');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Reset form and close modal
                    $('#submitQCForm')[0].reset();
                    $('#addQCModal').modal('hide');
                    
                    // Reset Select2 fields
                    $('#productionOrderId').val('').trigger('change');
                    
                    // Reload DataTable
                    $('#qcTable').DataTable().ajax.reload();
                    
                    // Show success notification
                    toastr.success('Quality control entry created successfully');
                } else {
                    toastr.error(response.messages);
                }
            },
            error: function(xhr, status, error) {
                toastr.error('An error occurred while creating the entry');
                console.error('Error:', error);
                showDebug('AJAX Error while creating entry: ' + error);
            },
            complete: function() {
                // Reset button state
                $('#createQCBtn')
                    .prop('disabled', false)
                    .html('Create Entry');
            }
        });
    });

    // Form submission for editing QC entry
    $('#editQCForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        var isValid = true;
        var $form = $(this);
        
        // Check required fields
        $form.find('[required]').each(function() {
            if(!$(this).val()) {
                isValid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
        
        if(!isValid) {
            toastr.error('Please fill in all required fields');
            return false;
        }
        
        // Validate quantities
        var checked = parseFloat($('#editQuantityChecked').val());
        var passed = parseFloat($('#editQuantityPassed').val());
        var failed = parseFloat($('#editQuantityFailed').val());
        
        if(passed + failed !== checked) {
            toastr.error('Sum of passed and failed quantities must equal checked quantity');
            return false;
        }
        
        // Show loading state
        $('#editQCBtn')
            .prop('disabled', true)
            .html('<i class="fa fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Close modal
                    $('#editQCModal').modal('hide');
                    
                    // Reload DataTable
                    $('#qcTable').DataTable().ajax.reload();
                    
                    // Show success notification
                    toastr.success('Quality control entry updated successfully');
                } else {
                    toastr.error(response.messages);
                }
            },
            error: function(xhr, status, error) {
                toastr.error('An error occurred while updating the entry');
                console.error('Error:', error);
                showDebug('AJAX Error while updating entry: ' + error);
            },
            complete: function() {
                // Reset button state
                $('#editQCBtn')
                    .prop('disabled', false)
                    .html('Save Changes');
            }
        });
    });
    
    // Add debug mode toggle
    $('<button>')
        .addClass('btn btn-default')
        .text('Toggle Debug')
        .css({
            position: 'fixed',
            bottom: '10px',
            right: '10px',
            zIndex: 9999
        })
        .click(function() {
            $('#debug-message').toggle();
        })
        .appendTo('body');
});

// Edit QC Entry
function editQC(id) {
    // Reset form
    $('#editQCForm')[0].reset();
    $('#edit-qc-messages').html('');
    
    // Show loading state
    $('#editQCModal').modal('show');
    $('#editQCModal .modal-body').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading...</p></div>');
    
    $.ajax({
        url: 'php_action/fetchSingleQC.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Populate form with data
                var data = response.data;
                $('#qcId').val(data.id);
                $('#editProductionOrderId').val(data.production_order_id).trigger('change');
                $('#editInspectionDate').val(data.inspection_date);
                $('#editQuantityChecked').val(parseFloat(data.quantity_checked).toFixed(2));
                $('#editQuantityPassed').val(parseFloat(data.quantity_passed).toFixed(2));
                $('#editQuantityFailed').val(parseFloat(data.quantity_failed).toFixed(2));
                $('#editDefectType').val(data.defect_type);
                $('#editNotes').val(data.notes);
                
                // Show the form
                $('#editQCModal .modal-body').html($('#editQCForm').parent().html());
                $('#editQCForm').show();
                
                // Reinitialize Select2
                $('#editQCModal #editProductionOrderId').select2({
                    placeholder: 'Select Production Order',
                    allowClear: true
                });
                
                // Re-bind the auto-calculate event
                $('#editQCModal #editQuantityChecked, #editQCModal #editQuantityPassed').on('input', function() {
                    var checked = parseFloat($('#editQCModal #editQuantityChecked').val()) || 0;
                    var passed = parseFloat($('#editQCModal #editQuantityPassed').val()) || 0;
                    var failed = Math.max(0, checked - passed);
                    $('#editQCModal #editQuantityFailed').val(failed.toFixed(2));
                });
            } else {
                toastr.error(response.messages);
                $('#editQCModal').modal('hide');
            }
        },
        error: function(xhr, status, error) {
            toastr.error('An error occurred while fetching the entry');
            console.error('Error:', error);
            showDebug('AJAX Error while fetching entry: ' + error);
            $('#editQCModal').modal('hide');
        }
    });
}

// View QC Details
function viewQC(id) {
    // Show loading state
    $('#viewQCModal').modal('show');
    $('#qcDetails').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading...</p></div>');
    
    $.ajax({
        url: 'php_action/fetchSingleQC.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                var data = response.data;
                var statusBadge = '';
                switch(data.status) {
                    case 'passed':
                        statusBadge = '<span class="label label-passed">PASSED</span>';
                        break;
                    case 'failed':
                        statusBadge = '<span class="label label-failed">FAILED</span>';
                        break;
                    case 'partially_passed':
                        statusBadge = '<span class="label label-partially-passed">PARTIALLY PASSED</span>';
                        break;
                }
                
                var html = `
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width:30%">Production Order</th>
                            <td>${data.order_number}</td>
                        </tr>
                        <tr>
                            <th>Product</th>
                            <td>${data.product_name}</td>
                        </tr>
                        <tr>
                            <th>Inspection Date</th>
                            <td>${moment(data.inspection_date).format('DD/MM/YYYY')}</td>
                        </tr>
                        <tr>
                            <th>Quantity Checked</th>
                            <td>${parseFloat(data.quantity_checked).toFixed(2)}</td>
                        </tr>
                        <tr>
                            <th>Quantity Passed</th>
                            <td>${parseFloat(data.quantity_passed).toFixed(2)}</td>
                        </tr>
                        <tr>
                            <th>Quantity Failed</th>
                            <td>${parseFloat(data.quantity_failed).toFixed(2)}</td>
                        </tr>
                        <tr>
                            <th>Pass Rate</th>
                            <td>${((data.quantity_passed / data.quantity_checked) * 100).toFixed(2)}%</td>
                        </tr>
                        <tr>
                            <th>Defect Type</th>
                            <td>${data.defect_type || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Notes</th>
                            <td>${data.notes || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>${statusBadge}</td>
                        </tr>
                    </table>
                </div>`;
                
                $('#qcDetails').html(html);
            } else {
                toastr.error(response.messages);
                $('#viewQCModal').modal('hide');
            }
        },
        error: function(xhr, status, error) {
            toastr.error('An error occurred while fetching the entry');
            console.error('Error:', error);
            showDebug('AJAX Error while viewing entry: ' + error);
            $('#viewQCModal').modal('hide');
        }
    });
}

// Remove QC Entry
function removeQC(id) {
    // Confirm deletion
    if(confirm('Are you sure you want to remove this quality control entry?')) {
        $.ajax({
            url: 'php_action/removeQC.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Reload DataTable
                    $('#qcTable').DataTable().ajax.reload();
                    
                    // Show success notification
                    toastr.success(response.messages);
                } else {
                    toastr.error(response.messages);
                }
            },
            error: function(xhr, status, error) {
                toastr.error('An error occurred while removing the entry');
                console.error('Error:', error);
                showDebug('AJAX Error while removing entry: ' + error);
            }
        });
    }
}

$(document).ready(function() {
    // Initialize Select2 with search
    $('.select2').select2({
        theme: 'bootstrap',
        width: '100%'
    });

    // Set max date for inspection date
    var today = new Date().toISOString().split('T')[0];
    $('#inspectionDate').attr('max', today);

    // Handle quantity calculations and validation
    function updateQuantities() {
        var checked = parseFloat($('#quantityChecked').val()) || 0;
        var passed = parseFloat($('#quantityPassed').val()) || 0;
        var failed = Math.max(0, (checked - passed).toFixed(2));
        
        // Update failed quantity
        $('#quantityFailed').val(failed);
        
        // Show/hide defect type based on failed quantity
        if (failed > 0) {
            $('#defectTypeGroup').slideDown();
            if (failed === checked) {
                $('#defectType').prop('required', true);
            }
        } else {
            $('#defectTypeGroup').slideUp();
            $('#defectType').prop('required', false);
        }
        
        // Validate quantities
        var $helpBlock = $('.quantity-validation');
        if (passed > checked) {
            $helpBlock.text('Passed quantity cannot exceed checked quantity').addClass('text-danger');
            return false;
        } else {
            $helpBlock.text('').removeClass('text-danger');
            return true;
        }
    }

    $('#quantityChecked, #quantityPassed').on('input', updateQuantities);

    // Form validation and submission
    $('#submitQCForm').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous messages
        $('#add-qc-messages').empty();
        
        // Basic validation
        if (!updateQuantities()) {
            return false;
        }
        
        // Show loading state
        var $submitBtn = $('#createQCBtn');
        $submitBtn.prop('disabled', true)
                 .html('<i class="fa fa-spinner fa-spin"></i> Creating...');
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $('#add-qc-messages').html(
                        '<div class="alert alert-success">' +
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                            '<strong><i class="fa fa-check"></i></strong> ' + response.messages +
                        '</div>'
                    );
                    
                    // Reset form
                    $('#submitQCForm')[0].reset();
                    $('.select2').val('').trigger('change');
                    
                    // Reload DataTable
                    $('#qcTable').DataTable().ajax.reload();
                    
                    // Close modal after delay
                    setTimeout(function() {
                        $('#addQCModal').modal('hide');
                    }, 1500);
                } else {
                    // Show error message
                    $('#add-qc-messages').html(
                        '<div class="alert alert-danger">' +
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                            '<strong><i class="fa fa-times"></i></strong> ' + response.messages +
                        '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                // Show error message
                $('#add-qc-messages').html(
                    '<div class="alert alert-danger">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="fa fa-times"></i></strong> An error occurred while creating the entry. Please try again.' +
                    '</div>'
                );
                console.error('Error:', error);
            },
            complete: function() {
                // Reset button state
                $submitBtn.prop('disabled', false)
                         .html('<i class="fa fa-plus"></i> Create Entry');
            }
        });
    });

    // Reset form when modal is closed
    $('#addQCModal').on('hidden.bs.modal', function() {
        $('#submitQCForm')[0].reset();
        $('.select2').val('').trigger('change');
        $('#add-qc-messages').empty();
        $('#defectTypeGroup').hide();
        $('.quantity-validation').empty();
        $('#createQCBtn').prop('disabled', false)
                        .html('<i class="fa fa-plus"></i> Create Entry');
    });

    // Override the Add QC Entry button click
    $('[data-target="#addQCModal"]').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Show development notice using SweetAlert2
        Swal.fire({
            title: 'Under Development',
            text: 'This new feature is under development. You will be notified when it\'s ready.',
            icon: 'info',
            confirmButtonText: 'Got it!',
            confirmButtonColor: '#3085d6',
            customClass: {
                container: 'custom-swal-container',
                popup: 'custom-swal-popup',
                title: 'custom-swal-title',
                content: 'custom-swal-content'
            }
        });
        
        return false;
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 