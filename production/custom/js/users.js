$(document).ready(function() {
    // Load roles for dropdowns
    function loadRoles() {
        $.ajax({
            url: 'php_action/fetchRoles.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                var options = '<option value="">Select Role</option>';
                if (Array.isArray(response)) {
                    response.forEach(function(role) {
                        options += '<option value="' + role.role_id + '">' + role.role_name + '</option>';
                    });
                }
                $('#role, #editRole').html(options);
            },
            error: function() {
                showAlert('error', 'Error loading roles');
            }
        });
    }

    // Call loadRoles when document is ready
    loadRoles();

    // Call loadRoles when add modal is shown
    $('#addUserModal').on('show.bs.modal', function() {
        loadRoles();
    });

    // Initialize DataTable with improved configuration
    var usersTable = $('#usersTable').DataTable({
        'ajax': 'php_action/fetchUsers.php',
        'order': [[4, 'desc']], // Sort by created_at by default
        'columns': [
            { data: 'username' },
            { data: 'email' },
            { data: 'role_name' },
            { 
                data: 'status',
                render: function(data) {
                    return data == 1 ? 
                        '<span class="label label-success">Active</span>' : 
                        '<span class="label label-danger">Inactive</span>';
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return moment(data).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            { 
                data: 'updated_at',
                render: function(data) {
                    return data ? moment(data).format('YYYY-MM-DD HH:mm:ss') : '';
                }
            },
            {
                data: null,
                render: function(data, type, row) {
                    var buttons = '';
                    
                    if(typeof hasEditPermission !== 'undefined' && hasEditPermission) {
                        buttons += '<button class="btn btn-warning btn-sm" onclick="editUser('+ row.user_id +')" title="Edit User"><i class="fa fa-edit"></i></button> ';
                    }
                    
                    if(typeof hasDeletePermission !== 'undefined' && hasDeletePermission) {
                        buttons += '<button class="btn btn-danger btn-sm" onclick="removeUser('+ row.user_id +')" title="Delete User"><i class="fa fa-trash"></i></button>';
                    }
                    
                    return buttons;
                }
            }
        ],
        'responsive': true,
        'pageLength': 10,
        'dom': 'Bfrtip',
        'buttons': ['copy', 'csv', 'excel', 'pdf', 'print']
    });

    // Enhanced form validation
    function validateForm(formData, isEdit = false) {
        var errors = [];
        
        // Username validation
        if (formData.username.length < 3) {
            errors.push('Username must be at least 3 characters long');
        }
        
        // Email validation
        var emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        if (!emailRegex.test(formData.email)) {
            errors.push('Please enter a valid email address');
        }
        
        // Password validation for new users or when changing password
        if (!isEdit || (isEdit && formData.password)) {
            if (formData.password.length < 6) {
                errors.push('Password must be at least 6 characters long');
            }
            if (formData.password !== formData.confirmPassword) {
                errors.push('Passwords do not match');
            }
        }
        
        // Role validation
        if (!formData.roleId) {
            errors.push('Please select a role');
        }
        
        return errors;
    }

    // Add User Form Submit with enhanced handling
    $("#addUserForm").on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            username: $('#username').val().trim(),
            email: $('#email').val().trim(),
            password: $('#password').val(),
            confirmPassword: $('#confirmPassword').val(),
            roleId: $('#role').val()
        };
        
        var errors = validateForm(formData);
        if (errors.length > 0) {
            showAlert('error', errors.join('<br>'));
            return;
        }
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $("#addUserModal").modal('hide');
                    $("#addUserForm")[0].reset();
                    usersTable.ajax.reload();
                    showAlert('success', response.messages);
                } else {
                    showAlert('error', response.messages);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while creating user');
            }
        });
    });

    // Edit User
    window.editUser = function(id) {
        $.ajax({
            url: 'php_action/fetchUser.php',
            type: 'POST',
            data: {userId: id},
            dataType: 'json',
            success: function(response) {
                $("#editUserId").val(response.user_id);
                $("#editUsername").val(response.username);
                $("#editEmail").val(response.email);
                $("#editRole").val(response.role_id);
            }
        });
    }

    // Edit User Form Submit with enhanced handling
    $("#editUserForm").on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            userId: $('#editUserId').val(),
            username: $('#editUsername').val().trim(),
            email: $('#editEmail').val().trim(),
            password: $('#editPassword').val(),
            confirmPassword: $('#editConfirmPassword').val(),
            roleId: $('#editRole').val(),
            status: $('#editStatus').val()
        };
        
        var errors = validateForm(formData, true);
        if (errors.length > 0) {
            showAlert('error', errors.join('<br>'));
            return;
        }
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $("#editUserModal").modal('hide');
                    usersTable.ajax.reload();
                    showAlert('success', response.messages);
                } else {
                    showAlert('error', response.messages);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while updating user');
            }
        });
    });

    // Remove User
    window.removeUser = function(id) {
        if(confirm('Are you sure you want to delete this user?')) {
            $.ajax({
                url: 'php_action/removeUser.php',
                type: 'POST',
                data: {userId: id},
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        usersTable.ajax.reload();
                        
                        $('.alert').remove();
                        $(".container").prepend('<div class="alert alert-success alert-dismissible" role="alert">'+
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                            response.messages+
                            '</div>');
                    }
                }
            });
        }
    }

    // Enhanced alert function
    function showAlert(type, message) {
        // Remove any existing alerts
        $('.alert').remove();
        
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var alert = '<div class="alert ' + alertClass + ' alert-dismissible" role="alert">' +
                   '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                   '<span aria-hidden="true">&times;</span></button>' + message + '</div>';
        
        if (type === 'success') {
            $(".container").prepend(alert);
        } else {
            $(".modal:visible .modal-body").prepend(alert);
        }
        
        // Auto-dismiss success messages
        if (type === 'success') {
            setTimeout(function() {
                $('.alert').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 3000);
        }
    }

    // Clear form and errors when modal is closed
    $('.modal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        $('.alert').remove();
        $('.form-group').removeClass('has-error');
    });

    // Add loading indicators
    $(document).on({
        ajaxStart: function() {
            $("button[type=submit]").prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
        },
        ajaxStop: function() {
            $("button[type=submit]").prop('disabled', false).html('Save Changes');
        }
    });
});

// Function to validate email
function isValidEmail(email) {
    var emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    return emailRegex.test(email);
} 