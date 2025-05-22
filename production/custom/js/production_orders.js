$(document).ready(function() {
    // Initialize DataTable for production orders
    var productionOrdersTable = $('#productionOrdersTable').DataTable({
        "ajax": {
            "url": "php_action/fetchProductionOrders.php",
            "type": "POST",
            "dataSrc": function(json) {
                if(json.error) {
                    console.error('DataTable error:', json.error);
                    return [];
                }
                return json.data || [];
            }
        },
        "columns": [
            {"data": "order_number"},
            {"data": "product_name"},
            {
                "data": "status",
                "render": function(data, type, row) {
                    var statusClass = 'label-' + data.toLowerCase().replace(' ', '-');
                    return '<span class="label ' + statusClass + '">' + data + '</span>';
                }
            },
            {"data": "target_quantity"},
            {"data": "completed_quantity"},
            {"data": "start_date"},
            {"data": "expected_completion_date"},
            {"data": "created_by"},
            {
                "data": null,
                "orderable": false,
                "render": function(data, type, row) {
                    var buttons = '<div class="btn-group">';
                    
                    // View button
                    buttons += '<button type="button" class="btn btn-info btn-sm" onclick="viewProductionOrder('+ row.id +')" title="View Details">';
                    buttons += '<i class="fa fa-eye"></i>';
                    buttons += '</button>';

                    // Edit button - only for draft and confirmed status
                    if(['Draft', 'Confirmed'].includes(row.status)) {
                        buttons += '<button type="button" class="btn btn-warning btn-sm" onclick="editProductionOrder('+ row.id +')" title="Edit Order">';
                        buttons += '<i class="fa fa-edit"></i>';
                        buttons += '</button>';
                    }

                    // Delete button - only for draft status
                    if(row.status === 'Draft') {
                        buttons += '<button type="button" class="btn btn-danger btn-sm" onclick="removeProductionOrder('+ row.id +')" title="Delete Order">';
                        buttons += '<i class="fa fa-trash"></i>';
                        buttons += '</button>';
                    }

                    buttons += '</div>';
                    return buttons;
                }
            }
        ],
        "order": [[0, "desc"]],
        "pageLength": 10,
        "responsive": true,
        "dom": '<"row"<"col-sm-6"l><"col-sm-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-5"i><"col-sm-7"p>>',
        "language": {
            "lengthMenu": "Show _MENU_ entries",
            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "search": "Search:",
            "searchPlaceholder": "Search orders...",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        }
    });

    // Initialize Select2 for product selection
    $('#product_id').select2({
        placeholder: "Select a product",
        allowClear: true
    });

    // Add Material Row
    $("#addMaterialRow").click(function() {
        var newRow = $("#productMaterialsTable tbody tr:first").clone();
        newRow.find('input').val('');
        newRow.find('select').val('').trigger('change');
        newRow.find('.available-stock').text('0');
        $("#productMaterialsTable tbody").append(newRow);
        
        // Initialize select2 for the new row
        newRow.find('.material-select').select2({
            placeholder: "Select Material",
            allowClear: true
        });
    });

    // Remove Material Row
    $(document).on('click', '.remove-material', function() {
        if($("#productMaterialsTable tbody tr").length > 1) {
            $(this).closest('tr').remove();
        }
    });

    // Initialize select2 for existing material selects
    $('.material-select').select2({
        placeholder: "Select Material",
        allowClear: true
    });

    // Update Available Stock on Material Selection
    $(document).on('change', '.material-select', function() {
        var stock = $(this).find(':selected').data('stock') || 0;
        $(this).closest('tr').find('.available-stock').text(stock);
        validateMaterialQuantity($(this).closest('tr'));
    });

    // Validate material quantity against available stock
    function validateMaterialQuantity(row) {
        var availableStock = parseFloat(row.find('.available-stock').text()) || 0;
        var requiredQuantity = parseFloat(row.find('.required-quantity').val()) || 0;
        
        if(requiredQuantity > availableStock) {
            row.find('.required-quantity').addClass('is-invalid');
            return false;
        }
        row.find('.required-quantity').removeClass('is-invalid');
        return true;
    }

    // Validate quantity input
    $(document).on('input', '.required-quantity', function() {
        validateMaterialQuantity($(this).closest('tr'));
    });

    // Handle Production Order Form Submit with validation
    $("#submitProductionOrderForm").on('submit', function(e) {
        e.preventDefault();
        
        // Basic form validation
        var isValid = true;
        $(this).find('select[required], input[required]').each(function() {
            if(!$(this).val()) {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        // Validate material quantities
        $('#productMaterialsTable tbody tr').each(function() {
            if(!validateMaterialQuantity($(this))) {
                isValid = false;
            }
        });

        if(!isValid) {
            alert('Please check all required fields and material quantities');
            return false;
        }

        // Submit form
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $("#addProductionOrderModal").modal('hide');
                    $("#submitProductionOrderForm")[0].reset();
                    // Reset select2 fields
                    $('.material-select').val('').trigger('change');
                    // Reset available stock displays
                    $('.available-stock').text('0');
                    // Reload DataTable
                    $('#productionOrdersTable').DataTable().ajax.reload();
                    // Show success message
                    $('.remove-messages').html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fa fa-check"></i></strong> '+ response.messages +
                        '</div>');
                } else {
                    // Show error message
                    $('.remove-messages').html('<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fa fa-times"></i></strong> '+ response.messages +
                        '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax Error:', error);
                alert('An error occurred while processing your request. Please try again.');
            }
        });
    });
});

// View Production Order
function viewProductionOrder(orderId) {
    $.ajax({
        url: 'php_action/fetchSingleProductionOrder.php',
        type: 'POST',
        data: { id: orderId },
        dataType: 'json',
        success: function(response) {
            if(!response.success) {
                alert(response.messages || 'Error fetching production order details');
                return;
            }

            var data = response.data;
            // Create view modal content
            var html = `
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-eye"></i> View Production Order</h4>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width:30%">Order Number</th>
                            <td>${data.order_number}</td>
                        </tr>
                        <tr>
                            <th>Product</th>
                            <td>${data.product_display_name}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span class="label label-${data.status.toLowerCase()}">${data.status}</span></td>
                        </tr>
                        <tr>
                            <th>Target Quantity</th>
                            <td>${data.target_quantity}</td>
                        </tr>
                        <tr>
                            <th>Completed Quantity</th>
                            <td>${data.completed_quantity || '0.00'}</td>
                        </tr>
                        <tr>
                            <th>Start Date</th>
                            <td>${data.start_date}</td>
                        </tr>
                        <tr>
                            <th>Expected Completion</th>
                            <td>${data.expected_completion_date}</td>
                        </tr>
                        ${data.actual_completion_date ? `
                        <tr>
                            <th>Actual Completion</th>
                            <td>${data.actual_completion_date}</td>
                        </tr>
                        ` : ''}
                        <tr>
                            <th>Notes</th>
                            <td>${data.notes || '-'}</td>
                        </tr>
                    </table>

                    <h4>Required Materials</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Material</th>
                                <th>Required Quantity</th>
                                <th>Consumed Quantity</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.materials && data.materials.length > 0 ? data.materials.map(material => `
                                <tr>
                                    <td>${material.name}</td>
                                    <td>${material.required_quantity}</td>
                                    <td>${material.consumed_quantity}</td>
                                    <td>${material.current_stock}</td>
                                    <td><span class="label label-${material.status.toLowerCase()}">${material.status}</span></td>
                                </tr>
                            `).join('') : '<tr><td colspan="5">No materials found</td></tr>'}
                        </tbody>
                    </table>

                    <h4>Production Progress History</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Quantity</th>
                                <th>Notes</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.progress && data.progress.length > 0 ? data.progress.map(prog => `
                                <tr>
                                    <td>${prog.created_at}</td>
                                    <td>${prog.quantity}</td>
                                    <td>${prog.notes || '-'}</td>
                                    <td>${prog.created_by}</td>
                                </tr>
                            `).join('') : '<tr><td colspan="4">No progress records found</td></tr>'}
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            `;

            $('#editProductionOrderModal .modal-content').html(html);
            $('#editProductionOrderModal').modal('show');
        },
        error: function(xhr, status, error) {
            console.error('Ajax Error:', error);
            alert('Error fetching production order details');
        }
    });
}

// Edit Production Order
function editProductionOrder(orderId) {
    $.ajax({
        url: 'php_action/fetchSingleProductionOrder.php',
        type: 'POST',
        data: {orderId: orderId},
        dataType: 'json',
        success: function(response) {
            if(!response.success) {
                alert(response.messages || 'Error fetching production order details');
                return;
            }

            var data = response.data;
            // Create edit form
            var html = `
                <form id="editProductionOrderForm" action="php_action/updateProductionOrder.php" method="POST">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Production Order</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="control-label col-sm-3">Order Number:</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="${data.order_number}" readonly />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-3">Status:</label>
                            <div class="col-sm-9">
                                <select class="form-control" name="status" required>
                                    <option value="draft" ${data.status.toLowerCase() === 'draft' ? 'selected' : ''}>Draft</option>
                                    <option value="confirmed" ${data.status.toLowerCase() === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                                    <option value="in_progress" ${data.status.toLowerCase() === 'in progress' ? 'selected' : ''}>In Progress</option>
                                    <option value="completed" ${data.status.toLowerCase() === 'completed' ? 'selected' : ''}>Completed</option>
                                    <option value="cancelled" ${data.status.toLowerCase() === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-3">Completed Quantity:</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" name="completed_quantity" value="${parseFloat(data.completed_quantity) || 0}" step="0.01" min="0" max="${parseFloat(data.target_quantity)}" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="control-label col-sm-3">Notes:</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" name="notes" rows="3">${data.notes || ''}</textarea>
                            </div>
                        </div>
                        <input type="hidden" name="orderId" value="${orderId}" />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            `;

            $('#editProductionOrderModal .modal-content').html(html);
            $('#editProductionOrderModal').modal('show');

            // Handle edit form submission
            $('#editProductionOrderForm').off('submit').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            $('#editProductionOrderModal').modal('hide');
                            // Reload DataTable
                            $('#productionOrdersTable').DataTable().ajax.reload();
                            // Show success message
                            $('.remove-messages').html('<div class="alert alert-success">'+
                                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                                '<strong><i class="fa fa-check"></i></strong> '+ response.messages +
                                '</div>');
                        } else {
                            alert(response.messages);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Ajax Error:', error);
                        alert('Error updating production order');
                    }
                });
            });
        },
        error: function(xhr, status, error) {
            console.error('Ajax Error:', error);
            alert('Error fetching production order details');
        }
    });
}

// Remove Production Order
function removeProductionOrder(orderId) {
    if(confirm('Are you sure you want to delete this production order?')) {
        $.ajax({
            url: 'php_action/removeProductionOrder.php',
            type: 'POST',
            data: {orderId: orderId},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Reload DataTable
                    $('#productionOrdersTable').DataTable().ajax.reload();
                    // Show success message
                    $('.remove-messages').html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fa fa-check"></i></strong> '+ response.messages +
                        '</div>');
                } else {
                    alert(response.messages);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax Error:', error);
                alert('Error removing production order');
            }
        });
    }
} 