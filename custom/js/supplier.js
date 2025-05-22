var manageSupplierTable;

$(document).ready(function() {
    // Initialize DataTable
    manageSupplierTable = $('#manageSupplierTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchSuppliers.php',
            'type': 'GET',
            'error': function(xhr, error, thrown) {
                console.log('DataTables error:', error);
                if (xhr.responseText) {
                    console.log('Server response:', xhr.responseText);
                }
            }
        },
        'order': [],
        'pageLength': 10,
        'responsive': true,
        'dom': '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        'buttons': [
            'copy', 'csv', 'print'
        ],
        'processing': true,
        'serverSide': false,
        'columns': [
            { 
                data: 'company_name',
                render: function(data, type, row) {
                    return data || '';
                }
            },
            { 
                data: 'contact_person',
                render: function(data, type, row) {
                    return data || '';
                }
            },
            { 
                data: 'email',
                render: function(data, type, row) {
                    return data || '';
                }
            },
            { 
                data: 'phone',
                render: function(data, type, row) {
                    return data || '';
                }
            },
            { 
                data: 'address',
                render: function(data, type, row) {
                    return data || '';
                }
            },
            { 
                data: 'status',
                render: function(data, type, row) {
                    if (type === 'display') {
                        return data == 1 ? 
                            '<span class="badge badge-active">Active</span>' : 
                            '<span class="badge badge-inactive">Inactive</span>';
                    }
                    return data;
                }
            },
            { 
                data: 'action',
                orderable: false,
                searchable: false
            }
        ],
        'language': {
            'processing': 'Loading...',
            'emptyTable': 'No suppliers found',
            'zeroRecords': 'No matching suppliers found'
        },
        'drawCallback': function(settings) {
            if (settings.json && settings.json.error) {
                console.error('Server error:', settings.json.error);
            }
        }
    });

    // Submit supplier form
    $("#submitSupplierForm").unbind('submit').bind('submit', function() {
        var form = $(this);

        // Remove error messages
        $(".text-danger").remove();

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success:function(response) {
                if(response.success === true) {
                    // Reset form
                    $("#submitSupplierForm")[0].reset();
                    
                    // Reload the manage table
                    manageSupplierTable.ajax.reload(null, false);

                    // Show success message
                    $("#add-supplier-messages").html('<div class="alert alert-success">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> ' + response.messages +
                        '</div>');

                    // Close modal
                    $("#addSupplierModal").modal('hide');

                } else {
                    $("#add-supplier-messages").html('<div class="alert alert-warning">' +
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' + response.messages +
                        '</div>');
                }
            }
        });

        return false;
    });
});

// Edit supplier
function editSupplier(supplierId = null) {
    if(supplierId) {
        $("#supplierId").val(supplierId);

        $.ajax({
            url: 'php_action/fetchSelectedSupplier.php',
            type: 'post',
            data: {supplierId: supplierId},
            dataType: 'json',
            success:function(response) {
                $("#editCompanyName").val(response.company_name);
                $("#editContactPerson").val(response.contact_person);
                $("#editEmail").val(response.email);
                $("#editPhone").val(response.phone);
                $("#editAddress").val(response.address);
                $("#editActive").val(response.active);

                // Show modal
                $("#editSupplierModal").modal('show');
            }
        });
    }
}

// Submit edit supplier form
$("#editSupplierForm").unbind('submit').bind('submit', function() {
    var form = $(this);

    $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: form.serialize(),
        dataType: 'json',
        success:function(response) {
            if(response.success === true) {
                // Reload the manage table
                manageSupplierTable.ajax.reload(null, false);

                // Show success message
                $("#edit-supplier-messages").html('<div class="alert alert-success">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> ' + response.messages +
                    '</div>');

                // Close modal
                $("#editSupplierModal").modal('hide');

            } else {
                $("#edit-supplier-messages").html('<div class="alert alert-warning">' +
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> ' + response.messages +
                    '</div>');
            }
        }
    });

    return false;
});

// Remove supplier
function removeSupplier(id = null) {
    if(id) {
        // Remove previous click event handler
        $('#removeSupplierBtn').off('click').on('click', function() {
            $.ajax({
                url: 'php_action/removeSupplier.php',
                type: 'post',
                data: {supplierId: id},
                dataType: 'json',
                success: function(response) {
                    if(response.success == true) {
                        // Hide modal
                        $('#removeSupplierModal').modal('hide');
                        
                        // Reload the table
                        manageSupplierTable.ajax.reload(null, false);
                        
                        // Show success message
                        $('.remove-messages').html('<div class="alert alert-success">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                        '</div>');
                    } else {
                        // Show error message
                        $('.removeSupplierMessages').html('<div class="alert alert-warning">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.messages +
                        '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    $('.removeSupplierMessages').html('<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> An error occurred while removing the supplier.'+
                    '</div>');
                }
            });
        });
    }
} 