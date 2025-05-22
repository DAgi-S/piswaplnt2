<?php 
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Production Tracking</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-search"></i> Production Tracking
                    <div class="pull-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default" id="filterByRawMaterial">
                                Raw Materials
                            </button>
                            <button type="button" class="btn btn-default" id="filterByProduct">
                                Products
                            </button>
                            <button type="button" class="btn btn-default" id="filterByWarehouse">
                                By Warehouse
                            </button>
                            <button type="button" class="btn btn-default" id="showAllMovements">
                                All Movements
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                <div class="row">
                    <!-- Filters -->
                    <div class="col-md-12 mb-3">
                        <form id="filterForm" class="form-inline">
                            <div class="form-group mx-2">
                                <label class="mr-2">Date Range:</label>
                                <input type="date" class="form-control" id="startDate" name="start_date">
                                <input type="date" class="form-control" id="endDate" name="end_date">
                            </div>
                            <div class="form-group mx-2">
                                <label class="mr-2">Movement Type:</label>
                                <select class="form-control" id="movementType" name="movement_type">
                                    <option value="">All</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="production_in">Production In</option>
                                    <option value="production_out">Production Out</option>
                                </select>
                            </div>
                            <div class="form-group mx-2">
                                <label class="mr-2">Status:</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="">All</option>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </form>
                    </div>

                    <!-- Main Table -->
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table id="inventoryTrackingTable" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Item Type</th>
                                        <th>Item Name</th>
                                        <th>Source</th>
                                        <th>Destination</th>
                                        <th>Quantity</th>
                                        <th>Movement Type</th>
                                        <th>Reference</th>
                                        <th>Status</th>
                                        <th>Actions</th>
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

<!-- View Movement Details Modal -->
<div class="modal fade" id="viewMovementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Movement Details</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Basic Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Movement ID</th>
                                <td id="movementId"></td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td id="movementDate"></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td id="movementStatus"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Item Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Item Type</th>
                                <td id="itemType"></td>
                            </tr>
                            <tr>
                                <th>Item Name</th>
                                <td id="itemName"></td>
                            </tr>
                            <tr>
                                <th>Quantity</th>
                                <td id="quantity"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Source Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Source Type</th>
                                <td id="sourceType"></td>
                            </tr>
                            <tr>
                                <th>Source Name</th>
                                <td id="sourceName"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Destination Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Destination Type</th>
                                <td id="destinationType"></td>
                            </tr>
                            <tr>
                                <th>Destination Name</th>
                                <td id="destinationName"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <h5>Additional Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Reference Type</th>
                                <td id="referenceType"></td>
                            </tr>
                            <tr>
                                <th>Reference ID</th>
                                <td id="referenceId"></td>
                            </tr>
                            <tr>
                                <th>Notes</th>
                                <td id="notes"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
    .form-inline .form-group {
        margin-right: 10px;
    }
    .form-inline label {
        margin-right: 5px;
    }
    .table > tbody > tr > td {
        vertical-align: middle;
    }
    .label {
        display: inline-block;
        min-width: 80px;
        text-align: center;
    }
    .btn-group {
        margin-bottom: 15px;
    }
    .modal-lg {
        width: 90%;
        max-width: 1200px;
    }
    .table-bordered th {
        background-color: #f5f5f5;
        width: 40%;
    }
</style>

<!-- Include DataTables -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap.min.css"/>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<!-- Include Moment.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<!-- Custom JavaScript -->
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#inventoryTrackingTable').DataTable({
        'processing': true,
        'serverSide': true,
        'ajax': {
            'url': 'php_action/fetchInventoryMovements.php',
            'type': 'POST',
            'dataType': 'json',
            'data': function(d) {
                d.startDate = $('#startDate').val();
                d.endDate = $('#endDate').val();
                d.movementType = $('#movementType').val();
                d.status = $('#status').val();
                return d;
            },
            'error': function(xhr, error, thrown) {
                console.error('Ajax Error:', error);
                console.error('Server Response:', xhr.responseText);
                alert('Error loading data. Please check the console for details.');
            }
        },
        'columns': [
            { 
                'data': 'created_at',
                'render': function(data) {
                    return moment(data).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            { 
                'data': 'item_type',
                'render': function(data) {
                    return data ? data.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A';
                }
            },
            { 
                'data': 'item_name',
                'render': function(data) {
                    return data || 'N/A';
                }
            },
            { 
                'data': null,
                'render': function(data) {
                    var sourceType = data.source_type ? data.source_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A';
                    var sourceName = data.source_name || 'Main Warehouse';
                    
                    if (sourceType.toLowerCase() === 'warehouse' && !data.source_name) {
                        sourceName = 'Main Warehouse';
                    }
                    
                    return sourceType + ': ' + sourceName;
                }
            },
            { 
                'data': null,
                'render': function(data) {
                    var destType = data.destination_type ? data.destination_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A';
                    var destName = data.destination_name || 'Main Warehouse';
                    
                    if (destType.toLowerCase() === 'warehouse' && !data.destination_name) {
                        destName = 'Main Warehouse';
                    }
                    
                    return destType + ': ' + destName;
                }
            },
            { 
                'data': 'quantity',
                'render': function(data, type, row) {
                    if (type === 'display') {
                        return parseFloat(data).toFixed(2);
                    }
                    return data;
                }
            },
            { 
                'data': 'movement_type',
                'render': function(data) {
                    var label = data.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    var labelClass = '';
                    
                    // Determine label class based on movement type
                    if (data === 'production_in' || data === 'in') {
                        labelClass = 'label-success';
                    } else if (data === 'production_out' || data === 'out') {
                        labelClass = 'label-danger';
                    } else {
                        labelClass = 'label-default';
                    }
                    
                    return '<span class="label ' + labelClass + '">' + label + '</span>';
                }
            },
            { 
                'data': null,
                'render': function(data) {
                    var refType = data.reference_type ? data.reference_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A';
                    var refId = data.reference_id || 'N/A';
                    return refType + ' #' + refId;
                }
            },
            { 
                'data': 'status',
                'render': function(data) {
                    var labelClass = {
                        'pending': 'label-warning',
                        'completed': 'label-success',
                        'cancelled': 'label-danger'
                    };
                    return '<span class="label ' + (labelClass[data] || 'label-default') + '">' + 
                           (data ? data.charAt(0).toUpperCase() + data.slice(1) : 'N/A') + '</span>';
                }
            },
            {
                'data': null,
                'orderable': false,
                'searchable': false,
                'render': function(data) {
                    if (!data.id) return '';
                    var buttons = '<div class="btn-group">';
                    buttons += '<button class="btn btn-default btn-sm view-btn" data-id="' + data.id + '" title="View Details"><i class="fa fa-eye"></i></button>';
                    if(data.status === 'pending') {
                        buttons += '<button class="btn btn-default btn-sm complete-btn" data-id="' + data.id + '" title="Complete"><i class="fa fa-check"></i></button>';
                        buttons += '<button class="btn btn-default btn-sm cancel-btn" data-id="' + data.id + '" title="Cancel"><i class="fa fa-times"></i></button>';
                    }
                    buttons += '</div>';
                    return buttons;
                }
            }
        ],
        'language': {
            'processing': '<div class="dt-loader"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span></div>',
            'emptyTable': 'No movements found',
            'zeroRecords': 'No matching movements found',
            'info': 'Showing _START_ to _END_ of _TOTAL_ entries',
            'infoEmpty': 'Showing 0 to 0 of 0 entries',
            'infoFiltered': '(filtered from _MAX_ total entries)',
            'search': 'Search:',
            'paginate': {
                'first': '<i class="fa fa-angle-double-left"></i>',
                'last': '<i class="fa fa-angle-double-right"></i>',
                'next': '<i class="fa fa-angle-right"></i>',
                'previous': '<i class="fa fa-angle-left"></i>'
            }
        },
        'dom': "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        'lengthMenu': [[10, 25, 50, -1], [10, 25, 50, "All"]],
        'pageLength': 10,
        'pagingType': 'full_numbers',
        'responsive': true,
        'stateSave': false,
        'processing': true,
        'deferRender': true,
        'order': [[0, 'desc']]
    });

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Filter buttons
    $('#filterByRawMaterial').click(function() {
        table.column(1).search('raw_material').draw();
    });

    $('#filterByProduct').click(function() {
        table.column(1).search('finished_good|product').draw();
    });

    $('#filterByWarehouse').click(function() {
        table.column(3).search('warehouse').draw();
    });

    $('#showAllMovements').click(function() {
        table.search('').columns().search('').draw();
    });

    // View movement details
    $('#inventoryTrackingTable').on('click', '.view-btn', function() {
        var id = $(this).data('id');
        $.ajax({
            url: 'php_action/fetchSingleMovement.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    var data = response.data;
                    $('#movementId').text(data.id || 'N/A');
                    $('#movementDate').text(moment(data.created_at).format('YYYY-MM-DD HH:mm:ss'));
                    $('#movementStatus').text(data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : 'N/A');
                    $('#itemType').text(data.item_type ? data.item_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A');
                    $('#itemName').text(data.item_name || 'N/A');
                    $('#quantity').text(data.quantity ? parseFloat(data.quantity).toFixed(2) : 'N/A');
                    
                    var sourceType = data.source_type ? data.source_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A';
                    var sourceName = data.source_name || 'Main Warehouse';
                    if (sourceType.toLowerCase() === 'warehouse' && !data.source_name) {
                        sourceName = 'Main Warehouse';
                    }
                    $('#sourceType').text(sourceType);
                    $('#sourceName').text(sourceName);
                    
                    var destType = data.destination_type ? data.destination_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A';
                    var destName = data.destination_name || 'Main Warehouse';
                    if (destType.toLowerCase() === 'warehouse' && !data.destination_name) {
                        destName = 'Main Warehouse';
                    }
                    $('#destinationType').text(destType);
                    $('#destinationName').text(destName);
                    
                    $('#referenceType').text(data.reference_type ? data.reference_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'N/A');
                    $('#referenceId').text(data.reference_id || 'N/A');
                    $('#notes').text(data.notes || 'N/A');
                    $('#viewMovementModal').modal('show');
                }
            }
        });
    });

    // Complete movement
    $('#inventoryTrackingTable').on('click', '.complete-btn', function() {
        var id = $(this).data('id');
        if(confirm('Are you sure you want to complete this movement?')) {
            $.ajax({
                url: 'php_action/updateMovementStatus.php',
                type: 'POST',
                data: { 
                    id: id,
                    status: 'completed'
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        table.ajax.reload();
                    }
                }
            });
        }
    });

    // Cancel movement
    $('#inventoryTrackingTable').on('click', '.cancel-btn', function() {
        var id = $(this).data('id');
        if(confirm('Are you sure you want to cancel this movement?')) {
            $.ajax({
                url: 'php_action/updateMovementStatus.php',
                type: 'POST',
                data: { 
                    id: id,
                    status: 'cancelled'
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        table.ajax.reload();
                    }
                }
            });
        }
    });

    // Reset filters
    $('#resetFilters').click(function() {
        $('#startDate, #endDate').val('');
        $('#movementType, #status').val('all');
        table.search('').columns().search('').draw();
    });

    // Error handling for AJAX requests
    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        console.error('AJAX error:', thrownError);
        alert('An error occurred while processing your request. Please try again.');
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 