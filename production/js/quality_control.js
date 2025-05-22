$(document).ready(function() {
    // Initialize DataTable
    var qcTable = $('#qcTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchQC.php',
            'type': 'POST',
            'error': function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                toastr.error('Error loading quality control entries');
            }
        },
        'order': [[0, 'desc']],
        'columns': [
            { 
                data: 'inspection_date',
                render: function(data) {
                    return moment(data).format('DD/MM/YYYY');
                }
            },
            { data: 'order_number' },
            { data: 'product_name' },
            { 
                data: 'quantity_checked',
                render: function(data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            { 
                data: 'quantity_passed',
                render: function(data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            { 
                data: 'quantity_failed',
                render: function(data) {
                    return parseFloat(data).toFixed(2);
                }
            },
            { data: 'defect_type' },
            { 
                data: 'status',
                render: function(data) {
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
                }
            },
            {
                data: null,
                render: function(data, type, row) {
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
                }
            }
        ],
        'pageLength': 10,
        'responsive': true,
        'dom': "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
               "<'row'<'col-sm-12'tr>>" +
               "<'row'<'col-sm-5'i><'col-sm-7'p>>"
    });

    // Initialize Select2 for dropdowns
    $('#productionOrderId, #editProductionOrderId').select2({
        placeholder: 'Select Production Order',
        allowClear: true
    });

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
                    qcTable.ajax.reload();
                    
                    // Show success notification
                    toastr.success('Quality control entry created successfully');
                } else {
                    toastr.error(response.messages);
                }
            },
            error: function(xhr, status, error) {
                toastr.error('An error occurred while creating the entry');
                console.error('Error:', error);
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
                    qcTable.ajax.reload();
                    
                    // Show success notification
                    toastr.success('Quality control entry updated successfully');
                } else {
                    toastr.error(response.messages);
                }
            },
            error: function(xhr, status, error) {
                toastr.error('An error occurred while updating the entry');
                console.error('Error:', error);
            },
            complete: function() {
                // Reset button state
                $('#editQCBtn')
                    .prop('disabled', false)
                    .html('Save Changes');
            }
        });
    });
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
            }
        });
    }
} 