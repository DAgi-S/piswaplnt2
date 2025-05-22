$(document).ready(function() {
    // Initialize DataTable
    $('#guestTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchGuests.php',
            'type': 'GET',
            'dataSrc': function(json) {
                // Log the response for debugging
                console.log('Server Response:', json);
                return json.data || [];
            },
            'error': function(xhr, error, thrown) {
                console.error('DataTables error:', error, thrown);
                console.log('Server Response:', xhr.responseText);
                $('.remove-messages').html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="fa fa-times"></i></strong> Error loading guest data. Please try refreshing the page.'+
                    '</div>');
            }
        },
        'processing': true,
        'serverSide': false,
        'order': [[0, 'desc']],
        'columns': [
            { data: 'guest_id' },
            { data: 'username' },
            { data: 'full_name' },
            { data: 'account_name', orderable: false },
            { data: 'currency', orderable: false },
            { data: 'access_level', orderable: false },
            { 
                data: 'status',
                render: function(data) {
                    return data == 1 ? 
                        '<span class="label label-success">Active</span>' : 
                        '<span class="label label-danger">Inactive</span>';
                }
            },
            { data: 'expiry_date' },
            { data: 'last_login' },
            { 
                data: null,
                orderable: false,
                render: function(data) {
                    return '<button class="btn btn-warning btn-sm" onclick="editGuest(' + data.id + ')"><i class="fa fa-edit"></i></button>';
                }
            }
        ]
    });

    // Initialize Select2 for account dropdowns
    $('#linkedAccount, #editLinkedAccount').select2();

    // Submit new guest form
    $("#submitGuestForm").on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'php_action/createGuest.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $("#addGuestModal").modal('hide');
                    $("#submitGuestForm")[0].reset();
                    $('.remove-messages').html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fa fa-check"></i></strong> '+ response.messages +
                        '</div>');
                    $('#guestTable').DataTable().ajax.reload();
                } else {
                    $('.remove-messages').html('<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fa fa-times"></i></strong> '+ response.messages +
                        '</div>');
                }
            }
        });
    });

    // Submit edit guest form
    $("#editGuestForm").on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'php_action/editGuest.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $("#editGuestModal").modal('hide');
                    $("#editGuestForm")[0].reset();
                    $('.remove-messages').html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fa fa-check"></i></strong> '+ response.messages +
                        '</div>');
                    $('#guestTable').DataTable().ajax.reload();
                } else {
                    $('.remove-messages').html('<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fa fa-times"></i></strong> '+ response.messages +
                        '</div>');
                }
            }
        });
    });

    // Clear forms when modals are closed
    $('#addGuestModal, #editGuestModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        $('.remove-messages').empty();
    });
});

// Function to edit guest
function editGuest(guestId) {
    if(guestId) {
        $.ajax({
            url: 'php_action/getGuestInfo.php',
            type: 'POST',
            data: {guestId: guestId},
            dataType: 'json',
            success: function(response) {
                if(!response.error) {
                    $("#editGuestModal").modal('show');
                    $("#editUsername").val(response.username);
                    $("#editFullName").val(response.full_name);
                    $("#editLinkedAccount").val(response.linked_account_id).trigger('change');
                    $("#editExpiryDate").val(response.expiry_date);
                    $("#editStatus").val(response.status);
                    $("#guestId").val(response.id);
                    
                    // Set access checkboxes
                    var access = JSON.parse(response.access_level);
                    $("input[name='editAccess[]']").prop('checked', false);
                    if(access) {
                        access.forEach(function(item) {
                            $("input[name='editAccess[]'][value='" + item + "']").prop('checked', true);
                        });
                    }
                }
            }
        });
    }
} 