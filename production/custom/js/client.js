var manageClientTable;

$(document).ready(function() {
    // Initialize DataTable
    manageClientTable = $("#manageClientTable").DataTable({
        'ajax': {
            url: 'php_action/fetchClientsMain.php',
            type: 'GET'
        },
        'order': [],
        'columns': [
            { data: 0 }, // company_name
            { data: 1 }, // tin_number
            { data: 2 }, // phone
            { data: 3 }, // email
            { data: 4 }, // address
            { 
                data: 5, // status
                render: function(data, type, row) {
                    return data == 1 ? 
                        '<span class="label label-success">Active</span>' : 
                        '<span class="label label-danger">Inactive</span>';
                }
            },
            { 
                data: 6, // options column
                orderable: false,
                render: function(data, type, row) {
                    return '<div class="btn-group">'+
                        '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'+
                            'Action <span class="caret"></span>'+
                        '</button>'+
                        '<ul class="dropdown-menu dropdown-menu-right">'+
                            '<li><a href="javascript:void(0);" onclick="editClient('+row[7]+')"><i class="fa fa-edit"></i> Edit</a></li>'+
                            '<li><a href="javascript:void(0);" onclick="removeClient('+row[7]+')"><i class="fa fa-trash"></i> Remove</a></li>'+
                        '</ul>'+
                    '</div>';
                }
            }
        ],
        'pageLength': 10,
        'responsive': true,
        'dom': 'Bfrtip',
        'buttons': ['copy', 'csv', 'excel', 'pdf', 'print']
    });

    // Handle Add Client Form Submit
    $("#submitClientForm").on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'php_action/createClientMain.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Reset form
                    $("#submitClientForm")[0].reset();
                    
                    // Close modal
                    $("#addClientModal").modal('hide');
                    
                    // Reload table
                    manageClientTable.ajax.reload();
                    
                    // Show success message
                    $(".remove-messages").html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                    '</div>');
                } else {
                    // Show error message
                    $(".remove-messages").html('<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.messages +
                    '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                $(".remove-messages").html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> An error occurred'+
                '</div>');
            }
        });
    });

    // Handle Remove Client Button
    $("#removeClientBtn").on('click', function() {
        var clientId = $(this).data('id');
        
        $.ajax({
            url: 'php_action/removeClient.php',
            type: 'POST',
            data: {clientId: clientId},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Close modal
                    $("#removeClientModal").modal('hide');
                    
                    // Reload table
                    manageClientTable.ajax.reload();
                    
                    // Show success message
                    $(".remove-messages").html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                    '</div>');
                } else {
                    // Show error message
                    $(".remove-messages").html('<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.messages +
                    '</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                $(".remove-messages").html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> An error occurred'+
                '</div>');
            }
        });
    });
});

// Edit Client
function editClient(clientId) {
    $.ajax({
        url: 'php_action/fetchSelectedClient.php',
        type: 'POST',
        data: {clientId: clientId},
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Populate form fields
                $("#clientId").val(response.id);
                $("#editCompanyName").val(response.company_name);
                $("#editTinNumber").val(response.tin_number);
                $("#editPhone").val(response.phone);
                $("#editEmail").val(response.email);
                $("#editAddress").val(response.address);
                $("#editStatus").val(response.status);
                
                // Show modal
                $("#editClientModal").modal('show');
            } else {
                alert(response.messages);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            alert('Error fetching client details');
        }
    });
}

// Remove Client
function removeClient(clientId) {
    // Set the client ID to the remove button
    $("#removeClientBtn").data('id', clientId);
    // Show the confirmation modal
    $("#removeClientModal").modal('show');
}

// Handle Edit Client Form Submit
$("#editClientForm").on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: 'php_action/editClient.php',
        type: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Reset form
                $("#editClientForm")[0].reset();
                
                // Close modal
                $("#editClientModal").modal('hide');
                
                // Reload table
                manageClientTable.ajax.reload();
                
                // Show success message
                $(".remove-messages").html('<div class="alert alert-success">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                '</div>');
            } else {
                // Show error message in modal
                $(".edit-messages").html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.messages +
                '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            $(".edit-messages").html('<div class="alert alert-danger">'+
                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> An error occurred'+
            '</div>');
        }
    });
}); 