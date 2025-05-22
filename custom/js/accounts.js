var manageAccountsTable;

$(document).ready(function() {
    manageAccountsTable = $('#manageAccountsTable').DataTable({
        'ajax': {
            url: 'php_action/fetchAccounts.php',
            type: 'GET',
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                console.error('Server response:', xhr.responseText);
                console.error('Exception:', thrown);
            },
            dataSrc: function(json) {
                console.log('Received data:', json);
                return json.data;
            }
        },
        'order': [],
        'pageLength': 10,
        'columns': [
            { 
                'data': null,
                'render': function(data, type, row) {
                    var activeClass = (row.id == activeAccountId) ? 'active' : '';
                    return '<a href="#" class="select-account ' + activeClass + '" data-id="' + row.id + '">' + 
                           row.account_owner + '</a>';
                }
            },
            { 'data': 'account_platform' },
            { 'data': 'currency' },
            { 'data': 'current_balance' },
            { 'data': 'number_of_transactions' },
            { 'data': 'created_at' },
            {
                'data': null,
                'render': function(data, type, row) {
                    var html = '<div class="btn-group">' +
                        '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                        'Action <span class="caret"></span>' +
                        '</button>' +
                        '<ul class="dropdown-menu">';
                    
                    if (row.id != activeAccountId) {
                        html += '<li><a href="#" class="select-account" data-id="' + row.id + '"><i class="fa fa-check"></i> Select Account</a></li>';
                    }
                    
                    html += '<li><a href="#" onclick="editAccount(' + row.id + ')"><i class="fa fa-edit"></i> Edit</a></li>' +
                           '<li><a href="#" onclick="removeAccount(' + row.id + ')"><i class="fa fa-trash"></i> Remove</a></li>' +
                           '</ul></div>';
                    return html;
                }
            }
        ]
    });

    // Handle account selection
    $('#manageAccountsTable').on('click', '.select-account', function(e) {
        e.preventDefault();
        var accountId = $(this).data('id');
        
        $.ajax({
            url: 'php_action/selectAccount.php',
            type: 'POST',
            data: { account_id: accountId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Reload the page to reflect the new active account
                    location.reload();
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to select account', 'error');
            }
        });
    });

    // Add Account button click handler
    $("#addAccountModalBtn").click(function() {
        $("#addAccountForm")[0].reset();
        $("#addAccountModal").modal('show');
    });

    // Add Account form submit handler
    $("#addAccountForm").unbind('submit').bind('submit', function() {
        var form = $(this);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $("#addAccountModal").modal('hide');
                    manageAccountsTable.ajax.reload(null, false);
                    Swal.fire('Success', response.messages, 'success');
                } else {
                    Swal.fire('Error', response.messages, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error(xhr, status, error);
                Swal.fire('Error', 'An error occurred while adding the account', 'error');
            }
        });

        return false;
    });
});

function editAccount(id) {
    $.ajax({
        url: 'php_action/fetchSelectedAccount.php',
        type: 'post',
        data: {id: id},
        dataType: 'json',
        success: function(response) {
            if(response.success === false) {
                $('.removeMessages').html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-warning-sign"></i></strong> '+ response.messages +
                    '</div>');
            } else {
                $("#editAccountId").val(response.id);
                $("#editAccountOwner").val(response.account_owner);
                $("#editAccountPlatform").val(response.account_platform);
                $("#editAccountCurrency").val(response.Currency);
                
                $("#editAccountModal").modal('show');
            }
        },
        error: function(err) {
            console.error(err);
        }
    });
}

// Add form submission handler for edit form
$("#editAccountForm").on('submit', function(e) {
    e.preventDefault();
    
    var form = $(this);
    var formData = form.serialize();

    $.ajax({
        url: form.attr('action'),
        type: form.attr('method'),
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $("#edit-account-messages").html('<div class="alert alert-success">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                    '</div>');

                // Close modal after 2 seconds and reload table
                setTimeout(function() {
                    $("#editAccountModal").modal('hide');
                    manageAccountsTable.ajax.reload(null, false);
                }, 2000);
            } else {
                $("#edit-account-messages").html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-warning-sign"></i></strong> '+ response.messages +
                    '</div>');
            }
        },
        error: function(err) {
            console.error(err);
        }
    });
});

function removeAccount(id) {
    if(confirm('Are you sure you want to remove this account?')) {
        $.ajax({
            url: 'php_action/removeAccount.php',
            type: 'post',
            data: {id: id},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    manageAccountsTable.ajax.reload(null, false);
                }
            }
        });
    }
} 