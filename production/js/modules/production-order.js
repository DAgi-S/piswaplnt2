// Production Order Module
const ProductionOrder = {
    // DataTable instance
    dataTable: null,

    // Initialize module
    init: function() {
        // Add modal styles
        const modalStyles = `
            #viewProductionOrderModal .modal-header {
                border-bottom: 1px solid #eee;
                padding: 15px 20px;
            }
            #viewProductionOrderModal .modal-header .order-status {
                margin-top: 5px;
            }
            #viewProductionOrderModal .modal-body {
                padding: 20px;
            }
            #viewProductionOrderModal .modal-title {
                margin: 0;
                font-size: 18px;
                font-weight: 600;
            }
            #viewProductionOrderModal h5 {
                margin-top: 0;
                margin-bottom: 15px;
                font-size: 16px;
                font-weight: 600;
                color: #333;
            }
            #viewProductionOrderModal .table {
                margin-bottom: 0;
                width: 100% !important;
            }
            #viewProductionOrderModal .table > thead > tr > th {
                background: #f8f9fa;
                border-bottom: 2px solid #dee2e6;
                vertical-align: middle;
            }
            #viewProductionOrderModal .table > tbody > tr > td {
                vertical-align: middle;
            }
            #viewProductionOrderModal .progress {
                margin-bottom: 0;
                height: 18px;
                border-radius: 4px;
            }
            #viewProductionOrderModal .progress-bar {
                line-height: 18px;
                font-size: 12px;
            }
            #viewProductionOrderModal .progress-bar.bg-success {
                background-color: #28a745;
            }
            #viewProductionOrderModal .progress-bar.bg-warning {
                background-color: #ffc107;
            }
            #viewProductionOrderModal .mt-4 {
                margin-top: 20px;
            }
            #viewProductionOrderModal .text-muted {
                color: #777;
            }
            #viewProductionOrderModal .label {
                display: inline-block;
                padding: 0.25em 0.4em;
                font-size: 75%;
                font-weight: 700;
                line-height: 1;
                text-align: center;
                white-space: nowrap;
                vertical-align: baseline;
                border-radius: 0.25rem;
                margin-left: 5px;
            }
            #viewProductionOrderModal .label-danger {
                background-color: #dc3545;
                color: white;
            }
            #viewProductionOrderModal .label-warning {
                background-color: #ffc107;
                color: #000;
            }
            #viewProductionOrderModal .label-success {
                background-color: #28a745;
                color: white;
            }
            #viewProductionOrderModal .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        `;
        
        // Add styles to head
        const styleElement = document.createElement('style');
        styleElement.textContent = modalStyles;
        document.head.appendChild(styleElement);

        this.initDataTable();
        
        // Initialize add order modal
        $('#addProductionOrderBtn').on('click', function() {
            $('#addProductionOrderModal').modal('show');
        });

        // Initialize add order form when modal is shown
        $('#addProductionOrderModal').on('shown.bs.modal', function() {
            ProductionOrder.initAddOrder();
        });

        // Reset form when modal is hidden
        $('#addProductionOrderModal').on('hidden.bs.modal', function() {
            const $form = $('#submitProductionOrderForm');
            ProductionOrder.resetAddOrderForm($form);
        });

        // Handle view modal events
        $(document).on('hidden.bs.modal', '#viewProductionOrderModal', function() {
            if (ProductionOrder.dataTable) {
                ProductionOrder.dataTable.columns.adjust();
            }
        });
    },

    // Initialize DataTable with real-time monitoring
    initDataTable: function() {
        // Destroy existing instance if it exists
        if ($.fn.DataTable.isDataTable('#productionOrdersTable')) {
            $('#productionOrdersTable').DataTable().destroy();
        }

        // Clear existing table content
        $('#productionOrdersTable tbody').empty();

        // Initialize new DataTable instance
        this.dataTable = $('#productionOrdersTable').DataTable({
            'ajax': {
                'url': 'php_action/fetchProductionOrders.php',
                'type': 'POST',
                'dataSrc': function(json) {
                    if (!json.data) {
                        console.error('Invalid response format:', json);
                        toastr.error('Error: Invalid server response format');
                        return [];
                    }
                    return json.data;
                },
                'error': function(xhr, error, thrown) {
                    console.error('DataTables error:', error);
                    console.error('Server response:', xhr.responseText);
                    toastr.error('Error loading production orders: ' + error);
                }
            },
            'order': [[0, 'desc']],
            'columns': [
                { data: 'order_number', width: '11%', className: 'text-nowrap' },
                { data: 'product_name', width: '10%', className: 'text-nowrap' },
                { 
                    data: 'status',
                    width: '8%',
                    className: 'text-center text-nowrap',
                    render: function(data) {
                        return StatusHandler.getStatusBadge(data);
                    }
                },
                { data: 'target_quantity', width: '8%', className: 'text-right text-nowrap' },
                { 
                    data: 'completed_quantity',
                    width: '12%',
                    className: 'text-right text-nowrap',
                    render: function(data, type, row) {
                        const progress = (parseFloat(data) / parseFloat(row.target_quantity)) * 100;
                        return `
                            <div>
                                ${data}
                                <div class="progress" style="height: 5px; margin-top: 5px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: ${Math.min(100, progress)}%"
                                         aria-valuenow="${Math.round(progress)}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                },
                { data: 'start_date', width: '10%', className: 'text-center text-nowrap' },
                { data: 'expected_completion_date', width: '10%', className: 'text-center text-nowrap' },
                { data: 'created_by', width: '10%', className: 'text-center text-nowrap' },
                {
                    data: null,
                    width: '17%',
                    orderable: false,
                    className: 'text-center action-buttons text-nowrap',
                    render: function(data, type, row) {
                        return StatusHandler.getActionButtons(row.id, row.status);
                    }
                }
            ],
            'pageLength': 10,
            'responsive': true,
            'stateSave': true,
            'processing': true,
            'autoWidth': false,
            'scrollX': true,
            'dom': "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            'language': {
                'processing': '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>',
                'emptyTable': 'No production orders available',
                'zeroRecords': 'No matching records found'
            },
            'drawCallback': function() {
                // Update monitoring indicators
                ProductionOrder.updateMonitoringIndicators();
                
                // Ensure action buttons are visible
                $('.action-buttons').css({
                    'min-width': '120px',
                    'white-space': 'normal',
                    'text-align': 'center',
                    'padding': '4px'
                });

                // Add fixed styling to the table
                $('#productionOrdersTable').css({
                    'width': '100%',
                    'margin-bottom': '1rem',
                    'table-layout': 'fixed'
                });

                $('.action-buttons-container').css({
                    'display': 'inline-block',
                    'text-align': 'center',
                    'width': '100%'
                });
                
                $('.btn-group-vertical').css({
                    'display': 'inline-flex',
                    'flex-direction': 'column',
                    'align-items': 'center',
                    'gap': '2px'
                });
                
                $('.btn-group').css({
                    'display': 'inline-flex',
                    'flex-wrap': 'nowrap',
                    'gap': '2px'
                });
                
                // Adjust button sizes
                $('.btn-group .btn').css({
                    'padding': '3px 6px',
                    'font-size': '12px',
                    'line-height': '1.2',
                    'flex-shrink': '0',
                    'margin': '0'
                });
            },
            'createdRow': function(row, data, dataIndex) {
                $(row).find('td').css({
                    'vertical-align': 'middle',
                    'padding': '0.75rem',
                    'overflow': 'hidden',
                    'text-overflow': 'ellipsis'
                });
                
                // Ensure action buttons cell has proper styling
                $(row).find('td:last-child').css({
                    'min-width': '100px',
                    'white-space': 'normal',
                    'padding': '4px',
                    'text-align': 'center'
                });
            },
            'initComplete': function() {
                // Fix column widths
                this.api().columns.adjust();
                
                // Add fixed styling to the table
                $('#productionOrdersTable').css({
                    'width': '100%',
                    'margin-bottom': '1rem',
                    'table-layout': 'fixed'
                });

                // Add fixed styling to the table header
                $('#productionOrdersTable thead th').css({
                    'background-color': '#f8f9fa',
                    'border-bottom': '2px solid #dee2e6',
                    'vertical-align': 'middle',
                    'padding': '0.75rem',
                    'white-space': 'nowrap',
                    'overflow': 'hidden',
                    'text-overflow': 'ellipsis'
                });
                
                // Ensure last column (action buttons) has enough width
                $('#productionOrdersTable thead th:last-child').css({
                    'min-width': '170px',
                    'width': '17%'
                });

                // Force column width recalculation
                setTimeout(() => {
                    this.api().columns.adjust();
                }, 100);
            }
        });

        return this.dataTable;
    },

    // Update monitoring indicators
    updateMonitoringIndicators: function() {
        $.ajax({
            url: 'php_action/fetchMonitoringStatus.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    // Update stock status indicators
                    response.data.stockAlerts.forEach(alert => {
                        toastr.warning(`Low stock alert: ${alert.material_name} (${alert.current_stock} remaining)`);
                    });

                    // Update order progress indicators
                    response.data.delayedOrders.forEach(order => {
                        toastr.error(`Delayed order: ${order.order_number} (Expected: ${order.expected_completion_date})`);
                    });
                }
            }
        });
    },

    // View production order details with enhanced monitoring
    view: function(orderId) {
        // Create modal if it doesn't exist
        if (!$('#viewProductionOrderModal').length) {
            $('body').append(`
                <div class="modal fade" id="viewProductionOrderModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-body">
                                <div class="text-center">
                                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                                    <p>Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }

        // Show loading state
        $('#viewProductionOrderModal').modal('show');

        $.ajax({
            url: 'php_action/fetchSingleProductionOrder.php',
            type: 'POST',
            data: { id: orderId },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    const order = response.data;
                    order.status_html = StatusHandler.getStatusBadge(order.status);
                    
                    // Calculate overall progress
                    const progress = order.target_quantity > 0 
                        ? (order.completed_quantity / order.target_quantity) * 100 
                        : 0;

                    // Create modal content with monitoring features
                    const modalContent = `
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h4 class="modal-title">Production Order Details</h4>
                                <div class="order-status mt-2">${order.status_html}</div>
                                <small class="text-muted">${order.order_number}</small>
                            </div>
                            <div class="modal-body" id="printableArea">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="order-info">
                                            <h5>Order Information</h5>
                                            <table class="table">
                                                <tr>
                                                    <td>Product:</td>
                                                    <td>${order.product_name}</td>
                                                </tr>
                                                <tr>
                                                    <td>Start Date:</td>
                                                    <td>${order.start_date || 'Not started'}</td>
                                                </tr>
                                                <tr>
                                                    <td>Expected Completion:</td>
                                                <td>
                                                    ${order.expected_completion_date || 'Not set'}
                                                    ${order.is_delayed ? '<span class="label label-danger">Delayed</span>' : ''}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Time Remaining:</td>
                                                <td>${order.time_remaining || 'N/A'}</td>
                                            </tr>
                                            <tr>
                                                <td>Created By:</td>
                                                <td>${order.created_by || 'System'}</td>
                                            </tr>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="production-progress">
                                            <h5>Production Progress</h5>
                                            <div class="progress" style="height: 25px; margin: 10px 0;">
                                            <div class="progress-bar ${progress < 50 ? 'bg-warning' : 'bg-success'}" 
                                                 role="progressbar" 
                                                     style="width: ${Math.min(100, progress)}%"
                                                     aria-valuenow="${Math.round(progress)}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    ${Math.round(progress)}%
                                                </div>
                                            </div>
                                            <div class="text-center">
                                                <strong>${order.completed_quantity || '0'}</strong> / 
                                                <span>${order.target_quantity}</span> units
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="material-consumption mt-4">
                                    <h5>Material Consumption</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Material</th>
                                                <th>Required</th>
                                                <th>Consumed</th>
                                                <th>Available</th>
                                                <th>Progress</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${Array.isArray(order.materials) && order.materials.length > 0 ? 
                                                order.materials.map(material => {
                                                    const required = parseFloat(material.required_quantity) || 0;
                                                    const consumed = parseFloat(material.consumed_quantity) || 0;
                                                    const available = parseFloat(material.current_stock) || 0;
                                                    const progress = required > 0 ? (consumed / required) * 100 : 0;
                                                
                                                return `
                                                    <tr>
                                                            <td>${material.name || 'N/A'}</td>
                                                        <td>${required.toFixed(2)}</td>
                                                        <td>${consumed.toFixed(2)}</td>
                                                            <td>
                                                                ${available.toFixed(2)}
                                                                ${available < required ? 
                                                                '<span class="label label-danger">Low Stock</span>' : ''}
                                                            </td>
                                                        <td>
                                                            <div class="progress">
                                                                <div class="progress-bar" role="progressbar" 
                                                                     style="width: ${Math.min(100, progress)}%">
                                                                    ${Math.round(progress)}%
                                                                </div>
                                                            </div>
                                                        </td>
                                                            <td>${ProductionOrder.getMaterialStatusBadge(material)}</td>
                                                    </tr>
                                                `;
                                                }).join('') : 
                                                '<tr><td colspan="6" class="text-center">No materials found</td></tr>'
                                            }
                                        </tbody>
                                    </table>
                                </div>
                                </div>

                                    <div class="progress-history mt-4">
                                        <h5>Production Progress History</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Date & Time</th>
                                                    <th>Quantity</th>
                                                    <th>Notes</th>
                                                    <th>Created By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            ${Array.isArray(order.progress) && order.progress.length > 0 ? 
                                                order.progress.map(p => `
                                                    <tr>
                                                        <td>${p.created_at || 'N/A'}</td>
                                                        <td>${parseFloat(p.quantity || 0).toFixed(2)}</td>
                                                        <td>${p.notes || '-'}</td>
                                                        <td>${p.created_by || 'System'}</td>
                                                    </tr>                                                `).join('') : 
                                                '<tr><td colspan="4" class="text-center">No progress records found</td></tr>'
                                            }
                                            </tbody>
                                        </table>
                                    </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-info" onclick="ProductionOrder.printPreview('${order.order_number}', ${orderId})">
                                <i class="fa fa-print"></i> Print
                            </button>
                        </div>
                    `;

                    // Update modal content
                    $('#viewProductionOrderModal .modal-content').html(modalContent);

                    // Adjust main table after modal is shown
                    setTimeout(() => {
                        if (ProductionOrder.dataTable) {
                            ProductionOrder.dataTable.columns.adjust();
                        }
                    }, 100);

                    // Start real-time monitoring for this order
                    ProductionOrder.startOrderMonitoring(orderId);
                } else {
                    toastr.error(response.messages);
                    $('#viewProductionOrderModal').modal('hide');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching order details:', error);
                toastr.error('Error fetching production order details');
                $('#viewProductionOrderModal').modal('hide');
            }
        });
    },

    // Start real-time monitoring for a specific order
    startOrderMonitoring: function(orderId) {
        if(this.monitoringInterval) {
            clearInterval(this.monitoringInterval);
        }

        this.monitoringInterval = setInterval(() => {
            $.ajax({
                url: 'php_action/fetchSingleProductionOrder.php',
                type: 'POST',
                data: { id: orderId },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        // Update progress and material consumption in real-time
                        const order = response.data;
                        $('.production-progress .progress-bar').css('width', `${Math.min(100, order.progress)}%`);
                        $('.production-progress .progress-bar').text(`${Math.round(order.progress)}%`);
                        
                        // Update material consumption
                        order.materials.forEach(material => {
                            const row = $(`.material-consumption tr[data-material-id="${material.id}"]`);
                            if(row.length) {
                                const progress = (material.consumed_quantity / material.required_quantity) * 100;
                                row.find('.progress-bar').css('width', `${Math.min(100, progress)}%`);
                                row.find('.progress-bar').text(`${Math.round(progress)}%`);
                                row.find('td:eq(2)').text(material.consumed_quantity.toFixed(2));
                            }
                        });
                    }
                }
            });
        }, 30000); // Update every 30 seconds
    },

    // Get material status badge
    getMaterialStatusBadge: function(material) {
        const required = Number(material.required_quantity);
        const consumed = Number(material.consumed_quantity);
        const available = Number(material.current_stock);
        
        if(consumed >= required) {
            return '<span class="label label-success">Completed</span>';
        } else if(available < required - consumed) {
            return '<span class="label label-danger">Insufficient Stock</span>';
        } else if(consumed > 0) {
            return '<span class="label label-warning">In Progress</span>';
        }
        return '<span class="label label-default">Pending</span>';
    },

    // Update production progress
    updateProgress: function(orderId) {
        // First fetch order details
        $.ajax({
            url: 'php_action/fetchSingleProductionOrder.php',
            type: 'POST',
            data: { id: orderId },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    const orderData = response.data;
                    
                    // Show progress update dialog
                    Swal.fire({
                        title: 'Update Production Progress',
                        html: `
                            <div class="progress-update-form">
                                <div class="form-group">
                                    <label>Order Number: ${orderData.order_number}</label>
                                </div>
                                <div class="form-group">
                                    <label>Product: ${orderData.product_name}</label>
                                </div>
                                <div class="form-group">
                                    <label>Current Progress: ${orderData.completed_quantity} / ${orderData.target_quantity}</label>
                                </div>
                                <div class="form-group">
                                    <label for="progressQuantity">Additional Quantity Completed:</label>
                                    <input type="number" id="progressQuantity" class="form-control" 
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
                                                <th>Previously Used</th>
                                                <th>Additional Used</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${orderData.materials.map(material => `
                                                <tr>
                                                    <td>${material.name}</td>
                                                    <td>${Number(material.required_quantity).toFixed(2)}</td>
                                                    <td>${Number(material.consumed_quantity).toFixed(2)}</td>
                                                    <td>
                                                        <input type="number" 
                                                               class="form-control material-used" 
                                                               data-material-id="${material.material_id}"
                                                               data-required="${material.required_quantity}"
                                                               data-consumed="${material.consumed_quantity}"
                                                               min="0" 
                                                               max="${Number(material.current_stock)}"
                                                               step="0.01" 
                                                               required>
                                                    </td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                                <div class="form-group">
                                    <label for="progressNotes">Notes:</label>
                                    <textarea id="progressNotes" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Update Progress',
                        cancelButtonText: 'Cancel',
                        preConfirm: () => {
                            const progressQty = parseFloat($('#progressQuantity').val());
                            if (isNaN(progressQty) || progressQty <= 0) {
                                Swal.showValidationMessage('Please enter a valid quantity');
                                return false;
                            }

                            // Collect material usage data
                            const materialUsage = [];
                            let isValid = true;
                            $('.material-used').each(function() {
                                const materialId = $(this).data('material-id');
                                const usedQty = parseFloat($(this).val());
                                const requiredQty = parseFloat($(this).data('required'));
                                const consumedQty = parseFloat($(this).data('consumed'));
                                
                                if (isNaN(usedQty) || usedQty < 0) {
                                    Swal.showValidationMessage('Please enter valid material quantities');
                                    isValid = false;
                                    return false;
                                }
                                
                                materialUsage.push({
                                    material_id: materialId,
                                    quantity_used: usedQty
                                });
                            });

                            if (!isValid) return false;

                            return {
                                quantity: progressQty,
                                materials: materialUsage,
                                notes: $('#progressNotes').val()
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed && result.value) {
    // Submit progress update
        $.ajax({
            url: 'php_action/updateProductionProgress.php',
            type: 'POST',
                                data: {
                                    production_order_id: orderId,
                                    quantity: result.value.quantity,
                                    materials: JSON.stringify(result.value.materials),
                                    notes: result.value.notes
                                },
            dataType: 'json',
            success: function(response) {
                                    if (response.success) {
                    toastr.success(response.messages);
                                        if (ProductionOrder.dataTable) {
                                            ProductionOrder.dataTable.ajax.reload(null, false);
                    }
                } else {
                    toastr.error(response.messages);
                }
            },
                                error: function(xhr, status, error) {
                                    toastr.error('Error updating progress: ' + error);
                                    console.error('Progress update error:', error);
                                    console.error('Server response:', xhr.responseText);
                                }
                            });
                        }
                    });
                } else {
                    toastr.error(response.messages || 'Failed to fetch order details');
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Error fetching order details: ' + error);
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
            }
        });
    },

    // Clean up resources
    cleanup: function() {
        if (this.monitoringInterval) {
            clearInterval(this.monitoringInterval);
        }
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
        if (this.dataTable) {
            this.dataTable.destroy();
            this.dataTable = null;
        }
    },

    // Initialize Add Order functionality
    initAddOrder: function() {
        // Remove any existing event handlers
        $('#submitProductionOrderForm').off('submit');
        $('#product_id').off('change');

        // Initialize product selection handler
        $('#product_id').on('change', function() {
            const productId = $(this).val();
            if (productId) {
                ProductionOrder.fetchProductMaterials(productId);
            } else {
                $('#materialsTable tbody').html('<tr><td colspan="5" class="text-center">Please select a product</td></tr>');
            }
        });

        // Initialize form submission handler with proper event delegation
        $('#submitProductionOrderForm').on('submit', function(e) {
            e.preventDefault();
            
            // Check for form validity before proceeding
            if (!this.checkValidity()) {
                // If the form is invalid, trigger browser's built-in validation
                return false;
            }
            
            // Use our custom validation too
            if (!ProductionOrder.validateOrderForm($(this))) {
                return false;
            }
            
            ProductionOrder.submitOrder(this);
            return false;
        });

        // Initialize date pickers with default values
        const today = new Date().toISOString().split('T')[0];
        $('#startDate').val(today);
        
        // Set default completion date to 7 days from now
        const nextWeek = new Date();
        nextWeek.setDate(nextWeek.getDate() + 7);
        $('#completionDate').val(nextWeek.toISOString().split('T')[0]);
    },

    // Fetch product materials
    fetchProductMaterials: function(productId) {
        $.ajax({
            url: 'php_action/fetchProductBOM.php',
            type: 'POST',
            data: { product_id: productId },
            dataType: 'json',
            beforeSend: function() {
                $('#materialsTable tbody').html('<tr><td colspan="4" class="text-center">Loading materials...</td></tr>');
            },
            success: function(response) {
                if(response.success) {
                    let html = '';
                    if(response.data.length > 0) {
                        response.data.forEach(material => {
                            const requiredQty = parseFloat(material.quantity_required).toFixed(2);
                            const currentStock = parseFloat(material.current_stock).toFixed(2);
                            const wastage = parseFloat(material.wastage_percent || 0).toFixed(2);
                            
                            html += `
                                <tr data-material-id="${material.id}">
                                    <td>${material.material_code} - ${material.name}</td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" class="form-control required-quantity" 
                                                   name="quantities[]" value="${requiredQty}" 
                                                   min="0" step="0.01" required readonly>
                                            ${wastage > 0 ? `<span class="input-group-addon">+${wastage}% wastage</span>` : ''}
                                        </div>
                                        <input type="hidden" name="materials[]" value="${material.id}">
                                    </td>
                                    <td>
                                        ${currentStock}
                                        <div class="stock-status mt-1">
                                            <small class="${currentStock >= requiredQty ? 'text-success' : 'text-danger'}">
                                                <i class="fa fa-${currentStock >= requiredQty ? 'check' : 'warning'}"></i>
                                                ${currentStock >= requiredQty ? 'Sufficient' : 'Insufficient'} stock
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar ${currentStock >= requiredQty ? 'progress-bar-success' : 'progress-bar-danger'}" 
                                                 role="progressbar" 
                                                 style="width: ${Math.min(100, (currentStock / requiredQty) * 100)}%">
                                                ${Math.round((currentStock / requiredQty) * 100)}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        html = '<tr><td colspan="4" class="text-center">No materials defined for this product</td></tr>';
                    }
                    $('#materialsTable tbody').html(html);
                } else {
                    toastr.error(response.messages);
                    $('#materialsTable tbody').html('<tr><td colspan="4" class="text-center text-danger">Error loading materials</td></tr>');
                }
            },
            error: function() {
                toastr.error('Error fetching product materials');
                $('#materialsTable tbody').html('<tr><td colspan="4" class="text-center text-danger">Error loading materials</td></tr>');
            }
        });
    },

    // Submit production order
    submitOrder: function(form) {
        const $form = $(form);
        const $submitBtn = $('#createProductionOrderBtn');
        
        // Prevent duplicate submissions
        if ($submitBtn.prop('disabled')) {
            return false;
        }
        
        // Validate form
        if (!this.validateOrderForm($form)) {
            return false;
        }

        // Add CSRF token if available
        const csrfToken = $('input[name="csrf_token"]').val();
        const formData = new FormData(form);
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }
        
        // Disable submit button and show loading state
        $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Creating...');

        // Submit form data
        $.ajax({
            url: 'php_action/createProductionOrder.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addProductionOrderModal').modal('hide');
                    if(ProductionOrder.dataTable) {
                        ProductionOrder.dataTable.ajax.reload(null, false);
                    }
                    toastr.success(response.messages);
                    ProductionOrder.resetAddOrderForm($form);
                } else {
                    toastr.error(response.messages);
                }
            },
            error: function(xhr, status, error) {
                // Improved error handling
                let errorMessage = 'Error creating production order';
                
                if (error === 'parsererror') {
                    errorMessage += ': Invalid server response format';
                    console.error('JSON parse error. Raw response:', xhr.responseText);
                } else {
                    errorMessage += ': ' + error;
                }
                
                toastr.error(errorMessage);
                console.error('Form submission error:', error);
                
                // Try to log the actual response for debugging
                try {
                    console.error('Server response:', xhr.responseText);
                } catch (e) {
                    console.error('Could not log server response');
                }
            },
            complete: function() {
                // Re-enable submit button after response is received
                $submitBtn.prop('disabled', false).html('Create Order');
            }
        });

        // Prevent form from submitting normally
        return false;
    },

    // Validate order form
    validateOrderForm: function($form) {
        let isValid = true;
        
        // Check required fields
        $form.find('[required]').each(function() {
            if(!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        // Validate dates
        const startDate = new Date($('#startDate').val());
        const completionDate = new Date($('#completionDate').val());
        if(completionDate < startDate) {
            toastr.error('Expected completion date cannot be earlier than start date');
            $('#completionDate').addClass('is-invalid');
            isValid = false;
        }

        // Validate target quantity
        const targetQty = parseFloat($('#targetQuantity').val());
        if(isNaN(targetQty) || targetQty <= 0) {
            toastr.error('Target quantity must be greater than zero');
            $('#targetQuantity').addClass('is-invalid');
            isValid = false;
        }

        // Check if product is selected
        if(!$('#product_id').val()) {
            toastr.error('Please select a product');
            $('#product_id').addClass('is-invalid');
            isValid = false;
        }

        // Validate material quantities only if there are materials
        const $materials = $('#materialsTable tbody tr');
        if ($materials.length > 0 && !$materials.find('td[colspan]').length) {
            let hasInsufficientStock = false;
            let insufficientMaterials = [];
            
            $materials.each(function() {
                const $row = $(this);
                const materialName = $row.find('td:eq(1)').text();
                const requiredText = $row.find('td:eq(2)').text();
                const availableText = $row.find('td:eq(3)').text();
                
                const required = parseFloat(requiredText.replace(/[^\d.-]/g, ''));
                const available = parseFloat(availableText.replace(/[^\d.-]/g, ''));
                
                if (required > available) {
                    hasInsufficientStock = true;
                    insufficientMaterials.push(`${materialName} (Required: ${required}, Available: ${available})`);
                }
            });

            if(hasInsufficientStock) {
                toastr.error('Cannot create production order. Insufficient stock for:\n' + insufficientMaterials.join('\n'));
                isValid = false;
            }
        }

        return isValid;
    },

    // Reset add order form
    resetAddOrderForm: function($form) {
        $form[0].reset();
        $('#product_id').val('').trigger('change');
        $('#materialsTable tbody').empty();
        $form.find('.is-invalid').removeClass('is-invalid');
        
        // Reset date fields to defaults
        const today = new Date().toISOString().split('T')[0];
        $('#startDate').val(today);
        
        // Set default completion date to 7 days from now
        const nextWeek = new Date();
        nextWeek.setDate(nextWeek.getDate() + 7);
        $('#completionDate').val(nextWeek.toISOString().split('T')[0]);
    },

    // Print preview function
    printPreview: function(orderNumber, orderId) {
        const printContent = document.getElementById('printableArea').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = `
            <div class="print-header" style="text-align: center; margin-bottom: 20px;">
                <h2>Production Order Details</h2>
                <h4>Order Number: ${orderNumber}</h4>
            </div>
            ${printContent}
        `;
        
        window.print();
        document.body.innerHTML = originalContent;
        
        // Reinitialize jQuery and Bootstrap
        jQuery(function($) {
            // Remove any existing modal backdrop
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            
            // Hide current modal
            $('#viewProductionOrderModal').modal('hide');
            
            // Reinitialize the modal after a short delay
            setTimeout(() => {
                ProductionOrder.view(orderId);
            }, 100);
        });
    }
};

// Initialize when document is ready
$(document).ready(function() {
    ProductionOrder.init();
});

// Clean up when modal is closed
$(document).on('hidden.bs.modal', '#editProductionOrderModal', function() {
    ProductionOrder.cleanup();
});

// Clean up when page is unloaded
$(window).on('unload', function() {
    ProductionOrder.cleanup();
});

// Make it globally available
window.ProductionOrder = ProductionOrder; 
