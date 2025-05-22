document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('roleSelect');
    const permissionsTable = document.getElementById('permissionsTable');
    const ipRestrictionForm = document.getElementById('ipRestrictionForm');
    const ipRestrictionsTable = document.getElementById('ipRestrictionsTable');

    // Handle role selection
    roleSelect.addEventListener('change', function() {
        const roleId = this.value;
        if (!roleId) {
            resetPermissionTable();
            return;
        }

        // Fetch permissions for selected role
        fetch(`php_action/granular_permissions.php?action=get_permissions&role_id=${roleId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updatePermissionTable(data.permissions);
                } else {
                    alert('Error fetching permissions: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error fetching permissions');
            });
    });

    // Handle permission assignment/revocation
    permissionsTable.addEventListener('click', function(e) {
        const target = e.target;
        if (target.classList.contains('assign-permission') || target.classList.contains('revoke-permission')) {
            const row = target.closest('tr');
            const permissionId = row.dataset.permissionId;
            const roleId = roleSelect.value;
            const action = target.classList.contains('assign-permission') ? 'assign' : 'revoke';

            fetch('php_action/granular_permissions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action === 'assign' ? 'assign_permission' : 'revoke_permission',
                    role_id: roleId,
                    permission_id: permissionId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updatePermissionStatus(row, action === 'assign');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating permission');
            });
        }
    });

    // Handle IP restriction form submission
    ipRestrictionForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('php_action/granular_permissions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'add_ip_restriction',
                role_id: formData.get('role_id'),
                ip_address: formData.get('ip_address'),
                is_allowed: formData.get('is_allowed')
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addIpRestrictionRow(data.restriction);
                this.reset();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error adding IP restriction');
        });
    });

    // Handle IP restriction deletion
    ipRestrictionsTable.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-restriction')) {
            const row = e.target.closest('tr');
            const restrictionId = row.dataset.restrictionId;

            if (confirm('Are you sure you want to delete this IP restriction?')) {
                fetch('php_action/granular_permissions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete_ip_restriction',
                        restriction_id: restrictionId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        row.remove();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting IP restriction');
                });
            }
        }
    });

    // Helper functions
    function resetPermissionTable() {
        const rows = permissionsTable.querySelectorAll('tr');
        rows.forEach(row => {
            const statusBadge = row.querySelector('.permission-status');
            const assignBtn = row.querySelector('.assign-permission');
            const revokeBtn = row.querySelector('.revoke-permission');

            statusBadge.className = 'badge bg-secondary permission-status';
            statusBadge.textContent = 'Not Assigned';
            assignBtn.style.display = 'none';
            revokeBtn.style.display = 'none';
        });
    }

    function updatePermissionTable(permissions) {
        const rows = permissionsTable.querySelectorAll('tr');
        rows.forEach(row => {
            const permissionId = parseInt(row.dataset.permissionId);
            const hasPermission = permissions.includes(permissionId);
            const statusBadge = row.querySelector('.permission-status');
            const assignBtn = row.querySelector('.assign-permission');
            const revokeBtn = row.querySelector('.revoke-permission');

            if (hasPermission) {
                statusBadge.className = 'badge bg-success permission-status';
                statusBadge.textContent = 'Assigned';
                assignBtn.style.display = 'none';
                revokeBtn.style.display = 'inline-block';
            } else {
                statusBadge.className = 'badge bg-secondary permission-status';
                statusBadge.textContent = 'Not Assigned';
                assignBtn.style.display = 'inline-block';
                revokeBtn.style.display = 'none';
            }
        });
    }

    function updatePermissionStatus(row, isAssigned) {
        const statusBadge = row.querySelector('.permission-status');
        const assignBtn = row.querySelector('.assign-permission');
        const revokeBtn = row.querySelector('.revoke-permission');

        if (isAssigned) {
            statusBadge.className = 'badge bg-success permission-status';
            statusBadge.textContent = 'Assigned';
            assignBtn.style.display = 'none';
            revokeBtn.style.display = 'inline-block';
        } else {
            statusBadge.className = 'badge bg-secondary permission-status';
            statusBadge.textContent = 'Not Assigned';
            assignBtn.style.display = 'inline-block';
            revokeBtn.style.display = 'none';
        }
    }

    function addIpRestrictionRow(restriction) {
        const row = document.createElement('tr');
        row.dataset.restrictionId = restriction.id;
        
        row.innerHTML = `
            <td>${restriction.role_name}</td>
            <td>${restriction.ip_address}</td>
            <td>
                <span class="badge ${restriction.is_allowed ? 'bg-success' : 'bg-danger'}">
                    ${restriction.is_allowed ? 'Allowed' : 'Denied'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-danger delete-restriction">Delete</button>
            </td>
        `;
        
        ipRestrictionsTable.appendChild(row);
    }
}); 