$(document).ready(function() {
    // Initialize DataTable
    var rawMaterialsTable = $('#rawMaterialsTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchRawMaterials.php',
            'type': 'GET',
            'dataSrc': function(response) {
                // Log the raw response for debugging
                console.log('Server Response:', response);
                
                if (response.error) {
                    // Show error message
                    toastr.error(response.message || 'Error loading data');
                    console.error('Server Error:', response);
                    return [];
                }
                
                if (!response.data || !Array.isArray(response.data)) {
                    toastr.error('Invalid data received from server');
                    console.error('Invalid response format:', response);
                    return [];
                }
                
                return response.data;
            },
            'error': function(xhr, error, thrown) {
                console.error('DataTables AJAX error:', error);
                console.error('Server response:', xhr.responseText);
                console.error('Error details:', thrown);
                toastr.error('Failed to load data. Please check console for details.');
            }
        },
        'order': [[0, 'asc']],
        'processing': true,
        'serverSide': false,
        'dom': "<'row'<'col-sm-6'B><'col-sm-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        'buttons': [
            {
                extend: 'copy',
                text: '<i class="fa fa-copy"></i> Copy',
                className: 'btn btn-default',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7]
                }
            },
            {
                extend: 'csv',
                text: '<i class="fa fa-file-text-o"></i> CSV',
                className: 'btn btn-default',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7]
                }
            },
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel-o"></i> Excel',
                className: 'btn btn-default',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fa fa-file-pdf-o"></i> PDF',
                className: 'btn btn-default',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7]
                }
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print"></i> Print',
                className: 'btn btn-default',
                exportOptions: {
                    columns: [0,1,2,3,4,5,6,7]
                }
            }
        ],
        'pageLength': 10,
        'lengthMenu': [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        'responsive': true,
        'columns': [
            { data: 'material_code' },
            { data: 'name' },
            { data: 'category_name' },
            { 
                data: 'unit',
                render: function(data) {
                    return data ? data.toUpperCase() : '';
                }
            },
            { 
                data: 'current_stock',
                render: function(data, type, row) {
                    if (type === 'display') {
                        let stockClass = parseFloat(data) <= parseFloat(row.min_stock_level) ? 'text-danger' : 'text-success';
                        return '<span class="' + stockClass + '">' + (parseFloat(data) || 0).toFixed(2) + '</span>';
                    }
                    return data;
                }
            },
            { 
                data: 'min_stock_level',
                render: function(data) {
                    return (parseFloat(data) || 0).toFixed(2);
                }
            },
            { 
                data: 'cost_per_unit',
                render: function(data) {
                    return (parseFloat(data) || 0).toFixed(2);
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    let badge = data === 'active' ? 'success' : 'warning';
                    return '<span class="label label-' + badge + '">' + (data || '').toUpperCase() + '</span>';
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle action-btn" data-toggle="dropdown">
                                <i class="fa fa-cog"></i> Action <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a href="#" onclick="editRawMaterial('${row.id}'); return false;">
                                    <i class="fa fa-edit"></i> Edit
                                </a></li>
                                <li><a href="#" onclick="adjustStock('${row.id}', '${row.name}', ${row.current_stock}); return false;">
                                    <i class="fa fa-exchange"></i> Adjust Stock
                                </a></li>
                                <li><a href="#" onclick="viewStockHistory('${row.id}'); return false;">
                                    <i class="fa fa-history"></i> View History
                                </a></li>
                                <li class="divider"></li>
                                ${row.status === 'active' 
                                    ? '<li><a href="#" onclick="changeStatus(\'' + row.id + '\', \'inactive\'); return false;"><i class="fa fa-ban"></i> Deactivate</a></li>'
                                    : '<li><a href="#" onclick="changeStatus(\'' + row.id + '\', \'active\'); return false;"><i class="fa fa-check"></i> Activate</a></li>'
                                }
                            </ul>
                        </div>`;
                }
            }
        ],
        'language': {
            'emptyTable': 'No raw materials found',
            'zeroRecords': 'No matching raw materials found',
            'loadingRecords': 'Loading...',
            'processing': 'Processing...',
            'search': 'Search:',
            'info': 'Showing _START_ to _END_ of _TOTAL_ entries',
            'infoEmpty': 'Showing 0 to 0 of 0 entries',
            'infoFiltered': '(filtered from _MAX_ total entries)',
            'paginate': {
                'first': 'First',
                'last': 'Last',
                'next': 'Next',
                'previous': 'Previous'
            }
        }
    });

    // Initialize Select2 for dropdowns
    $('.select2').select2();
    
    // Initialize warehouse dropdown with Select2
    $('#warehouseId').select2({
        placeholder: "Select Warehouse",
        allowClear: true
    });

    // Refresh table function
    function refreshTable() {
        rawMaterialsTable.ajax.reload(null, false);
    }

    // Add refresh button
    $('.dt-buttons').append(
        '<button class="btn btn-default" onclick="refreshTable()">' +
        '<i class="fa fa-refresh"></i> Refresh</button>'
    );

    // Add Category Quick Add Button
    $('#categoryId').parent().append(
        '<button type="button" class="btn btn-info btn-sm" style="margin-left:10px;" ' +
        'onclick="showAddCategoryModal()"><i class="fa fa-plus"></i> Quick Add</button>'
    );

    // Global function to force cleanup modals
    function forceCleanupModals() {
        $('.modal').modal('hide');
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
        $('.modal').removeClass('in');
    }

    // Submit new raw material form
    $('#submitRawMaterialForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate warehouse selection
        if (!$('#warehouseId').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please select a warehouse'
            });
            return false;
        }
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('#createRawMaterialBtn').button('loading');
            },
            success: function(response) {
                if(response.success) {
                    // Reset the form
                    $('#submitRawMaterialForm')[0].reset();
                    
                    // Hide modal
                    $('#addRawMaterialModal').modal('hide');
                    
                    // Reload table
                    rawMaterialsTable.ajax.reload();
                    
                    // Show simple success popup
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Raw material created successfully.',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        }
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while processing your request'
                });
                console.error('Update error:', error);
            },
            complete: function() {
                $('#createRawMaterialBtn').button('reset');
            }
        });
    });

    // Clear form and errors when modal is closed
    $('#addRawMaterialModal').on('hidden.bs.modal', function () {
        $('#submitRawMaterialForm')[0].reset();
        $('#add-raw-material-messages').html('');
        $('.form-group').removeClass('has-error');
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
    });

    // Clear form and errors when modal is opened
    $('#addRawMaterialModal').on('show.bs.modal', function () {
        $('#submitRawMaterialForm')[0].reset();
        $('#add-raw-material-messages').html('');
        $('.form-group').removeClass('has-error');
    });

    // Edit raw material
    function editRawMaterial(id) {
        // Clear previous error messages
        $('#edit-raw-material-messages').html('');
        
        $.ajax({
            url: 'php_action/fetchRawMaterialById.php',
            type: 'POST',
            data: { materialId: id },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Populate form fields
                    $('#editRawMaterialId').val(response.id);
                    $('#editMaterialCode').val(response.material_code);
                    $('#editMaterialName').val(response.name);
                    $('#editCategoryId').val(response.category_id);
                    $('#editUnit').val(response.unit);
                    $('#editMinStockLevel').val(response.min_stock_level);
                    $('#editCostPerUnit').val(response.cost_per_unit);
                    $('#editDescription').val(response.description);
                    
                    // Trigger change for select2 dropdowns
                    $('#editCategoryId, #editUnit').trigger('change');
                    
                    $('#editRawMaterialModal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages || 'Failed to fetch material details'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to fetch material details'
                });
            }
        });
    }
    window.editRawMaterial = editRawMaterial;

    // Submit edit raw material form
    $('#editRawMaterialForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate required fields
        var isValid = true;
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                isValid = false;
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (!isValid) {
            toastr.error('Please fill in all required fields');
            return false;
        }
        
        // Clear previous messages
        $('#edit-raw-material-messages').html('');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                // Disable submit button and show loading state
                $('#editRawMaterialForm button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                if(response.success) {
                    // Hide modal
                    $('#editRawMaterialModal').modal('hide');
                    
                    // Reload table
                    $('#rawMaterialsTable').DataTable().ajax.reload(null, false);
                    
                    // Show success message
                    toastr.success('Raw material updated successfully!');
                    
                    // Reset form
                    $('#editRawMaterialForm')[0].reset();
                } else {
                    // Show error message
                    toastr.error(response.messages || 'Failed to update raw material');
                    
                    // Display validation errors if any
                    if (response.messages) {
                        $('#edit-raw-material-messages').html(
                            '<div class="alert alert-danger">' + response.messages + '</div>'
                        );
                    }
                }
            },
            error: function(xhr, status, error) {
                toastr.error('An error occurred while processing your request');
                console.error('Update error:', error);
            },
            complete: function() {
                // Re-enable submit button
                $('#editRawMaterialForm button[type="submit"]').prop('disabled', false);
            }
        });
    });

    // Adjust stock
    function adjustStock(id, name, currentStock) {
        // Reset form and clear messages
        $('#adjustStockForm')[0].reset();
        $('#adjust-stock-messages').html('');
        
        // Set values
        $('#adjustMaterialId').val(id);
        $('#adjustMaterialName').text(name);
        $('#adjustCurrentStock').text(parseFloat(currentStock).toFixed(2));
        
        // Get current warehouse for this material
        $.ajax({
            url: 'php_action/fetchMaterialWarehouse.php',
            type: 'POST',
            data: { materialId: id },
            dataType: 'json',
            success: function(response) {
                if(response.success && response.warehouse_id) {
                    $('#adjustWarehouseId').val(response.warehouse_id).trigger('change');
                }
                // Show modal after getting warehouse info
                $('#adjustStockModal').modal('show');
            },
            error: function() {
                // Show modal even if warehouse fetch fails
                $('#adjustStockModal').modal('show');
            }
        });
    }
    window.adjustStock = adjustStock;

    // Submit stock adjustment form
    $('#adjustStockForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!$('#adjustWarehouseId').val()) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please select a warehouse'
            });
            return false;
        }
        
        // Clear previous messages
        $('#adjust-stock-messages').html('');
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            beforeSend: function() {
                $('#adjustStockForm button[type="submit"]').prop('disabled', true);
            },
            success: function(response) {
                if(response.success) {
                    // Hide modal
                    $('#adjustStockModal').modal('hide');
                    
                    // Reload table
                    $('#rawMaterialsTable').DataTable().ajax.reload(null, false);
                    
                    // Show success message
                    toastr.success('Stock adjusted successfully!');
                    
                    // Reset form
                    $('#adjustStockForm')[0].reset();
                } else {
                    // Show error message
                    toastr.error(response.messages || 'Failed to adjust stock');
                    
                    // Display validation errors if any
                    if (response.messages) {
                        $('#adjust-stock-messages').html(
                            '<div class="alert alert-danger">' + response.messages + '</div>'
                        );
                    }
                }
            },
            error: function(xhr, status, error) {
                toastr.error('An error occurred while processing your request');
                console.error('Adjustment error:', error);
            },
            complete: function() {
                $('#adjustStockForm button[type="submit"]').prop('disabled', false);
            }
        });
    });

    // View stock history
    function viewStockHistory(id) {
        if ($.fn.DataTable.isDataTable('#stockHistoryTable')) {
            $('#stockHistoryTable').DataTable().destroy();
        }

        // Initialize DataTable for history
        $('#stockHistoryTable').DataTable({
            'ajax': {
                'url': 'php_action/fetchRawMaterialHistory.php',
                'type': 'POST',
                'data': { materialId: id },
                'error': function(xhr, error, thrown) {
                    console.error('DataTables error:', error);
                    toastr.error('Error loading movement history');
                }
            },
            'order': [[0, 'desc']], // Order by date desc
            'columns': [
                { 
                    data: 'created_at',
                    className: 'col-date',
                    render: function(data) {
                        return moment(data).format('YYYY-MM-DD HH:mm:ss');
                    }
                },
                { 
                    data: 'movement_type',
                    className: 'col-type text-center',
                    render: function(data) {
                        let badge = data === 'IN' ? 'success' : (data === 'OUT' ? 'danger' : 'warning');
                        return '<span class="label label-' + badge + '">' + data + '</span>';
                    }
                },
                { 
                    data: 'quantity',
                    className: 'col-quantity text-right'
                },
                { 
                    data: 'reference_type',
                    className: 'col-reference'
                },
                { 
                    data: 'notes',
                    className: 'col-notes'
                },
                { 
                    data: 'created_by_name',
                    className: 'col-by'
                }
            ],
            'responsive': true,
            'pageLength': 10,
            'lengthMenu': [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
            'dom': "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            'language': {
                'emptyTable': 'No movement history found',
                'zeroRecords': 'No matching movements found',
                'loadingRecords': 'Loading...',
                'processing': 'Processing...',
                'search': 'Search:',
                'lengthMenu': '_MENU_ records per page',
                'info': 'Showing _START_ to _END_ of _TOTAL_ entries',
                'infoEmpty': 'Showing 0 to 0 of 0 entries',
                'infoFiltered': '(filtered from _MAX_ total entries)',
                'paginate': {
                    'first': 'First',
                    'last': 'Last',
                    'next': 'Next',
                    'previous': 'Previous'
                }
            },
            'drawCallback': function() {
                // Adjust modal height after table is drawn
                $('#viewStockHistoryModal .modal-body').css('max-height', $(window).height() * 0.6);
            }
        });

        $('#viewStockHistoryModal').modal('show');
    }
    window.viewStockHistory = viewStockHistory;

    // Change material status
    function changeStatus(id, newStatus) {
        $.ajax({
            url: 'php_action/changeRawMaterialStatus.php',
            type: 'POST',
            data: {
                materialId: id,
                status: newStatus
            },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    rawMaterialsTable.ajax.reload();
                    toastr.success('Status changed successfully!');
                } else {
                    toastr.error(response.messages, 'Error!');
                }
            },
            error: function() {
                toastr.error('An error occurred while changing status', 'Error!');
            }
        });
    }
    window.changeStatus = changeStatus;

    // Clear form on modal close
    $('#addRawMaterialModal').on('hidden.bs.modal', function() {
        $('#submitRawMaterialForm')[0].reset();
        $('#categoryId, #unit').val('').trigger('change');
    });

    $('#editRawMaterialModal').on('hidden.bs.modal', function() {
        $('#editRawMaterialForm')[0].reset();
        $('#editCategoryId, #editUnit').val('').trigger('change');
    });

    $('#adjustStockModal').on('hidden.bs.modal', function() {
        $('#adjustStockForm')[0].reset();
    });
});

// Function to show add category modal
function showAddCategoryModal() {
    $('#quickAddCategoryModal').modal('show');
}

// Function to submit quick add category
function submitQuickAddCategory() {
    var categoryName = $('#quickCategoryName').val();
    var categoryDesc = $('#quickCategoryDescription').val();
    
    if(!categoryName) {
        toastr.warning('Please enter category name', 'Warning!');
        return;
    }
    
    $.ajax({
        url: 'php_action/createCategory.php',
        type: 'POST',
        data: {
            name: categoryName,
            description: categoryDesc
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Add new option to category select
                var newOption = new Option(categoryName, response.categoryId, true, true);
                $('#categoryId').append(newOption).trigger('change');
                
                // Close modal and reset form
                $('#quickAddCategoryModal').modal('hide');
                $('#quickAddCategoryForm')[0].reset();
                
                toastr.success('Category added successfully!');
            } else {
                toastr.error(response.messages, 'Error!');
            }
        },
        error: function() {
            toastr.error('An error occurred while adding category', 'Error!');
        }
    });
}

// Function to show add supplier modal
function showAddSupplierModal() {
    $('#quickAddSupplierModal').modal('show');
}

// Function to submit quick add supplier
function submitQuickAddSupplier() {
    var supplierName = $('#quickSupplierName').val();
    
    if(!supplierName) {
        toastr.warning('Please enter supplier name', 'Warning!');
        return;
    }
    
    $.ajax({
        url: 'php_action/createSupplier.php',
        type: 'POST',
        data: $('#quickAddSupplierForm').serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#quickAddSupplierModal').modal('hide');
                $('#quickAddSupplierForm')[0].reset();
                toastr.success('Supplier added successfully!');
            } else {
                toastr.error(response.messages, 'Error!');
            }
        },
        error: function() {
            toastr.error('An error occurred while adding supplier', 'Error!');
        }
    });
} 