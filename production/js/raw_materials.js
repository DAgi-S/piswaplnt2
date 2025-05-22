$(document).ready(function() {
    // Initialize DataTable
    var rawMaterialsTable = $('#rawMaterialsTable').DataTable({
        "ajax": "php_action/fetchRawMaterials.php",
        "order": [[0, "asc"]],
        "columns": [
            {"data": "material_code"},
            {"data": "name"},
            {"data": "category_name"},
            {"data": "unit"},
            {"data": "current_stock"},
            {"data": "min_stock_level"},
            {"data": "cost_per_unit"},
            {
                "data": "status",
                "render": function(data, type, row) {
                    if(type === 'display') {
                        let badgeClass = data === 'active' ? 'success' : 'danger';
                        return '<span class="label label-' + badgeClass + '">' + 
                               data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                    }
                    return data;
                }
            },
            {
                "data": null,
                "orderable": false,
                "render": function(data, type, row) {
                    let buttons = '<div class="btn-group">' +
                        '<button type="button" class="btn btn-default dropdown-toggle action-btn" data-toggle="dropdown">' +
                        '<i class="fa fa-gear"></i> Action <span class="caret"></span>' +
                        '</button>' +
                        '<ul class="dropdown-menu dropdown-menu-right">' +
                        '<li><a href="#" onclick="editRawMaterial(' + row.id + ')"><i class="fa fa-edit"></i> Edit</a></li>' +
                        '<li><a href="#" onclick="adjustStock(' + row.id + ')"><i class="fa fa-exchange"></i> Adjust Stock</a></li>' +
                        '<li><a href="#" onclick="viewStockHistory(' + row.id + ')"><i class="fa fa-history"></i> View History</a></li>';
                    
                    if(row.status === 'active') {
                        buttons += '<li><a href="#" onclick="changeMaterialStatus(' + row.id + ', \'' + row.status + '\')"><i class="fa fa-ban"></i> Deactivate</a></li>';
                    } else {
                        buttons += '<li><a href="#" onclick="changeMaterialStatus(' + row.id + ', \'' + row.status + '\')"><i class="fa fa-check"></i> Activate</a></li>';
                    }
                    
                    buttons += '</ul></div>';
                    return buttons;
                }
            }
        ],
        "drawCallback": function() {
            $('.dropdown-toggle').dropdown();
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'copy',
                text: '<i class="fa fa-copy"></i> Copy',
                className: 'btn btn-default'
            },
            {
                extend: 'csv',
                text: '<i class="fa fa-file-text-o"></i> CSV',
                className: 'btn btn-default'
            },
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel-o"></i> Excel',
                className: 'btn btn-default'
            },
            {
                extend: 'pdf',
                text: '<i class="fa fa-file-pdf-o"></i> PDF',
                className: 'btn btn-default'
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print"></i> Print',
                className: 'btn btn-default'
            }
        ]
    });

    // Submit new raw material form
    $('#submitRawMaterialForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addRawMaterialModal').modal('hide');
                    $('#submitRawMaterialForm')[0].reset();
                    rawMaterialsTable.ajax.reload();
                    
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
                    text: 'An error occurred while processing your request'
                });
            }
        });
    });

    // Submit edit raw material form
    $('#editRawMaterialForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editRawMaterialModal').modal('hide');
                    rawMaterialsTable.ajax.reload();
                    
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
                    text: 'An error occurred while processing your request'
                });
            }
        });
    });

    // Submit stock adjustment form
    $('#adjustStockForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#adjustStockModal').modal('hide');
                    rawMaterialsTable.ajax.reload();
                    
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
                    text: 'An error occurred while processing your request'
                });
            }
        });
    });
});

// Function to edit raw material
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
                $('#editWarehouseId').val(response.warehouse_id);
                
                // Trigger change for select2 dropdowns
                $('#editCategoryId, #editUnit, #editWarehouseId').trigger('change');
                
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

// Function to adjust stock
function adjustStock(id, name, currentStock) {
    $('#adjustMaterialId').val(id);
    $('#adjustMaterialName').text(name);
    $('#adjustCurrentStock').text(currentStock.toFixed(2));
    $('#adjustQuantity').val('');
    $('#adjustmentNotes').val('');
    $('#adjustStockModal').modal('show');
}

// Function to view stock history
function viewStockHistory(id) {
    $.ajax({
        url: 'php_action/fetchRawMaterialHistory.php',
        type: 'POST',
        data: { materialId: id },
        dataType: 'json',
        success: function(response) {
            let historyHtml = '<div class="table-responsive"><table class="table table-bordered table-striped">';
            historyHtml += '<thead><tr><th>Date</th><th>Type</th><th>Quantity</th><th>Reference</th><th>Notes</th><th>By</th></tr></thead><tbody>';
            
            if(Array.isArray(response)) {
                response.forEach(function(record) {
                    historyHtml += '<tr>';
                    historyHtml += '<td>' + moment(record.created_at).format('YYYY-MM-DD HH:mm:ss') + '</td>';
                    historyHtml += '<td><span class="label label-' + (record.movement_type === 'in' ? 'success' : 'danger') + 
                                 '">' + record.movement_type.toUpperCase() + '</span></td>';
                    historyHtml += '<td>' + parseFloat(record.quantity).toFixed(2) + '</td>';
                    historyHtml += '<td>' + record.reference_type + ' #' + record.reference_id + '</td>';
                    historyHtml += '<td>' + (record.notes || '-') + '</td>';
                    historyHtml += '<td>' + record.created_by + '</td>';
                    historyHtml += '</tr>';
                });
            }
            
            historyHtml += '</tbody></table></div>';
            
            $('#stockHistoryTable').html(historyHtml);
            $('#viewStockHistoryModal').modal('show');
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load stock history'
            });
        }
    });
}

// Function to change material status
function changeMaterialStatus(id, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    const actionText = newStatus === 'active' ? 'activate' : 'deactivate';
    
    Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to ${actionText} this material?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
    }).then((result) => {
        if (result.isConfirmed) {
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
                        Swal.fire(
                            'Success!',
                            response.messages,
                            'success'
                        );
                    } else {
                        Swal.fire(
                            'Error!',
                            response.messages,
                            'error'
                        );
                    }
                },
                error: function() {
                    Swal.fire(
                        'Error!',
                        'An error occurred while processing your request',
                        'error'
                    );
                }
            });
        }
    });
} 