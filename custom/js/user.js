$(document).ready(function() {
	// Initialize DataTable with improved error handling
	var userTable = $('#manageUserTable').DataTable({
		'ajax': {
			'url': 'php_action/fetchUser.php',
			'type': 'GET',
			'dataSrc': function(response) {
				if (response.error) {
					showAlert('danger', response.message);
					return [];
				}
				return response; // Return the array directly
			}
		},
		'columns': [
			{ data: 'user_id' },
			{ data: 'username' },
			{ data: 'email' },
			{ data: 'role' },
			{ 
				data: 'status',
				render: function(data) {
					return `<span class="label label-${data === 1 ? 'success' : 'danger'}">
						${data === 1 ? 'Active' : 'Inactive'}</span>`;
				}
			},
			{ 
				data: null,
				render: function(data, type, row) {
					let buttons = '<div class="btn-group">';
					
					// Edit button
					if (hasEditPermission) {
						buttons += `
							<button class="btn btn-default btn-sm" onclick="editUser(${row.user_id})">
								<i class="glyphicon glyphicon-edit"></i>
							</button>`;
					}
					
					// Delete button
					if (hasDeletePermission) {
						buttons += `
							<button class="btn btn-danger btn-sm" onclick="removeUser(${row.user_id})">
								<i class="glyphicon glyphicon-trash"></i>
							</button>`;
					}
					
					buttons += '</div>';
					return buttons;
				}
			}
		],
		'order': [[0, 'asc']],
		'processing': true,
		'responsive': true
	});

	// Helper function to show alerts (same as role_management.js)
	function showAlert(type, message) {
		var alertHtml = `
			<div class="alert alert-${type} alert-dismissible">
				<button type="button" class="close" data-dismiss="alert">&times;</button>
				<strong>${type === 'danger' ? 'Error!' : 'Success!'}</strong> ${message}
			</div>`;
		$('.panel-body').prepend(alertHtml);
	}

	// Rest of your existing code for form handlers
	$('#addUserForm').submit(function(e) {
		e.preventDefault();
		
		// Show loading state
		const submitBtn = $(this).find('button[type="submit"]');
		const originalText = submitBtn.html();
		submitBtn.html('<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Creating...').prop('disabled', true);
		
		$.ajax({
			url: 'php_action/createUser.php',
			type: 'POST',
			data: $(this).serialize(),
			dataType: 'json',
			success: function(response) {
				console.log('Create user response:', response);
				if(response.success) {
					$('#addUserModal').modal('hide');
					userTable.ajax.reload();
					showAlert('success', response.messages);
					$('#addUserForm')[0].reset();
				} else {
					showAlert('danger', response.messages);
				}
			},
			error: function(xhr, status, error) {
				console.error('Create user error:', {xhr, status, error});
				showAlert('danger', 'Error creating user: ' + (xhr.responseJSON?.messages || error));
			},
			complete: function() {
				submitBtn.html(originalText).prop('disabled', false);
			}
		});
	});

	// Edit User Form Handler
	$('#editUserForm').submit(function(e) {
		e.preventDefault();
		
		$.ajax({
			url: 'php_action/editUser.php',
			type: 'POST',
			data: $(this).serialize(),
			dataType: 'json',
			success: function(response) {
				if(response.success) {
					$('#editUserModal').modal('hide');
					userTable.ajax.reload();
					showAlert('success', response.messages);
				} else {
					showAlert('danger', response.messages);
				}
			},
			error: function(xhr, status, error) {
				showAlert('danger', 'Error updating user: ' + error);
			}
		});
	});

	// Add User Button Click Handler
	$('#addUserBtn').click(function() {
		$('#addUserModal').modal('show');
	});
});

// Edit User Function
function editUser(userId) {
	$.ajax({
		url: 'php_action/getSelectedUser.php',
		type: 'POST',
		data: { userId: userId },
		dataType: 'json',
		success: function(response) {
			$('#editUserId').val(userId);
			$('#editUsername').val(response.username);
			$('#editEmail').val(response.email);
			$('#editRoleId').val(response.role_id);
			$('#editUserModal').modal('show');
		},
		error: function(xhr, status, error) {
			showAlert('danger', 'Error fetching user details: ' + error);
		}
	});
}

// Remove User Function
function removeUser(userId) {
	if(confirm('Are you sure you want to remove this user?')) {
		$.ajax({
			url: 'php_action/removeUser.php',
			type: 'POST',
			data: { userId: userId },
			dataType: 'json',
			success: function(response) {
				if(response.success) {
					$('#manageUserTable').DataTable().ajax.reload();
					showAlert('success', response.messages);
				} else {
					showAlert('danger', response.messages);
				}
			},
			error: function(xhr, status, error) {
				showAlert('danger', 'Error removing user: ' + error);
			}
		});
	}
}