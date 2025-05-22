$(document).ready(function() {
    // Cache DOM elements
    const $rolesList = $('#rolesList');
    const $permissionsContainer = $('#permissionsContainer');
    const $savePermissionsBtn = $('#savePermissionsBtn');
    const $roleModal = $('#roleModal');
    let selectedRoleId = null;

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Load roles on page load
    loadRoles();

    // Event Handlers
    $('#addRoleBtn').on('click', showAddRoleModal);
    $('#saveRoleBtn').on('click', saveRole);
    $('#confirmDeleteBtn').on('click', function() {
        if(selectedRoleId) deleteRole(selectedRoleId);
    });
    
    // Load all roles
    function loadRoles() {
        $.ajax({
            url: 'php_action/fetchRoles.php',
            method: 'GET',
            success: function(response) {
                if(response.success) {
                    renderRoles(response.data);
                } else {
                    showError('Error loading roles: ' + (response.messages || 'Unknown error'));
                    // Add a message to the roles list area
                    $rolesList.html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${response.messages || 'Error loading roles. Please check your permissions.'}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                const errorMsg = 'Server error while loading roles: ' + (error || 'Unknown error');
                showError(errorMsg);
                // Add a message to the roles list area
                $rolesList.html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        ${errorMsg}
                    </div>
                `);
            }
        });
    }

    // Render roles list
    function renderRoles(roles) {
        $rolesList.empty();
        roles.forEach(role => {
            const isSystemRole = role.role_id <= 2;
            const roleItem = $(`
                <div class="role-item" data-role-id="${role.role_id}">
                    <div class="role-info">
                        <div class="role-name">
                            ${role.role_name}
                            ${isSystemRole ? '<span class="badge badge-info ml-2">System Role</span>' : ''}
                        </div>
                        <small class="text-muted role-description">${role.description || 'No description available'}</small>
                    </div>
                    <div class="btn-group role-actions">
                        <button class="btn btn-sm btn-info edit-role" 
                                ${isSystemRole ? 'disabled' : ''} 
                                data-toggle="tooltip" 
                                title="${isSystemRole ? 'System roles cannot be edited' : 'Edit role'}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-role" 
                                ${isSystemRole ? 'disabled' : ''} 
                                data-toggle="tooltip" 
                                title="${isSystemRole ? 'System roles cannot be deleted' : 'Delete role'}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `);
            
            // Handle role selection
            roleItem.on('click', function(e) {
                if(!$(e.target).closest('.role-actions').length) {
                    $('.role-item').removeClass('active');
                    $(this).addClass('active');
                    loadRolePermissions(role.role_id);
                }
            });

            // Handle edit/delete for non-system roles
            if(!isSystemRole) {
                roleItem.find('.edit-role').on('click', (e) => {
                    e.stopPropagation();
                    showEditRoleModal(role);
                });
                
                roleItem.find('.delete-role').on('click', (e) => {
                    e.stopPropagation();
                    deleteRole(role.role_id);
                });
            }
            
            $rolesList.append(roleItem);
        });

        // Initialize tooltips for new elements
        $('[data-toggle="tooltip"]').tooltip();
    }

    // Load permissions for selected role
    function loadRolePermissions(roleId) {
        selectedRoleId = roleId;
        
        // Show loading state
        $permissionsContainer.html(`
            <div class="text-center my-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2">Loading permissions...</p>
            </div>
        `);
        
        $.ajax({
            url: 'php_action/fetchRolePermissions.php',
            method: 'GET',
            data: { role_id: roleId },
            success: function(response) {
                if(response.success) {
                    renderPermissions(response.data);
                    // Show save button only if user has permission to assign permissions
                    if(userPermissions.canAssignPermissions) {
                        $('#savePermissionsBtn').show();
                    }
                } else {
                    const errorMsg = response.messages || 'Error loading permissions';
                    showError(errorMsg);
                    $permissionsContainer.html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${errorMsg}
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                const errorMsg = 'Server error while loading permissions: ' + (error || 'Unknown error');
                showError(errorMsg);
                $permissionsContainer.html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        ${errorMsg}
                    </div>
                `);
            }
        });
    }

    // Render permissions
    function renderPermissions(permissions) {
        const permissionGroups = {
            'Account & Finance': [
                'account.view', 'account.create', 'account.edit', 'account.delete', 'account.approve',
                'transaction.view', 'transaction.create', 'transaction.edit', 'transaction.delete',
                'transaction.approve', 'transaction.reconcile',
                'finance.report.view', 'finance.report.generate', 'finance.report.export',
                'finance.audit.view', 'finance.audit.trail',
                'finance.analytics.view', 'finance.analytics.export',
                'finance.forecast.view', 'finance.forecast.generate',
                'finance.budget.view', 'finance.budget.create', 'finance.budget.edit', 'finance.budget.approve',
                'finance.annual.view', 'finance.annual.generate', 'finance.annual.export', 'finance.annual.archive'
            ],
            'Dashboard': [
                'dashboard.view', 'dashboard.customize',
                'dashboard.analytics.export', 'dashboard.analytics.view',
                'dashboard.reports.view', 'dashboard.reports.create', 'dashboard.reports.schedule',
                'dashboard.inventory.view', 'dashboard.sales.view',
                'dashboard.financial.overview', 'dashboard.financial.profit_loss', 'dashboard.financial.expenses',
                'dashboard.inventory.stock_level', 'dashboard.inventory.movements', 'dashboard.inventory.alerts',
                'view_dashboard', 'view_analytics', 'view_low_stock', 'view_revenue'
            ],
            'Digital Swap': [
                'digitalswap.view', 'digitalswap.create', 'digitalswap.edit', 'digitalswap.delete',
                'digitalswap.approve', 'digitalswap.status.view', 'digitalswap.status.update',
                'digitalswap.history.view', 'digitalswap.batch.manage',
                'digitalswap.analytics.view', 'digitalswap.analytics.export',
                'digitalswap.reports.view', 'digitalswap.reports.generate', 'digitalswap.reports.export'
            ],
            'Expense Management': [
                'expense.view', 'expense.create', 'expense.edit', 'expense.delete',
                'expense.approve.view', 'expense.approve.process', 'expense.approve.reject', 'expense.approve.delegate',
                'expense.category.view', 'expense.category.create', 'expense.category.edit', 'expense.category.delete',
                'expense.policy.view', 'expense.policy.create', 'expense.policy.edit', 'expense.policy.enforce',
                'expense.report.view', 'expense.report.create', 'expense.report.export', 'expense.report.schedule',
                'expense.analytics.view', 'expense.analytics.export',
                'expense.forecast.view', 'expense.forecast.generate'
            ],
            'Header Management': [
                'header.view', 'header.create', 'header.edit', 'header.delete',
                'header.main.view', 'header.main.edit', 'header.main.layout', 'header.main.style',
                'header.production.view', 'header.production.edit', 'header.production.layout', 'header.production.customize',
                'header.guest.view', 'header.guest.edit', 'header.guest.layout', 'header.guest.customize',
                'header.gps.view', 'header.gps.edit', 'header.gps.layout', 'header.gps.customize',
                'header.template.view', 'header.template.create', 'header.template.edit', 'header.template.delete'
            ],
            'Inventory': [
                'inventory.raw_materials.view', 'inventory.raw_materials.manage', 'inventory.raw_materials.edit', 'inventory.raw_materials.delete',
                'inventory.finished_goods.view', 'inventory.finished_goods.manage', 'inventory.finished_goods.edit', 'inventory.finished_goods.delete',
                'inventory.warehouse.view', 'inventory.warehouse.manage', 'inventory.warehouse.locations', 'inventory.warehouse.optimize',
                'inventory.transfer.create', 'inventory.transfer.approve', 'inventory.transfer.track', 'inventory.transfer.view',
                'inventory.bom.view', 'inventory.bom.create', 'inventory.bom.edit', 'inventory.bom.delete',
                'inventory.stock.adjust', 'inventory.stock.count', 'inventory.stock.reconcile',
                'inventory.reports.view', 'inventory.reports.generate', 'inventory.reports.export',
                'inventory.view_low_stock'
            ],
            'Invoice & Order': [
                'invoice.view', 'invoice.create', 'invoice.edit', 'invoice.delete',
                'invoice.print', 'invoice.email', 'invoice.payment.track', 'invoice.bulk_generate',
                'order.view', 'order.create', 'order.edit', 'order.delete',
                'order.process', 'order.status.update', 'order.payment.manage', 'order.shipping.manage',
                'order.report.export', 'order.bulk_update',
                'order.payment.view', 'order.shipping.view'
            ],
            'Letter Management': [
                'letter.view', 'letter.create', 'letter.edit', 'letter.delete', 'letter.approve',
                'letter.template.view', 'letter.template.create', 'letter.template.edit', 'letter.template.delete', 'letter.template.manage',
                'letter.gps.view', 'letter.gps.create', 'letter.gps.track', 'letter.gps.manage',
                'letter.process.review', 'letter.process.send', 'letter.process.track', 'letter.process.archive',
                'letter.reports.view', 'letter.reports.generate', 'letter.reports.export',
                'letter.analytics.view'
            ],
            'Product': [
                'product.view', 'product.create', 'product.edit', 'product.delete',
                'product.brand.view', 'product.brand.manage',
                'product.category.view', 'product.category.manage',
                'product.inventory.view', 'product.inventory.adjust',
                'product.pricing.view', 'product.pricing.manage',
                'product.import', 'product.export', 'product.bulk_update',
                'product.attributes.manage'
            ],
            'Production': [
                'production.view', 'production.create', 'production.edit', 'production.delete',
                'production.order.view', 'production.order.create', 'production.order.edit', 'production.order.delete', 'production.order.approve',
                'production.schedule.view', 'production.schedule.manage', 'production.schedule.optimize',
                'production.quality.view', 'production.quality.manage', 'production.quality.inspect', 'production.quality.approve',
                'production.waste.view', 'production.waste.manage', 'production.waste.report',
                'production.analytics.view', 'production.analytics.export',
                'production.reports.view', 'production.reports.generate', 'production.reports.export'
            ],
            'Purchase': [
                'purchase.view', 'purchase.create', 'purchase.edit', 'purchase.delete',
                'purchase.approve', 'purchase.payment.add', 'purchase.payment.edit', 'purchase.payment.view',
                'purchase.supplier.view', 'purchase.supplier.manage',
                'purchase.report.view', 'purchase.report.export',
                'purchase.settings.manage', 'purchase.tax.manage'
            ],
            'Quotation': [
                'quotation.view', 'quotation.create', 'quotation.edit', 'quotation.delete',
                'quotation.approve', 'quotation.email', 'quotation.print', 'quotation.convert',
                'quotation.status.update', 'quotation.report.view', 'quotation.report.export',
                'quotation.template.manage'
            ],
            'Settings': [
                'settings.access', 'settings.manage',
                'settings.brands.manage', 'settings.categories.manage',
                'settings.company.manage', 'settings.currency.manage',
                'settings.tax.manage', 'settings.units.manage',
                'settings.print.manage',
                'system.settings.view', 'system.settings.edit', 'system.settings.access',
                'edit_settings', 'view_settings', 'settings_access'
            ],
            'System Configuration': [
                'system.config.view', 'system.config.edit', 'system.config.manage',
                'system.email.view', 'system.email.edit', 'system.email.test', 'system.email.template.manage',
                'system.database.view', 'system.database.edit', 'system.database.backup', 'system.database.restore',
                'system.backup.view', 'system.backup.create', 'system.backup.restore', 'system.backup.schedule',
                'system.maintenance.view', 'system.maintenance.schedule', 'system.maintenance.execute', 'system.maintenance.logs',
                'system.security.view', 'system.security.edit', 'system.security.audit', 'system.security.manage'
            ],
            'User & Role Management': [
                'user.view', 'user.create', 'user.edit', 'user.delete',
                'user.activate', 'user.deactivate', 'user.reset_password',
                'role.view', 'role.create', 'role.edit', 'role.delete',
                'role.assign', 'role.revoke',
                'permission.view', 'permission.assign', 'permission.revoke', 'permission.manage',
                'guest.view', 'guest.create', 'guest.edit', 'guest.delete', 'guest.permissions',
                'access.view', 'access.manage', 'access.audit', 'access.report',
                'group.view', 'group.create', 'group.edit', 'group.delete', 'group.assign'
            ],
            'Integration & API': [
                'api.view', 'api.manage',
                'api.keys.view', 'api.keys.create', 'api.keys.revoke',
                'api.access.manage',
                'api.telegram.view', 'api.telegram.configure', 'api.telegram.manage', 'api.telegram.monitor',
                'api.webhook.view', 'api.webhook.create', 'api.webhook.edit', 'api.webhook.delete', 'api.webhook.logs',
                'api.integration.view', 'api.integration.configure', 'api.integration.test', 'api.integration.logs',
                'api.monitor.view', 'api.monitor.alerts', 'api.monitor.logs', 'api.monitor.metrics'
            ],
            'Data Management': [
                'data.import.view', 'data.import.execute', 'data.import.schedule', 'data.import.template',
                'data.import.validate', 'data.import.rollback',
                'data.export.view', 'data.export.execute', 'data.export.schedule',
                'data.export.template', 'data.export.customize',
                'data.validate.rules', 'data.validate.execute', 'data.validate.report',
                'data.integration.view', 'data.integration.configure', 'data.integration.execute', 'data.integration.monitor',
                'data.cleanup.view', 'data.cleanup.execute', 'data.cleanup.schedule', 'data.cleanup.archive'
            ]
        };

        // Create a map of permission details from the server response
        const permissionDetails = new Map(
            permissions.map(p => [p.permission_name, {
                id: parseInt(p.permission_id, 10),
                assigned: !!p.assigned,
                description: p.description
            }])
        );

        // Clear and build permissions container
        $permissionsContainer.empty();

        // Add select all checkbox with counter
        const selectAllDiv = $(`
            <div class="mb-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="selectAllPermissions">
                    <label class="custom-control-label" for="selectAllPermissions">
                        Select All Permissions
                        <span class="permissions-counter ml-2 text-muted"></span>
                    </label>
                </div>
            </div>
        `);
        $permissionsContainer.append(selectAllDiv);

        // Debug log permission details
        console.log('Permission Details:', {
            total: permissions.length,
            details: permissionDetails
        });

        // Render each permission group
        Object.entries(permissionGroups).forEach(([groupName, expectedPermissions]) => {
            // Filter existing permissions for this group
            const groupPermissions = expectedPermissions
                .filter(permName => permissionDetails.has(permName))
                .map(permName => ({
                    permission_id: permissionDetails.get(permName).id,
                    permission_name: permName,
                    assigned: permissionDetails.get(permName).assigned,
                    description: permissionDetails.get(permName).description
                }));

            if (groupPermissions.length > 0) {
                // Debug log group permissions
                console.log(`Group ${groupName}:`, groupPermissions);

                const groupDiv = $(`
                    <div class="permission-group">
                        <div class="permission-group-header">
                            <h5 class="permission-group-title">
                                ${groupName}
                                <small class="text-muted ml-2">(${groupPermissions.length} permissions)</small>
                            </h5>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input group-select-all" 
                                       id="selectAll_${groupName.replace(/\s+/g, '_')}">
                                <label class="custom-control-label" 
                                       for="selectAll_${groupName.replace(/\s+/g, '_')}">Select All ${groupName}</label>
                            </div>
                        </div>
                        <div class="permission-items"></div>
                    </div>
                `);

                const itemsContainer = groupDiv.find('.permission-items');
                groupPermissions.forEach(permission => {
                    // Ensure permission_id is a valid number
                    if (!permission.permission_id || isNaN(permission.permission_id)) {
                        console.error('Invalid permission ID:', permission);
                        return;
                    }

                    const permissionItem = $(`
                        <div class="permission-item">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" 
                                       class="custom-control-input permission-checkbox" 
                                       id="perm_${permission.permission_id}" 
                                       name="permissions[]" 
                                       value="${permission.permission_id}"
                                       data-group="${groupName}"
                                       ${permission.assigned ? 'checked' : ''}>
                                <label class="custom-control-label" for="perm_${permission.permission_id}">
                                    ${formatPermissionName(permission.permission_name)}
                                    ${permission.description ? 
                                        `<small class="text-muted d-block">${permission.description}</small>` : 
                                        ''}
                                </label>
                            </div>
                        </div>
                    `);
                    itemsContainer.append(permissionItem);
                });

                // Handle group select all
                groupDiv.find('.group-select-all').on('change', function() {
                    const isChecked = $(this).prop('checked');
                    groupDiv.find('.permission-checkbox').prop('checked', isChecked);
                    updateSelectAllCheckbox();
                });

                $permissionsContainer.append(groupDiv);
            }
        });

        // Add save permissions button
        $permissionsContainer.append(`
            <div class="mt-4">
                <button type="button" class="btn btn-primary" id="savePermissionsBtn">
                    <i class="fas fa-save"></i> Save Permissions
                </button>
            </div>
        `);

        // Initial state of checkboxes
        Object.keys(permissionGroups).forEach(updateGroupSelectAll);
        updateSelectAllCheckbox();

        // Handle select all functionality
        $('#selectAllPermissions').on('change', function() {
            const isChecked = $(this).prop('checked');
            $('.permission-checkbox').prop('checked', isChecked);
            $('.group-select-all').prop('checked', isChecked);
        });

        // Handle individual checkbox changes
        $('.permission-checkbox').on('change', function() {
            const group = $(this).data('group');
            updateGroupSelectAll(group);
            updateSelectAllCheckbox();
        });

        // Save permissions with enhanced error handling
        $('#savePermissionsBtn').on('click', function() {
            if (!selectedRoleId) {
                showError('No role selected. Please select a role first.');
                return;
            }

            // Show loading state
            const $saveBtn = $(this);
            const originalText = $saveBtn.html();
            $saveBtn.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

            // Get all checked permissions
            const selectedPermissions = [];
            $('.permission-checkbox:checked').each(function() {
                const permId = parseInt($(this).val(), 10);
                if (!isNaN(permId) && permId > 0) {
                    selectedPermissions.push(permId);
                }
            });

            // Debug log
            console.log('Saving permissions:', {
                roleId: selectedRoleId,
                permissionCount: selectedPermissions.length,
                permissions: selectedPermissions
            });

            // Make the AJAX call
            $.ajax({
                url: 'php_action/updateRolePermissions.php',
                method: 'POST',
                data: {
                    role_id: selectedRoleId,
                    permissions: selectedPermissions
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Server response:', response);
                    
                    if (response.success) {
                        showSuccess(response.message || 'Permissions updated successfully');
                        // Refresh permissions to ensure they're in sync
                        loadRolePermissions(selectedRoleId);
                    } else {
                        const errorMsg = response.message || 'Unknown error occurred while updating permissions';
                        console.error('Permission update failed:', errorMsg, response.debug || {});
                        showError(errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    
                    let errorMsg = 'Server error while updating permissions';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg = response.message || errorMsg;
                        if (response.debug) {
                            console.error('Debug info:', response.debug);
                        }
                    } catch (e) {
                        errorMsg += ': ' + (xhr.responseText || error);
                    }
                    
                    showError(errorMsg);
                },
                complete: function() {
                    // Restore button state
                    $saveBtn.prop('disabled', false).html(originalText);
                }
            });
        });
    }

    // Helper function to determine permission module
    function getPermissionModule(permissionName) {
        const moduleMap = {
            dashboard: 'Dashboard',
            product: 'Product',
            invoice: 'Invoice',
            order: 'Invoice',
            letter: 'GPS Letter',
            swap: 'Digital Swap',
            account: 'Account',
            user: 'User',
            role: 'User',
            settings: 'Settings',
            config: 'Settings',
            business: 'GPS Business',
            production: 'Production',
            guest: 'Guest',
            profit: 'GPS Business',
            gps: 'GPS Business',
            header: 'Header Management'
        };

        const matchingModule = Object.entries(moduleMap)
            .find(([key]) => permissionName.includes(key));
        
        return matchingModule ? matchingModule[1] : 'Other';
    }

    // Format permission name for display with improved readability
    function formatPermissionName(name) {
        return name
            .replace(/_/g, ' ')
            .split(' ')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    }

    // Update group select all checkbox state with animation
    function updateGroupSelectAll(groupName) {
        const $group = $(`.permission-group:contains('${groupName}')`);
        const $checkboxes = $group.find('.permission-checkbox');
        const totalPermissions = $checkboxes.length;
        const checkedPermissions = $checkboxes.filter(':checked').length;
        
        const $groupSelectAll = $group.find('.group-select-all');
        $groupSelectAll.prop('checked', totalPermissions === checkedPermissions);
        
        // Visual feedback
        if (totalPermissions === checkedPermissions) {
            $group.addClass('all-selected').fadeIn(200);
        } else {
            $group.removeClass('all-selected').fadeIn(200);
        }
    }

    // Update main select all checkbox state with improved feedback
    function updateSelectAllCheckbox() {
        const $allCheckboxes = $('.permission-checkbox');
        const totalPermissions = $allCheckboxes.length;
        const checkedPermissions = $allCheckboxes.filter(':checked').length;
        
        const $selectAll = $('#selectAllPermissions');
        $selectAll.prop('checked', totalPermissions === checkedPermissions);
        
        // Update counter display
        const $counter = $('.permissions-counter');
        if (!$counter.length) {
            $selectAll.after(`<span class="permissions-counter ml-2 text-muted">(${checkedPermissions}/${totalPermissions})</span>`);
        } else {
            $counter.text(`(${checkedPermissions}/${totalPermissions})`);
        }
    }

    // Show add role modal with enhanced validation
    function showAddRoleModal() {
        $('#roleModalTitle').text('Add New Role');
        $('#roleForm')[0].reset();
        $('#roleId').val('');
        $('#roleModal').modal('show');
    }

    // Show edit role modal with data validation
    function showEditRoleModal(role) {
        if (!role || !role.role_id) {
            showError('Invalid role data');
            return;
        }

        // Check if it's a system role
        if (role.role_id <= 2) {
            showError('System roles cannot be modified');
            return;
        }

        // Reset form and validation states
        const $roleForm = $('#roleForm');
        $roleForm[0].reset();
        $roleForm.find('.is-invalid').removeClass('is-invalid');
        $roleForm.find('.invalid-feedback').remove();

        // Set modal title and data
        $('#roleModalTitle').text('Edit Role');
        $('#roleName').val(role.role_name);
        $('#roleDescription').val(role.description || '');
        $('#roleId').val(role.role_id);

        // Show modal
        $('#roleModal').modal('show');
    }

    // Save role with enhanced validation and feedback
    function saveRole() {
        const $roleForm = $('#roleForm');
        const roleId = $('#roleId').val();
        const roleName = $('#roleName').val().trim();
        const roleDescription = $('#roleDescription').val().trim();

        // Clear previous validation states
        $roleForm.find('.is-invalid').removeClass('is-invalid');
        $roleForm.find('.invalid-feedback').remove();

        // Validate inputs
        if (!roleName) {
            $('#roleName').addClass('is-invalid')
                .after('<div class="invalid-feedback">Role name is required</div>');
            return;
        }

        // Show loading state
        const $saveBtn = $('#saveRoleBtn');
        const originalText = $saveBtn.text();
        $saveBtn.prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');

        // Prepare data
        const data = {
            roleName: roleName,
            roleDescription: roleDescription
        };

        // Determine if this is an edit or create operation
        const isEdit = roleId && roleId.trim() !== '';
        if (isEdit) {
            data.role_id = roleId;
        }

        // Make the AJAX call
        $.ajax({
            url: isEdit ? 'php_action/updateRole.php' : 'php_action/createRole.php',
            method: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#roleModal').modal('hide');
                    showSuccess(response.message);
                    loadRoles(); // Refresh the roles list
                } else {
                    showError(response.message || 'Error saving role');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr, status, error});
                let errorMsg = 'Server error while saving role';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {
                    errorMsg += ': ' + (error || 'Unknown error');
                }
                showError(errorMsg);
            },
            complete: function() {
                // Restore button state
                $saveBtn.prop('disabled', false).html(originalText);
            }
        });
    }

    // Delete role with confirmation
    function deleteRole(roleId) {
        if (!roleId) {
            showError('Role ID is required');
            return;
        }

        // Check if it's a system role
        if (roleId <= 2) {
            showError('System roles cannot be deleted');
            return;
        }

        // Show confirmation dialog
        Swal.fire({
            title: 'Delete Role',
            text: 'Are you sure you want to delete this role? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Make the AJAX call
                $.ajax({
                    url: 'php_action/deleteRole.php',
                    method: 'POST',
                    data: { role_id: roleId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Clear selected role if it was deleted
                            if (selectedRoleId === roleId) {
                                selectedRoleId = null;
                                $('#permissionsContainer').empty().append(`
                                    <div class="text-center text-muted">
                                        <p>Select a role to view and edit permissions</p>
                                    </div>
                                `);
                                $('#savePermissionsBtn').hide();
                            }
                            
                            // Refresh roles list
                            loadRoles();
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            throw new Error(response.message || 'Error deleting role');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete Role Error:', {xhr, status, error});
                        let errorMsg = 'Error deleting role';
                        
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMsg = response.message || errorMsg;
                        } catch (e) {
                            errorMsg += ': ' + (error || 'Unknown error');
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMsg
                        });
                    }
                });
            }
        });
    }

    // Show success message with enhanced styling
    function showSuccess(message) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: message,
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end',
            timerProgressBar: true
        });
    }

    // Show error message with enhanced styling
    function showError(message) {
        console.error('Error:', message);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            confirmButtonColor: '#d33',
            toast: true,
            position: 'top-end',
            showConfirmButton: true,
            timer: 5000,
            timerProgressBar: true
        });
    }
}); 