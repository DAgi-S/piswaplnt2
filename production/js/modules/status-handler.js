// Status Handler Module
const StatusHandler = {
    // Status class mappings (matching database enum values)
    statusClasses: {
        'draft': 'default',
        'confirmed': 'primary',
        'inprogress': 'info',
        'completed': 'success',
        'cancelled': 'danger'
    },

    // Valid status transitions (matching database enum values)
    validTransitions: {
        'draft': ['confirmed', 'cancelled'],
        'confirmed': ['inprogress', 'cancelled'],
        'inprogress': ['completed', 'cancelled'],
        'completed': ['cancelled'],
        'cancelled': []
    },

    // Format status for display
    formatStatusDisplay: function(status) {
        status = String(status).toLowerCase().replace(/\s+/g, '').trim();
        switch(status) {
            case 'inprogress':
                return 'In Progress';
            default:
                return status.charAt(0).toUpperCase() + status.slice(1);
        }
    },

    // Get status badge HTML
    getStatusBadge: function(status) {
        status = String(status).toLowerCase().replace(/\s+/g, '').trim();
        const badgeClass = this.statusClasses[status] || 'default';
        const displayText = this.formatStatusDisplay(status);
        return `<span class="label label-${badgeClass}">${displayText}</span>`;
    },

    // Get action buttons based on current status
    getActionButtons: function(orderId, currentStatus) {
        let buttons = '<div class="action-buttons-container">';
        buttons += '<div class="btn-group-vertical" style="gap: 2px;">';
        
        // First row of buttons
        buttons += '<div class="btn-group" style="margin-bottom: 2px;">';
        
        // View button - always visible
        buttons += `
            <button type="button" class="btn btn-default btn-sm" onclick="ProductionOrder.view(${orderId})" title="View Details">
                <i class="fa fa-eye"></i>
            </button>`;

        // Status-specific buttons - first row
        currentStatus = String(currentStatus).toLowerCase().replace(/\s+/g, '').trim();
        
        switch(currentStatus) {
            case 'draft':
                buttons += `
                    <button type="button" class="btn btn-primary btn-sm" onclick="StatusHandler.changeStatus(${orderId}, 'confirmed')" title="Confirm Order">
                        <i class="fa fa-check"></i>
                    </button>`;
                break;
                
            case 'confirmed':
                buttons += `
                    <button type="button" class="btn btn-info btn-sm" onclick="StatusHandler.changeStatus(${orderId}, 'inprogress')" title="Start Production">
                        <i class="fa fa-play"></i>
                    </button>`;
                break;
                
            case 'inprogress':
                buttons += `
                    <button type="button" class="btn btn-success btn-sm" onclick="StatusHandler.changeStatus(${orderId}, 'completed')" title="Complete Order">
                        <i class="fa fa-check-circle"></i>
                    </button>`;
                break;
        }
        
        buttons += '</div>'; // End first row

        // Second row of buttons
        buttons += '<div class="btn-group">';
        
        // Additional status-specific buttons - second row
        if (currentStatus === 'inprogress') {
            buttons += `
                <button type="button" class="btn btn-primary btn-sm" onclick="ProductionOrder.updateProgress(${orderId})" title="Update Progress">
                    <i class="fa fa-refresh"></i>
                </button>`;
        }
        
        // Cancel button - available for all except completed and cancelled
        if (!['completed', 'cancelled'].includes(currentStatus)) {
            buttons += `
                <button type="button" class="btn btn-danger btn-sm" onclick="StatusHandler.changeStatus(${orderId}, 'cancelled')" title="Cancel Order">
                    <i class="fa fa-ban"></i>
                </button>`;
        }
        
        buttons += '</div>'; // End second row
        buttons += '</div>'; // End btn-group-vertical
        buttons += '</div>'; // End action-buttons-container
        return buttons;
    },

    // Change order status
    changeStatus: function(orderId, newStatus) {
        newStatus = String(newStatus).toLowerCase().replace(/\s+/g, '').trim();

        if (newStatus === 'completed') {
            // First fetch order details
            $.ajax({
                url: 'php_action/fetchSingleProductionOrder.php',
                type: 'POST',
                data: { id: orderId },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        const orderData = response.data;
                        // Show completion dialog with quantity input and materials
                        Swal.fire({
                            title: 'Complete Production Order',
                            html: `
                                <div class="completion-form">
                                    <div class="form-group">
                                        <label>Order Number: ${orderData.order_number}</label>
                                    </div>
                                    <div class="form-group">
                                        <label>Product: ${orderData.product_name}</label>
                                    </div>
                                    <div class="form-group">
                                        <label>Target Quantity: ${orderData.target_quantity}</label>
                                    </div>
                                    <div class="form-group">
                                        <label for="completedQuantity">Completed Quantity:</label>
                                        <input type="number" id="completedQuantity" class="form-control" 
                                               value="${orderData.target_quantity}" 
                                               min="0.01" step="0.01" required>
                                    </div>
                                    
                                    <hr>
                                    <h4>Material Consumption</h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Material</th>
                                                    <th>Required</th>
                                                    <th>Current Stock</th>
                                                    <th>Consumed</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${orderData.materials.map(material => `
                                                    <tr>
                                                        <td>${material.name}</td>
                                                        <td>${Number(material.required_quantity).toFixed(2)}</td>
                                                        <td>${Number(material.current_stock).toFixed(2)}</td>
                                                        <td>
                                                            <input type="number" 
                                                                   class="form-control material-consumed" 
                                                                   data-material-id="${material.material_id}"
                                                                   data-required="${material.required_quantity}"
                                                                   value="${material.required_quantity}"
                                                                   min="0" 
                                                                   max="${Number(material.current_stock) + Number(material.consumed_quantity)}"
                                                                   step="0.01" 
                                                                   required>
                                                        </td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="form-group">
                                        <small class="text-warning">Note: All materials must be fully consumed before completing the order.</small>
                                    </div>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'Complete Order',
                            cancelButtonText: 'Cancel',
                            preConfirm: () => {
                                const completedQty = parseFloat($('#completedQuantity').val());
                                if (isNaN(completedQty) || completedQty <= 0) {
                                    Swal.showValidationMessage('Please enter a valid quantity');
                                    return false;
                                }

                                // Collect material consumption data
                                const materialConsumption = [];
                                $('.material-consumed').each(function() {
                                    const materialId = $(this).data('material-id');
                                    const consumedQty = parseFloat($(this).val());
                                    if (isNaN(consumedQty) || consumedQty < 0) {
                                        Swal.showValidationMessage('Please enter valid material quantities');
                                        return false;
                                    }
                                    materialConsumption.push({
                                        material_id: materialId,
                                        consumed_quantity: consumedQty
                                    });
                                });

                                const targetQty = parseFloat(orderData.target_quantity);
                                if (completedQty < targetQty) {
                                    return new Promise((resolve) => {
                                        Swal.fire({
                                            title: 'Warning',
                                            html: `
                                                <div class="alert alert-warning">
                                                    <p>The completed quantity (${completedQty}) is less than the target quantity (${targetQty}).</p>
                                                    <p>Do you want to complete the order with a reduced quantity?</p>
                                                </div>
                                            `,
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonText: 'Yes, Complete',
                                            cancelButtonText: 'No, Cancel'
                                        }).then((result) => {
                                            resolve(result.isConfirmed ? {
                                                completedQty: completedQty,
                                                materialConsumption: materialConsumption
                                            } : false);
                                        });
                                    });
                                }
                                return {
                                    completedQty: completedQty,
                                    materialConsumption: materialConsumption
                                };
                            }
                        }).then((result) => {
                            if (result.isConfirmed && result.value) {
                                const { completedQty, materialConsumption } = result.value;
                                this.processStatusChange(orderId, newStatus, completedQty, materialConsumption);
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.messages || 'Failed to fetch order details'
                        });
                    }
                },
                error: (xhr, status, error) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to fetch order details: ' + error
                    });
                }
            });
        } else {
            // For other status changes, show normal confirmation
            let confirmMessage = '';
            switch(newStatus) {
                case 'confirmed':
                    confirmMessage = 'Are you sure you want to confirm this production order?';
                    break;
                case 'inprogress':
                    confirmMessage = 'Are you sure you want to start production for this order?';
                    break;
                case 'cancelled':
                    confirmMessage = 'WARNING: Are you sure you want to cancel this order?';
                    break;
                default:
                    confirmMessage = `Are you sure you want to change the status to ${this.formatStatusDisplay(newStatus)}?`;
            }

            Swal.fire({
                title: 'Confirm Status Change',
                text: confirmMessage,
                icon: newStatus === 'cancelled' ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, proceed',
                cancelButtonText: 'No, cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.processStatusChange(orderId, newStatus);
                }
            });
        }
    },

    // Process the status change
    processStatusChange: function(orderId, newStatus, completedQty = null, materialConsumption = null) {
        // Show loading state
        Swal.fire({
            title: 'Processing...',
            text: 'Updating order status',
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Normalize status to match backend expectations
        newStatus = String(newStatus).toLowerCase().replace(/\s+/g, '').trim();

        // Prepare request data
        const requestData = {
            orderId: orderId,
            status: newStatus
        };

        // Add completed quantity if provided
        if (completedQty !== null) {
            requestData.completedQuantity = completedQty;
        }

        // Add material consumption if provided
        if (materialConsumption !== null) {
            requestData.materialConsumption = JSON.stringify(materialConsumption);
        }

        // Send status change request
        return new Promise((resolve) => {
            $.ajax({
                url: 'php_action/changeProductionOrderStatus.php',
                type: 'POST',
                data: requestData,
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        // Close loading dialog
                        Swal.close();
                        
                        // Show success message with auto-close
                        toastr.options = {
                            closeButton: true,
                            progressBar: true,
                            timeOut: 3000
                        };
                        toastr.success(response.messages);
                        
                        // Wait for a short delay before reloading table
                        setTimeout(() => {
                            if (ProductionOrder.dataTable) {
                                ProductionOrder.dataTable.ajax.reload(null, false);
                            }
                            resolve({ success: true });
                        }, 500);
                    } else {
                        let errorMessage = response.messages || 'Failed to update order status';
                        
                        // Check if error is related to stock validation
                        if (errorMessage.includes('Stock cannot be less than reserved quantity')) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Stock Validation Error',
                                html: `
                                    <div class="alert alert-warning">
                                        <p><strong>Unable to proceed with status change</strong></p>
                                        <p>${errorMessage}</p>
                                        <hr>
                                        <p class="mb-0">Please ensure sufficient stock is available before changing the status.</p>
                                    </div>
                                `,
                                confirmButtonText: 'OK'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMessage
                            });
                        }
                        resolve({ success: false, error: errorMessage });
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Status change error:', error);
                    let errorMessage = 'Failed to update order status';
                    
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.messages) {
                            errorMessage = response.messages;
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }

                    // Check if error is related to stock validation
                    if (errorMessage.includes('Stock cannot be less than reserved quantity')) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Stock Validation Error',
                            html: `
                                <div class="alert alert-warning">
                                    <p><strong>Unable to proceed with status change</strong></p>
                                    <p>${errorMessage}</p>
                                    <hr>
                                    <p class="mb-0">Please ensure sufficient stock is available before changing the status.</p>
                                </div>
                            `,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage
                        });
                    }
                    resolve({ success: false, error: errorMessage });
                }
            });
        });
    },

    // Show completion summary
    showCompletionSummary: function(orderData) {
        if (!orderData || !orderData.order) return;
        
        const order = orderData.order;
        
        Swal.fire({
            title: 'Production Completed',
            html: `
                <div class="completion-summary">
                    <h4>Order Summary</h4>
                    <table class="table">
                        <tr>
                            <th>Order Number:</th>
                            <td>${order.order_number || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Product:</th>
                            <td>${order.product_name || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Completed Quantity:</th>
                            <td>${order.completed_quantity || '0'}</td>
                        </tr>
                        <tr>
                            <th>Completion Date:</th>
                            <td>${order.actual_completion_date || new Date().toLocaleDateString()}</td>
                        </tr>
                    </table>
                </div>`,
            icon: 'success',
            confirmButtonText: 'OK'
        });
    }
};

// Make it globally available
window.StatusHandler = StatusHandler;