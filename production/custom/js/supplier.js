var manageSupplierTable;

$(document).ready(function() {
    // Initialize DataTable with improved styling
    manageSupplierTable = $("#manageSupplierTable").DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "php_action/fetchSuppliersMain.php",
            "type": "GET",
            "dataSrc": function(json) {
                if(json.error) {
                    console.error('Server Error:', json.message);
                    $(".remove-messages").html('<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ json.message +
                    '</div>');
                    return [];
                }
                return json.data || [];
            }
        },
        "columns": [
            {"data": 0, "name": "company_name"},
            {"data": 1, "name": "contact_person"},
            {"data": 2, "name": "phone"},
            {"data": 3, "name": "email"},
            {"data": 4, "name": "address"},
            {"data": 5, "name": "tin"},
            {"data": 6, "name": "status"},
            {"data": 7, "name": "options", "orderable": false, "searchable": false}
        ],
        "order": [[0, 'asc']],
        "pageLength": 10,
        "responsive": true,
        "dom": '<"row"<"col-sm-3"l><"col-sm-6"B><"col-sm-3"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-5"i><"col-sm-7"p>>',
        "buttons": [
            {
                extend: 'copy',
                className: 'btn btn-default',
                text: '<i class="fa fa-copy"></i> Copy'
            },
            {
                extend: 'csv',
                className: 'btn btn-default',
                text: '<i class="fa fa-file-text-o"></i> CSV'
            },
            {
                extend: 'excel',
                className: 'btn btn-default',
                text: '<i class="fa fa-file-excel-o"></i> Excel'
            },
            {
                extend: 'pdf',
                className: 'btn btn-default',
                text: '<i class="fa fa-file-pdf-o"></i> PDF'
            },
            {
                extend: 'print',
                className: 'btn btn-default',
                text: '<i class="fa fa-print"></i> Print'
            }
        ],
        "language": {
            "emptyTable": "No suppliers found",
            "info": "Showing _START_ to _END_ of _TOTAL_ suppliers",
            "infoEmpty": "Showing 0 to 0 of 0 suppliers",
            "infoFiltered": "(filtered from _MAX_ total suppliers)",
            "loadingRecords": '<i class="fa fa-spinner fa-spin"></i> Loading...',
            "processing": '<i class="fa fa-spinner fa-spin"></i> Processing...',
            "search": '<i class="fa fa-search"></i> _INPUT_',
            "searchPlaceholder": "Search suppliers...",
            "zeroRecords": "No matching suppliers found",
            "paginate": {
                "first": '<i class="fa fa-angle-double-left"></i>',
                "last": '<i class="fa fa-angle-double-right"></i>',
                "next": '<i class="fa fa-angle-right"></i>',
                "previous": '<i class="fa fa-angle-left"></i>'
            }
        },
        "drawCallback": function(settings) {
            // Reinitialize tooltips after draw
            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    // Add custom styling to the DataTable
    $('.dataTables_filter input').addClass('form-control').attr('placeholder', 'Search suppliers...');
    $('.dataTables_length select').addClass('form-control');

    // Handle supplier form submission with improved validation
    $("#submitSupplierForm").unbind('submit').bind('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        
        // Basic form validation
        var companyName = $("#companyName").val();
        var contactPerson = $("#contactPerson").val();
        var phone = $("#phone").val();
        
        if(!companyName || !contactPerson || !phone) {
            $(".remove-messages").html('<div class="alert alert-danger">'+
                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Please fill in all required fields'+
            '</div>');
            return false;
        }
        
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success == true) {
                    // Reset form and close modal
                    form[0].reset();
                    $("#addSupplierModal").modal('hide');
                    
                    // Reload table and show success message
                    manageSupplierTable.ajax.reload(null, false);
                    $(".remove-messages").html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                    '</div>');
                } else {
                    $(".remove-messages").html('<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.messages +
                    '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $(".remove-messages").html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> An error occurred while processing your request'+
                '</div>');
            }
        });
    });
});

// Edit supplier
function editSupplier(supplierId) {
    if(supplierId) {
        $.ajax({
            url: 'php_action/fetchSelectedSupplier.php',
            type: 'POST',
            data: {supplierId: supplierId},
            dataType: 'json',
            beforeSend: function() {
                $('.edit-messages').html('');
            },
            success: function(response) {
                try {
                    if(response.success === true) {
                        // Populate form with supplier data
                        $("#editCompanyName").val(response.data.company_name || '');
                        $("#editContactPerson").val(response.data.contact_person || '');
                        $("#editPhone").val(response.data.phone || '');
                        $("#editEmail").val(response.data.email || '');
                        $("#editAddress").val(response.data.address || '');
                        $("#editTin").val(response.data.tin || '');
                        $("#editStatus").val(response.data.active === 1 ? 'active' : 'inactive');
                        $("#supplierId").val(supplierId);
                        
                        // Show modal
                        $("#editSupplierModal").modal('show');
                    } else {
                        $('.edit-messages').html('<div class="alert alert-danger">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ (response.messages || 'Error fetching supplier data') +
                        '</div>');
                    }
                } catch(e) {
                    console.error('Error parsing response:', e);
                    $('.edit-messages').html('<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Error processing response'+
                    '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.log('Response Text:', xhr.responseText);
                $('.edit-messages').html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Error fetching supplier details'+
                '</div>');
            }
        });
    }
}

// Handle edit form submission
$("#editSupplierForm").on('submit', function(e) {
    e.preventDefault();
    var form = $(this);
    
    $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: form.serialize(),
        dataType: 'json',
        beforeSend: function() {
            $('.edit-messages').html('');
            $('#editSupplierModal button[type="submit"]').prop('disabled', true);
        },
        success: function(response) {
            try {
                if(response.success === true) {
                    // Reset form and close modal
                    form[0].reset();
                    $("#editSupplierModal").modal('hide');
                    
                    // Reload table and show success message
                    manageSupplierTable.ajax.reload(null, false);
                    $(".remove-messages").html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                    '</div>');
                } else {
                    $('.edit-messages').html('<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ (response.messages || 'Error updating supplier') +
                    '</div>');
                }
            } catch(e) {
                console.error('Error parsing response:', e);
                $('.edit-messages').html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Error processing response'+
                '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.log('Response Text:', xhr.responseText);
            $('.edit-messages').html('<div class="alert alert-danger">'+
                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> An error occurred while updating the supplier'+
            '</div>');
        },
        complete: function() {
            $('#editSupplierModal button[type="submit"]').prop('disabled', false);
        }
    });
});

// Remove supplier
function removeSupplier(supplierId) {
    if(supplierId) {
        $("#removeSupplierModal").modal('show');
        $("#removeSupplierBtn").unbind('click').bind('click', function() {
            $.ajax({
                url: 'php_action/removeSupplier.php',
                type: 'post',
                data: {supplierId: supplierId},
                dataType: 'json',
                success: function(response) {
                    if(response.success == true) {
                        // Close modal
                        $("#removeSupplierModal").modal('hide');
                        
                        // Reload table
                        manageSupplierTable.ajax.reload(null, false);
                        
                        // Show success message
                        $(".remove-messages").html('<div class="alert alert-success">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                        '</div>');
                    } else {
                        $(".remove-messages").html('<div class="alert alert-danger">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.messages +
                        '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('Error removing supplier');
                }
            });
        });
    }
}

// Add this function after the existing functions
function viewSupplier(supplierId) {
    if(supplierId) {
        $.ajax({
            url: 'php_action/fetchSelectedSupplierView.php',
            type: 'POST',
            data: {supplierId: supplierId},
            dataType: 'json',
            success: function(response) {
                if(response.success === true) {
                    // Populate the view modal with data
                    $("#view_company_name").text(response.data.company_name || 'N/A');
                    $("#view_contact_person").text(response.data.contact_person || 'N/A');
                    $("#view_phone").text(response.data.phone || 'N/A');
                    $("#view_email").text(response.data.email || 'N/A');
                    $("#view_tin").text(response.data.tin || 'N/A');
                    $("#view_address").text(response.data.address || 'N/A');
                    $("#view_status").html(
                        response.data.active == 1 
                            ? '<span class="label label-success">Active</span>' 
                            : '<span class="label label-danger">Inactive</span>'
                    );
                    
                    // Show the modal
                    $("#viewSupplierModal").modal('show');
                } else {
                    alert('Error fetching supplier details');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert('Error fetching supplier details');
            }
        });
    }
} 