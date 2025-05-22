// Global variables
var manageWarehouseTable;
var stockTable = null;

// Wait for DOM and scripts to load
document.addEventListener('DOMContentLoaded', function() {
    // Get permissions from PHP
    var permissions = {
        view: typeof userPermissions !== 'undefined' ? (userPermissions.view || userPermissions.legacy_view) : false,
        manage: typeof userPermissions !== 'undefined' ? (userPermissions.manage || userPermissions.legacy_manage) : false
    };

    // Initialize DataTable with optimized settings
    manageWarehouseTable = $("#warehousesTable").DataTable({
        'processing': true,
        'serverSide': false,
        'ajax': {
            'url': 'php_action/fetchWarehouses.php',
            'type': 'POST',
            'error': function(xhr, error, thrown) {
                console.error('DataTable Error:', error);
                alert('Error loading warehouse data. Please try again.');
            }
        },
        'columns': [
            { 'data': 'code' },
            { 'data': 'name' },
            { 
                'data': 'type',
                'render': function(data) {
                    var badge = '';
                    switch(data) {
                        case 'raw_material':
                            badge = '<span class="stock-badge stock-badge-raw">Raw Material</span>';
                            break;
                        case 'finished_good':
                            badge = '<span class="stock-badge stock-badge-finished">Finished Good</span>';
                            break;
                        case 'both':
                            badge = '<span class="stock-badge stock-badge-both">Both</span>';
                            break;
                        default:
                            badge = '<span class="stock-badge">' + data + '</span>';
                    }
                    return badge;
                }
            },
            { 
                'data': 'location',
                'render': function(data) {
                    return data || '-';
                }
            },
            {
                'data': 'status',
                'render': function(data) {
                    return data === 'active' ? 
                        '<span class="label label-success">Active</span>' : 
                        '<span class="label label-danger">Inactive</span>';
                }
            },
            {
                'data': 'stock_count',
                'render': function(data, type, row) {
                    if (permissions.view) {
                        return '<a href="warehouses_stock.php?id=' + row.id + '" class="btn btn-info btn-sm">' +
                               '<i class="fa fa-list"></i> View Stock (' + (data || 0) + ')</a>';
                    }
                    return (data || 0) + ' items';
                }
            },
            {
                'data': null,
                'visible': permissions.manage,
                'render': function(data, type, row) {
                    if (!permissions.manage) return '';
                    
                    var buttons = '<div class="btn-group">';
                    buttons += '<button class="btn btn-default btn-sm editBtn" data-id="' + row.id + 
                              '"><i class="fa fa-edit"></i></button>';
                    
                    if(row.status === 'active') {
                        buttons += '<button class="btn btn-danger btn-sm changeStatusBtn" data-id="' + row.id + 
                                 '" data-status="inactive"><i class="fa fa-times"></i></button>';
                    } else {
                        buttons += '<button class="btn btn-success btn-sm changeStatusBtn" data-id="' + row.id + 
                                 '" data-status="active"><i class="fa fa-check"></i></button>';
                    }
                    buttons += '</div>';
                    return buttons;
                }
            }
        ],
        'order': [[1, 'asc']],
        'pageLength': 25,
        'deferRender': true,
        'responsive': true,
        'dom': '<"row"<"col-sm-6"B><"col-sm-6"f>>rt<"row"<"col-sm-6"i><"col-sm-6"p>>',
        'buttons': [
            { extend: 'copy', className: 'btn-sm' },
            { extend: 'csv', className: 'btn-sm' },
            { extend: 'excel', className: 'btn-sm' },
            { extend: 'pdf', className: 'btn-sm' },
            { extend: 'print', className: 'btn-sm' }
        ],
        'language': {
            'search': '_INPUT_',
            'searchPlaceholder': 'Search...',
            'emptyTable': 'No warehouses found',
            'zeroRecords': 'No matching warehouses found'
        }
    });

    // Only bind event handlers if user has manage permission
    if (permissions.manage) {
        // Event delegation for better performance
        $('#warehousesTable').on('click', '.editBtn, .changeStatusBtn', handleButtonClick);
        $('#addWarehouseForm').on('submit', handleAddWarehouse);
        $('#editWarehouseForm').on('submit', handleEditWarehouse);
        $('.modal').on('hidden.bs.modal', handleModalClose);
    }
});

// Event handlers
function handleButtonClick(e) {
    var $btn = $(e.target).closest('button');
    if ($btn.hasClass('editBtn')) {
        handleEdit($btn.data('id'));
    } else if ($btn.hasClass('changeStatusBtn')) {
        handleStatusChange($btn.data('id'), $btn.data('status'));
    }
}

function handleEdit(warehouseId) {
    $.ajax({
        url: 'php_action/fetchSingleWarehouse.php',
        type: 'POST',
        data: { id: warehouseId },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#editWarehouseId').val(response.data.id);
                $('#editCode').val(response.data.code);
                $('#editName').val(response.data.name);
                $('#editType').val(response.data.type);
                $('#editLocation').val(response.data.location);
                $('#editDescription').val(response.data.description);
                $('#editWarehouseModal').modal('show');
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.messages
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error fetching warehouse data. Please try again.'
            });
        }
    });
}

function handleStatusChange(warehouseId, newStatus) {
    Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to " + (newStatus === 'active' ? 'activate' : 'deactivate') + " this warehouse?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) {
            updateWarehouseStatus(warehouseId, newStatus);
        }
    });
}

function updateWarehouseStatus(warehouseId, newStatus) {
    Swal.fire({
        title: 'Processing...',
        text: 'Please wait while we update the status.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'php_action/changeWarehouseStatus.php',
        type: 'POST',
        data: {
            id: warehouseId,
            status: newStatus
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                manageWarehouseTable.ajax.reload(null, false);
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.messages
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.messages || 'Failed to update warehouse status'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while updating the status. Please try again.'
            });
        }
    });
}

function handleAddWarehouse(e) {
    e.preventDefault();
    var $form = $(this);
    
    $.ajax({
        url: $form.attr('action'),
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#addWarehouseModal').modal('hide');
                $form[0].reset();
                manageWarehouseTable.ajax.reload();
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Warehouse added successfully'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.messages
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error adding warehouse. Please try again.'
            });
        }
    });
}

function handleEditWarehouse(e) {
    e.preventDefault();
    var $form = $(this);
    
    $.ajax({
        url: $form.attr('action'),
        type: 'POST',
        data: $form.serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#editWarehouseModal').modal('hide');
                manageWarehouseTable.ajax.reload();
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: 'Warehouse updated successfully'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.messages
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error updating warehouse. Please try again.'
            });
        }
    });
}

function handleModalClose() {
    $(this).find('form')[0].reset();
    $('.alert').remove();
}

// Form validation function
function validateStockForm() {
    var quantity = $('#editQuantity').val();
    
    if (!quantity || isNaN(quantity) || parseFloat(quantity) < 0) {
        toastr.error('Please enter a valid quantity');
        return false;
    }
    
    return true;
}

// Function to edit stock
function editStock(stockId) {
    if(!stockId) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Invalid stock ID'
        });
        return;
    }

    // Reset form
    $('#editStockForm')[0].reset();
    
    // Set stock ID
    $('#editStockId').val(stockId);
    
    // Show modal
    $('#editStockModal').modal('show');
}

// Handle edit stock form submission
$('#editStockForm').on('submit', function(e) {
    e.preventDefault();
    
    if(!validateStockForm()) {
        return;
    }
    
    $.ajax({
        url: 'php_action/updateStock.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#editStockModal').modal('hide');
                $('#warehouseStockTable').DataTable().ajax.reload(null, false);
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.messages
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.messages
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to update stock quantity'
            });
        }
    });
});

// Function to edit warehouse
function editWarehouse(id) {
    $.ajax({
        url: 'php_action/fetchSingleWarehouse.php',
        type: 'POST',
        data: {id: id},
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#editWarehouseId').val(response.data.id);
                $('#editCode').val(response.data.code);
                $('#editName').val(response.data.name);
                $('#editType').val(response.data.type);
                $('#editLocation').val(response.data.location);
                $('#editDescription').val(response.data.description);
                $('#editWarehouseModal').modal('show');
            } else {
                toastr.error(response.message || 'Error fetching warehouse data');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            toastr.error('Error fetching warehouse data. Please try again.');
        }
    });
}

// Function to change warehouse status
function changeStatus(id, status) {
    if(confirm('Are you sure you want to change the status of this warehouse?')) {
        $.ajax({
            url: 'php_action/changeWarehouseStatus.php',
            type: 'POST',
            data: {
                id: id,
                status: status
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    manageWarehouseTable.ajax.reload();
                    toastr.success('Warehouse status updated successfully');
                } else {
                    toastr.error(response.message || 'Error updating warehouse status');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                toastr.error('Error updating warehouse status. Please try again.');
            }
        });
    }
} 