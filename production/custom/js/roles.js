$(document).ready(function() {
    // Initialize DataTable
    var rolesTable = $('#rolesTable').DataTable({
        'ajax': 'php_action/fetchRoles.php',
        'order': [],
        'columns': [
            { data: 'role_name' },
            { data: 'description' },
            { data: 'created_at' },
            {
                data: null,
                render: function(data) {
                    var buttons = '<button class="btn btn-warning btn-sm" onclick="editRole(' + data.role_id + ')"><i class="fa fa-edit"></i></button>';
                    if (data.role_id != 1) { // Prevent deleting admin role
                        buttons += ' <button class="btn btn-danger btn-sm" onclick="deleteRole(' + data.role_id + ')"><i class="fa fa-trash"></i></button>';
                    }
                    return buttons;
                }
            }
        ]
    });

    // Add Role Form Submission
    $('#addRoleForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateRoleForm('add')) {
            return false;
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addRoleModal').modal('hide');
                    $('#addRoleForm')[0].reset();
                    rolesTable.ajax.reload(null, false);
                    showAlert('success', 'Role added successfully');
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while processing request');
            }
        });
    });

    // Edit Role Form Submission
    $('#editRoleForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateRoleForm('edit')) {
            return false;
        }

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editRoleModal').modal('hide');
                    rolesTable.ajax.reload(null, false);
                    showAlert('success', 'Role updated successfully');
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while processing request');
            }
        });
    });

    // Delete Role Form Submission
    $('#deleteRoleForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#deleteRoleModal').modal('hide');
                    rolesTable.ajax.reload(null, false);
                    showAlert('success', 'Role deleted successfully');
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while processing request');
            }
        });
    });
});

// Function to validate role form
function validateRoleForm(type) {
    var prefix = type === 'edit' ? 'edit' : '';
    var roleName = $('#' + prefix + 'RoleName').val();
    var permissions = $('input[name="' + prefix + 'permissions[]"]:checked').length;

    // Reset previous error states
    $('.form-group').removeClass('has-error');

    // Validate role name
    if (roleName.length < 3) {
        $('#' + prefix + 'RoleName').closest('.form-group').addClass('has-error');
        showAlert('error', 'Role name must be at least 3 characters long');
        return false;
    }

    // Validate permissions
    if (permissions === 0) {
        $('.permissions-container').closest('.form-group').addClass('has-error');
        showAlert('error', 'Please select at least one permission');
        return false;
    }

    return true;
}

// Function to edit role
function editRole(roleId) {
    $.ajax({
        url: 'php_action/fetchRole.php',
        type: 'POST',
        data: { roleId: roleId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#editRoleId').val(response.data.role_id);
                $('#editRoleName').val(response.data.role_name);
                $('#editDescription').val(response.data.description);

                // Reset all checkboxes
                $('input[name="editPermissions[]"]').prop('checked', false);

                // Check permissions
                if (response.data.permissions) {
                    response.data.permissions.forEach(function(permission) {
                        $('input[name="editPermissions[]"][value="' + permission + '"]').prop('checked', true);
                    });
                }

                $('#editRoleModal').modal('show');
            } else {
                showAlert('error', response.message);
            }
        },
        error: function() {
            showAlert('error', 'Error occurred while fetching role data');
        }
    });
}

// Function to delete role
function deleteRole(roleId) {
    // Check if role has assigned users
    $.ajax({
        url: 'php_action/checkRoleUsers.php',
        type: 'POST',
        data: { roleId: roleId },
        dataType: 'json',
        success: function(response) {
            if (response.hasUsers) {
                showAlert('error', 'Cannot delete role: There are users assigned to this role');
            } else {
                $('#deleteRoleId').val(roleId);
                $('#deleteRoleModal').modal('show');
            }
        },
        error: function() {
            showAlert('error', 'Error occurred while checking role users');
        }
    });
}

// Function to show alerts
function showAlert(type, message) {
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    var alert = '<div class="alert ' + alertClass + ' alert-dismissible" role="alert">' +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span></button>' + message + '</div>';
    
    $('.page-heading').after(alert);
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
} 