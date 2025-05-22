$(document).ready(function() {
    // Initialize date fields with today's date in YYYY-MM-DD format
    function formatDate(date) {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        return date.toISOString().split('T')[0];
    }

    function validateDate(dateString) {
        const date = new Date(dateString);
        return !isNaN(date.getTime());
    }

    function formatAmount(amount) {
        return parseFloat(amount).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Initialize order date with today's date and set max to today
    function initializeDates() {
        const today = new Date();
        const todayStr = formatDate(today);
        console.log('[Date Debug] Today:', todayStr);
        
        // Set order date to today
        $('#order_date')
            .val(todayStr)
            .attr('max', todayStr);
        
        console.log('[Date Debug] Order date initialized to:', $('#order_date').val());

        // Set delivery date min to order date
        $('#delivery_date')
            .attr('min', todayStr);
    }

    // Call initializeDates when document is ready and when modal is shown
    initializeDates();
    
    // Reset dates when modal is shown
    $('#addSalesOrderModal').on('show.bs.modal', function() {
        initializeDates();
    });

    // Initialize DataTable first, before any other functionality
    window.salesTable = $('#salesOrdersTable').DataTable({
        "ajax": {
            "url": "php_action/fetchSalesOrders.php",
            "type": "POST",
            "dataSrc": function(response) {
                if (!response.data) {
                    console.error('No data received from server');
                    return [];
                }
                return response.data;
            }
        },
        "order": [[2, "desc"]], // Order by date column descending
        "columns": [
            {"data": "order_number"},
            {"data": "client_name"},
            {
                "data": "order_date",
                "render": function(data) {
                    if (!data) return '';
                    const date = new Date(data);
                    return date.toLocaleDateString('en-GB');
                }
            },
            {
                "data": "total_amount",
                "render": function(data) {
                    return formatAmount(data);
                }
            },
            {
                "data": "paid_amount",
                "render": function(data) {
                    return formatAmount(data);
                }
            },
            {
                "data": "balance",
                "render": function(data) {
                    return formatAmount(data);
                }
            },
            {
                "data": "payment_status",
                "render": function(data) {
                    return getPaymentStatusBadge(data);
                }
            },
            {
                "data": "order_status",
                "render": function(data) {
                    return getOrderStatusBadge(data);
                }
            },
            {
                "data": null,
                "orderable": false,
                "render": function(data, type, row) {
                    return generateActionButtons(row);
                }
            }
        ],
        "responsive": true,
        "pageLength": 10,
        "dom": 'Bfrtip',
        "buttons": ['copy', 'csv', 'excel', 'pdf', 'print'],
        "drawCallback": function() {
            // Re-initialize tooltips after table redraw
            $('[data-toggle="tooltip"]').tooltip();
        }
    });

    // Load Clients for Select
    $.get('php_action/fetchClients.php', function(response) {
        if (response.success) {
            var options = '<option value="">Select Client</option>';
            response.data.forEach(function(client) {
                options += '<option value="' + client.id + '">' + client.company_name + '</option>';
            });
            $('#client_id').html(options);
        }
    });

    // Load Warehouses for Select
    $.get('php_action/fetchWarehouseForSales.php', function(response) {
        if (response.success) {
            var options = '<option value="">Select Warehouse</option>';
            response.data.forEach(function(warehouse) {
                options += '<option value="' + warehouse.id + '" data-display-name="' + warehouse.display_name + '">' + 
                          warehouse.name + '</option>';
            });
            $('#warehouse_id').html(options);
        }
    });

    // Warehouse Change Event
    $('#warehouse_id').on('change', function() {
        // Clear existing items
        $('#orderItemsTable tbody').html('<tr id="emptyRow"><td colspan="9" class="text-center">No items added</td></tr>');
        calculateTotals();
    });

    // Add Item Row
    $('#addItemBtn').click(function() {
        var warehouseId = $('#warehouse_id').val();
        
        if (!warehouseId) {
            Swal.fire({
                icon: 'warning',
                title: 'Warehouse Required',
                text: 'Please select a warehouse first'
            });
            return;
        }

        var newRow = '<tr>' +
            '<td>' +
            '<select class="form-control product-select" style="width: 100%;" required>' +
            '<option value="">Select Product</option>' +
            '</select>' +
            '</td>' +
            '<td class="text-right"><span class="available-stock">0</span> <small class="unit"></small></td>' +
            '<td><input type="number" class="form-control quantity-input text-right" min="1" step="1" required></td>' +
            '<td><input type="number" class="form-control unit-price text-right" min="0" step="0.01" required></td>' +
            '<td class="text-right"><span class="item-total">0.00</span></td>' +
            '<td class="text-center"><i class="fa fa-times remove-item"></i></td>' +
            '</tr>';

        $('#emptyRow').remove();
        $('#orderItemsTable tbody').append(newRow);

        // Initialize Select2 for the new product select
        $('.product-select').last().select2({
            placeholder: 'Select Product',
            allowClear: true,
            dropdownParent: $('#addSalesOrderModal'),
            ajax: {
                url: 'php_action/fetchProductsForSales.php',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        search: params.term,
                        warehouse_id: warehouseId
                    };
                },
                processResults: function(data) {
                    if (!data.success) return { results: [] };
                    
                    return {
                        results: data.data.map(function(product) {
                            var productData = {
                                id: product.id,
                                text: product.name,
                                selling_price: product.selling_price,
                                current_stock: product.current_stock,
                                unit: product.unit || 'units'
                            };
                            return productData;
                        })
                    };
                },
                cache: true
            },
            minimumInputLength: 0,
            templateResult: function(product) {
                if (!product.id) return product.text;
                
                var stock = parseFloat(product.current_stock) || 0;
                var price = parseFloat(product.selling_price) || 0;
                var stockClass = stock <= 0 ? 'text-danger' : (stock <= 5 ? 'text-warning' : 'text-success');
                
                var $container = $(
                    '<div class="product-option">' +
                        '<div class="product-name">' + product.text + '</div>' +
                        '<div class="product-info ' + stockClass + '">' +
                            'Stock: ' + stock + ' ' + (product.unit || 'units') +
                            ' | Price: ' + price.toFixed(2) +
                        '</div>' +
                    '</div>'
                );
                
                return $container;
            },
            templateSelection: function(product) {
                return product.text || 'Select Product';
            }
        }).on('select2:select', function(e) {
            var row = $(this).closest('tr');
            var product = e.params.data;
            
            if (product) {
                var price = parseFloat(product.selling_price) || 0;
                var stock = parseFloat(product.current_stock) || 0;
                
                row.find('.unit-price')
                    .val(price.toFixed(2))
                    .attr('data-original-price', price.toFixed(2)); // Store original price for reference
                row.find('.available-stock').text(stock.toFixed(2));
                row.find('.unit').text(product.unit || 'units');
                
                if (stock > 0) {
                    row.find('.quantity-input')
                        .attr('max', stock)
                        .val(1)
                        .trigger('input');
                }
            }
        });
    });

    // Remove Item Row
    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        if ($('#orderItemsTable tbody tr').length === 0) {
            $('#orderItemsTable tbody').html('<tr id="emptyRow"><td colspan="9" class="text-center">No items added</td></tr>');
        }
        calculateTotals();
    });

    // Product Select Change
    $(document).on('change', '.product-select', function() {
        var row = $(this).closest('tr');
        if (!$(this).val()) {
            resetRowInputs(row);
        }
    });

    // Input Change Events
    $(document).on('input', '.quantity-input, .unit-price', function() {
        calculateRowTotal($(this).closest('tr'));
    });

    // Withholding Tax and VAT Checkbox Change
    $('#applyWithholding, #applyVAT').on('change', function() {
        calculateTotals();
    });

    // Discount Percent Change
    $('#discountPercent').on('input', function() {
        calculateTotals();
    });

    // Calculate Row Total
    function calculateRowTotal(row) {
        var quantity = parseFloat(row.find('.quantity-input').val()) || 0;
        var unitPrice = parseFloat(row.find('.unit-price').val()) || 0;
        var itemSubtotal = quantity * unitPrice;
        
        // Calculate item level tax (15%) only if VAT is applied
        var itemTaxAmount = 0;
        if ($('#applyVAT').is(':checked')) {
            var itemTaxRate = 15;
            itemTaxAmount = (itemSubtotal * itemTaxRate) / 100;
        }
        
        // Calculate item level total
        var itemTotal = itemSubtotal + itemTaxAmount;
        
        row.find('.item-total').text(formatAmount(itemTotal));
        calculateTotals();
    }

    // Reset Row Inputs
    function resetRowInputs(row) {
        row.find('.unit-price').val('');
        row.find('.quantity-input').val('').removeAttr('max');
        row.find('.available-stock').text('0');
        row.find('.unit').text('');
        row.find('.item-total').text('0.00');
        calculateTotals();
    }

    // Calculate Order Totals
    function calculateTotals() {
        var subtotal = 0;
        var totalTaxAmount = 0;

        // Calculate subtotal and tax from items
        $('#orderItemsTable tbody tr:not(#emptyRow)').each(function() {
            var quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
            var unitPrice = parseFloat($(this).find('.unit-price').val()) || 0;
            var itemSubtotal = quantity * unitPrice;
            
            // Only calculate VAT if checkbox is checked
            if ($('#applyVAT').is(':checked')) {
                var itemTaxAmount = (itemSubtotal * 15) / 100; // 15% VAT
                totalTaxAmount += itemTaxAmount;
            }
            
            subtotal += itemSubtotal;
        });

        // Calculate Withholding Tax (2% of subtotal if checked)
        var withholdingAmount = 0;
        if ($('#applyWithholding').is(':checked')) {
            withholdingAmount = (subtotal * 2) / 100;
        }

        // Calculate Discount
        var discountPercent = parseFloat($('#discountPercent').val()) || 0;
        var discountAmount = (subtotal * discountPercent) / 100;

        // Calculate Total Amount
        var totalAmount = subtotal + totalTaxAmount - withholdingAmount - discountAmount;

        // Update display with proper formatting
        $('#subtotal').text(formatAmount(subtotal));
        $('#taxAmount').text(formatAmount(totalTaxAmount));
        $('#withholdingAmount').text(formatAmount(withholdingAmount));
        $('#discountAmount').text(formatAmount(discountAmount));
        $('#totalAmount').text(formatAmount(totalAmount));

        // Remove any existing hidden fields
        $('#addSalesOrderForm input[type="hidden"]').remove();

        // Add hidden fields for form submission
        var hiddenFields = {
            'subtotal': subtotal,
            'tax_amount': totalTaxAmount,
            'withholding_amount': withholdingAmount,
            'discount_amount': discountAmount,
            'total_amount': totalAmount,
            'discount_percent': discountPercent,
            'apply_withholding': $('#applyWithholding').is(':checked'),
            'apply_vat': $('#applyVAT').is(':checked')
        };

        // Add hidden fields to form
        Object.keys(hiddenFields).forEach(function(key) {
            $('<input>').attr({
                type: 'hidden',
                name: key,
                value: typeof hiddenFields[key] === 'boolean' ? 
                       (hiddenFields[key] ? '1' : '0') : 
                       hiddenFields[key].toFixed(2)
            }).appendTo('#addSalesOrderForm');
        });
    }

    // Handle form submission
    $('#addSalesOrderForm').on('submit', function(e) {
        e.preventDefault();
        
        // Get and validate order date
        const orderDate = $('#order_date').val();
        console.log('[Date Debug] Form submitted with order date:', orderDate);
        
        if (!orderDate) {
            console.error('[Date Debug] Order date is empty');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please select an order date'
            });
            return false;
        }

        if (!validateDate(orderDate)) {
            console.error('[Date Debug] Invalid order date format:', orderDate);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Invalid order date format. Please use the date picker.'
            });
            return false;
        }

        // Get items from the table
        var items = [];
        $('#orderItemsTable tbody tr:not(#emptyRow)').each(function() {
            var $row = $(this);
            items.push({
                product_id: $row.find('.product-select').val(),
                quantity: parseFloat($row.find('.quantity-input').val()),
                unit_price: parseFloat($row.find('.unit-price').val())
            });
        });
        
        if (items.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please add at least one item to the order'
            });
            return false;
        }

        // Get form data
        var formData = new FormData(this);
        var jsonData = {
            client_id: formData.get('client_id'),
            warehouse_id: formData.get('warehouse_id'),
            order_date: orderDate,
            delivery_date: formData.get('delivery_date') || null,
            notes: formData.get('notes'),
            items: items,
            subtotal: parseFloat($('#subtotal').text().replace(/,/g, '')),
            tax_amount: parseFloat($('#taxAmount').text().replace(/,/g, '')),
            discount_amount: parseFloat($('#discountAmount').text().replace(/,/g, '')),
            withholding_amount: parseFloat($('#withholdingAmount').text().replace(/,/g, '')),
            total_amount: parseFloat($('#totalAmount').text().replace(/,/g, '')),
            discount_percent: parseFloat($('#discountPercent').val() || 0),
            apply_withholding: $('#applyWithholding').is(':checked'),
            apply_vat: $('#applyVAT').is(':checked')
        };

        console.log('[Date Debug] Sending order data:', JSON.stringify(jsonData, null, 2));

        // Show loading state
        Swal.fire({
            title: 'Creating order...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Send order data
        $.ajax({
            url: 'php_action/createSalesOrder.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(jsonData),
            success: function(response) {
                console.log('[Date Debug] Server response:', JSON.stringify(response, null, 2));
                Swal.close();
                
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.messages[0]
                    }).then(() => {
                        $('#addSalesOrderModal').modal('hide');
                        refreshSalesTable();
                    });
                } else {
                    console.error('[Date Debug] Server error:', response.messages[0]);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages[0] || 'Failed to create order'
                    });
                }
            },
            error: function(xhr, status, error) {
                Swal.close();
                console.error('[Date Debug] XHR:', xhr);
                console.error('[Date Debug] Status:', status);
                console.error('[Date Debug] Error:', error);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to create order: ' + (xhr.responseJSON?.messages?.[0] || error)
                });
            }
        });
    });

    // Reset forms when modals are closed
    $('#addSalesOrderModal').on('hidden.bs.modal', function() {
        $('#addSalesOrderForm')[0].reset();
        $('#orderItemsTable tbody').html('<tr id="emptyRow"><td colspan="9" class="text-center">No items added</td></tr>');
        $('#client_id').val('').trigger('change');
        $('#warehouse_id').val('').trigger('change');
        calculateTotals();
    }).on('show.bs.modal', function() {
        initializeDates();
    });

    // View Order Details
    $(document).on('click', '.view-btn', function() {
        var orderId = $(this).data('id');
        
        // Show loading state
        Swal.fire({
            title: 'Loading...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Fetch order details
        $.ajax({
            url: 'php_action/fetchSalesOrderDetails.php',
            type: 'POST',
            data: { order_id: orderId },
            dataType: 'json',
            success: function(response) {
                Swal.close();
                
                if (response.success) {
                    var order = response.data;
                    
                    // Update order information
                    $('#view_order_number').text(order.order_number);
                    $('#view_client_name').text(order.client_name);
                    $('#view_order_date').text(formatDate(order.order_date));
                    $('#view_delivery_date').text(order.delivery_date ? formatDate(order.delivery_date) : 'Not specified');
                    $('#view_order_status').html(getOrderStatusBadge(order.order_status));
                    $('#view_payment_status').html(getPaymentStatusBadge(order.payment_status));
                    $('#view_created_by').text(order.created_by_name);
                    $('#view_created_at').text(formatDate(order.created_at));
                    
                    // Store order ID and manage toggle button visibility
                    $('#viewSalesOrderModal').data('order-id', order.id);
                    $('.toggle-status-modal-btn').toggle(
                        order.order_status.toLowerCase() !== 'cancelled' && 
                        ['pending', 'completed'].includes(order.order_status.toLowerCase())
                    );
                    
                    // Calculate totals from items
                    var subtotal = 0;
                    var totalTax = 0;
                    var totalDiscount = 0;
                    var totalWithholding = 0;

                    // Update items table
                    var itemsHtml = '';
                    order.items.forEach(function(item) {
                        var itemSubtotal = parseFloat(item.quantity) * parseFloat(item.unit_price);
                        var itemTaxAmount = (itemSubtotal * parseFloat(item.tax_rate)) / 100;
                        var itemWithholdingAmount = parseFloat(item.withholding_amount) || 0;
                        var itemDiscountAmount = (itemSubtotal * parseFloat(item.discount_percent)) / 100;
                        var itemTotal = itemSubtotal + itemTaxAmount - itemWithholdingAmount - itemDiscountAmount;
                        
                        subtotal += itemSubtotal;
                        totalTax += itemTaxAmount;
                        totalWithholding += itemWithholdingAmount;
                        totalDiscount += itemDiscountAmount;

                        itemsHtml += '<tr>' +
                            '<td>' + item.product_name + '</td>' +
                            '<td class="text-right">' + formatAmount(item.quantity) + '</td>' +
                            '<td class="text-right">' + formatAmount(item.unit_price) + '</td>' +
                            '<td class="text-right">' + item.tax_rate + '</td>' +
                            '<td class="text-right">' + formatAmount(itemTaxAmount) + '</td>' +
                            '<td class="text-right">' + item.discount_percent + '</td>' +
                            '<td class="text-right">' + formatAmount(itemDiscountAmount) + '</td>' +
                            '<td class="text-right">' + formatAmount(itemTotal) + '</td>' +
                        '</tr>';
                    });
                    $('#viewOrderItemsTable tbody').html(itemsHtml);

                    // Update totals
                    $('#view_subtotal').text(formatAmount(subtotal));
                    $('#view_tax_amount').text(formatAmount(totalTax));
                    $('#view_total_before_withholding').text(formatAmount(subtotal + totalTax));
                    
                    // Show/hide withholding row based on whether there is withholding tax
                    if (totalWithholding > 0) {
                        $('#view_withholding_row').show();
                        $('#view_withholding_amount').text(formatAmount(totalWithholding));
                    } else {
                        $('#view_withholding_row').hide();
                    }
                    
                    $('#view_grand_total').text(formatAmount(subtotal + totalTax - totalWithholding));
                    
                    // Show/hide discount row based on whether there is a discount
                    if (totalDiscount > 0) {
                        $('#view_discount_row').show();
                        $('#view_discount_amount').text(formatAmount(totalDiscount));
                    } else {
                        $('#view_discount_row').hide();
                    }
                    
                    // Update payable amount, paid amount and balance
                    $('#view_total_amount').text(formatAmount(subtotal + totalTax - totalWithholding - totalDiscount));
                    $('#view_paid_amount').text(formatAmount(order.paid_amount));
                    $('#view_balance').text(formatAmount(Math.max(0, order.balance))); // Ensure non-negative balance display
                    
                    // Update payment history
                    var totalPaid = 0;
                    var paymentsHtml = '';
                    if (order.payments && order.payments.length > 0) {
                        order.payments.forEach(function(payment) {
                            totalPaid += parseFloat(payment.amount);
                            paymentsHtml += '<tr>' +
                                '<td>' + formatDate(payment.payment_date) + '</td>' +
                                '<td class="text-right">' + formatAmount(payment.amount) + '</td>' +
                                '<td>' + (payment.payment_method || 'N/A') + '</td>' +
                                '<td>' + (payment.reference_number || '') + '</td>' +
                                '<td>' + (payment.notes || '') + '</td>' +
                            '</tr>';
                        });
                        
                        // Add total row if there are multiple payments
                        if (order.payments.length > 1) {
                            paymentsHtml += '<tr class="info">' +
                                '<td colspan="1"><strong>Total</strong></td>' +
                                '<td class="text-right"><strong>' + formatAmount(totalPaid) + '</strong></td>' +
                                '<td colspan="3"></td>' +
                            '</tr>';
                        }
                    } else {
                        paymentsHtml = '<tr><td colspan="5" class="text-center">No payments recorded</td></tr>';
                    }
                    $('#viewPaymentsTable tbody').html(paymentsHtml);
                    
                    // Update notes
                    $('#view_notes').html(order.notes || 'No notes');
                    
                    // Update payment button visibility based on actual balance
                    var remainingBalance = order.grand_total - totalPaid;
                    $('#addPaymentBtn')
                        .data('id', order.id)
                        .data('balance', remainingBalance)
                        .data('order-number', order.order_number)
                        .toggle(order.order_status !== 'cancelled' && remainingBalance > 0);

                    // Update "Mark as Paid" button visibility
                    $('.toggle-payment-status-btn').toggle(
                        remainingBalance > 0 && 
                        order.order_status.toLowerCase() !== 'cancelled'
                    );
                    
                    // Show modal
                    $('#viewSalesOrderModal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages || 'Failed to load order details'
                    });
                }
            },
            error: function() {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to fetch order details'
                });
            }
        });
    });

    // Helper functions for consistent formatting
    function getPaymentStatusBadge(status) {
        let badgeClass = '';
        switch(status.toLowerCase()) {
            case 'paid': badgeClass = 'success'; break;
            case 'partial': badgeClass = 'warning'; break;
            default: badgeClass = 'danger';
        }
        return '<span class="label label-' + badgeClass + '">' + 
               status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
    }

    function getOrderStatusBadge(status) {
        let badgeClass = '';
        switch(status.toLowerCase()) {
            case 'completed': badgeClass = 'success'; break;
            case 'processing': badgeClass = 'warning'; break;
            case 'cancelled': badgeClass = 'danger'; break;
            default: badgeClass = 'info';
        }
        return '<span class="label label-' + badgeClass + '">' + 
               status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
    }

    function generateActionButtons(row) {
        var buttons = '<div class="btn-group btn-group-sm">';
        
        // View button
        buttons += '<button type="button" class="btn btn-default view-btn" ' +
                 'data-id="' + row.id + '" ' +
                 'data-toggle="tooltip" title="View Details">' +
                 '<i class="fa fa-eye"></i></button>';
        
        // Payment button - show if not cancelled and not fully paid
        if (row.order_status.toLowerCase() !== 'cancelled' && 
            row.payment_status.toLowerCase() !== 'paid') {
            buttons += '<button type="button" class="btn btn-success payment-btn" ' +
                     'data-id="' + row.id + '" ' +
                     'data-balance="' + row.balance + '" ' +
                     'data-order-number="' + row.order_number + '" ' +
                     'data-toggle="tooltip" title="Add Payment">' +
                     '<i class="fa fa-money"></i></button>';
        }
        
        // Print button
        buttons += '<button type="button" class="btn btn-primary print-btn" ' +
                 'data-order-number="' + row.order_number + '" ' +
                 'data-toggle="tooltip" title="Print Invoice">' +
                 '<i class="fa fa-print"></i></button>';
        
        buttons += '</div>';
        return buttons;
    }

    // Helper function to format dates consistently
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB');
    }

    // Update payment method when account is selected
    $('#account_id').on('change', function() {
        var $selected = $(this).find('option:selected');
        var platform = $selected.data('platform');
        
        // Log for debugging
        console.log('Account changed:', {
            selectedAccount: $selected.text(),
            platform: platform,
            selectedValue: $(this).val()
        });
        
        // Clear any previous validation messages
        $('#payment_method')
            .removeClass('is-invalid')
            .next('.invalid-feedback').remove();
        
        if (platform) {
            // Set payment method as default but allow changes
            $('#payment_method')
                .val(platform)
                .prop('readonly', false)
                .attr('data-suggested-platform', platform);
            
            // Add visual indicator that this is the suggested method
            if (!$('#payment_method').next('.form-text').length) {
                $('#payment_method').after('<small class="form-text text-muted">Suggested payment method based on the selected account platform.</small>');
            }
        } else {
            // Clear payment method if no account selected
            $('#payment_method')
                .val('')
                .prop('readonly', false)
                .removeAttr('data-suggested-platform')
                .next('.form-text').remove();
        }
    });

    // Payment button click handler
    $(document).on('click', '.payment-btn', function(e) {
        e.preventDefault();
        
        // Get the data attributes
        var orderId = parseInt($(this).data('id'));
        var balance = parseFloat($(this).data('balance'));
        var orderNumber = $(this).data('order-number');
        
        // Log the values for debugging
        console.log('Payment button clicked:', {
            orderId: orderId,
            balance: balance,
            orderNumber: orderNumber
        });

        // Validate required data
        if (!orderId || isNaN(orderId) || isNaN(balance) || !orderNumber) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Invalid payment information'
            });
            return;
        }

        // Reset form and clear validation messages
        $('#addPaymentForm')[0].reset();
        $('#addPaymentForm .is-invalid').removeClass('is-invalid');
        $('#addPaymentForm .invalid-feedback').remove();
        
        // Set order ID and amount
        $('#payment_order_id').val(orderId);
        $('#payment_amount')
            .val(balance.toFixed(2))
            .attr('max', balance.toFixed(2));
        $('#max_payment_amount').text(balance.toFixed(2));
        
        // Set today's date as default
        var today = new Date().toISOString().split('T')[0];
        $('#payment_date').val(today);

        // Load accounts
        $.ajax({
            url: 'php_action/fetchAccountsForSales.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    console.log('Adding account options:', response.data);
                    var $select = $('#account_id');
                    
                    // Clear existing options
                    $select.empty().append('<option value="">Select Account</option>');
                    
                    // Initialize or destroy and reinitialize Select2
                    if ($select.data('select2')) {
                        $select.select2('destroy');
                    }
                    
                    // Add new options
                    response.data.forEach(function(account) {
                        $select.append(new Option(
                            account.display_name,
                            account.id,
                            false,
                            false
                        )).data('platform-' + account.id, account.account_platform);
                    });
                    
                    // Initialize Select2 with custom formatting
                    $select.select2({
                        placeholder: 'Select Account',
                        allowClear: true,
                        dropdownParent: $('#addPaymentModal'),
                        templateResult: function(account) {
                            if (!account.id) return account.text;
                            var $container = $(
                                '<div class="account-option">' +
                                '<div class="account-name">' + account.text + '</div>' +
                                '</div>'
                            );
                            return $container;
                        }
                    }).on('change', function(e) {
                        var selectedId = $(this).val();
                        var platform = selectedId ? $(this).data('platform-' + selectedId) : '';
                        
                        // Update payment method based on platform
                        var $paymentMethod = $('#payment_method');
                        if (platform) {
                            $paymentMethod
                                .val(platform)
                                .prop('readonly', true)
                                .attr('data-original-platform', platform);
                            
                            // Add visual indicator
                            if (!$paymentMethod.next('.form-text').length) {
                                $paymentMethod.after(
                                    '<small class="form-text text-muted">' +
                                    'Payment method is set based on the selected account.' +
                                    '</small>'
                                );
                            }
                        } else {
                            $paymentMethod
                                .val('')
                                .prop('readonly', false)
                                .removeAttr('data-original-platform')
                                .next('.form-text').remove();
                        }
                    });

                    // Show the modal
                    $('#addPaymentModal').modal('show');

                    // Set order ID immediately
                    $('#payment_order_id').val(orderId);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages || 'Failed to load accounts'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading accounts:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load accounts. Please try again.'
                });
            }
        });
    });

    // Payment form submission
    $('#addPaymentForm').on('submit', function(e) {
        e.preventDefault();
        
        // Get form data and ensure proper types
        var orderId = parseInt($('#payment_order_id').val(), 10);
        var accountId = parseInt($('#account_id').val(), 10);
        var amount = parseFloat($('#payment_amount').val());
        var paymentMethod = $('#payment_method').val();
        
        // Create FormData object for file upload
        var formData = new FormData();
        formData.append('payment_order_id', orderId);
        formData.append('account_id', accountId);
        formData.append('amount', amount);
        formData.append('payment_method', paymentMethod);
        formData.append('payment_date', $('#payment_date').val());
        formData.append('reference_number', $('#reference_number').val());
        formData.append('notes', $('#payment_notes').val());

        // Add payment proof if selected
        var paymentProof = $('#payment_proof')[0].files[0];
        if (paymentProof) {
            // Validate file size (max 5MB)
            if (paymentProof.size > 5 * 1024 * 1024) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Payment proof file size must be less than 5MB'
                });
                return false;
            }
            formData.append('payment_proof', paymentProof);
        }

        // Validate required fields and data types
        if (!orderId || isNaN(orderId)) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Invalid order ID. Please try again.'
            });
            return false;
        }

        if (!accountId || isNaN(accountId)) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please select an account'
            });
            return false;
        }

        if (!amount || isNaN(amount) || amount <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please enter a valid payment amount'
            });
            return false;
        }

        if (!paymentMethod) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Payment method is required'
            });
            return false;
        }

        // Disable submit button and show loading state
        var $submitBtn = $('#submitPaymentBtn');
        $submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

        // Submit payment with file upload
        $.ajax({
            url: 'php_action/addSalesPayment.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('Payment response:', response);
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.messages || 'Payment added successfully'
                    }).then(() => {
                        $('#addPaymentModal').modal('hide');
                        $('#addPaymentForm')[0].reset();
                        refreshSalesTable();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: Array.isArray(response.messages) ? response.messages.join('\n') : 
                              (response.messages || 'Failed to add payment')
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Payment Error:', {xhr, status, error});
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to process payment. Please try again.'
                });
            },
            complete: function() {
                // Reset submit button
                $submitBtn.prop('disabled', false).html('Submit Payment');
            }
        });
    });

    // Initialize payment modal
    $('#addPaymentModal').on('show.bs.modal', function() {
        // Initialize Select2 for account selection if not already initialized
        if (!$('#account_id').data('select2')) {
            $('#account_id').select2({
                placeholder: 'Select Account',
                allowClear: true,
                dropdownParent: $('#addPaymentModal')
            });
        }
    }).on('hidden.bs.modal', function() {
        // Reset form and clear validation when modal is closed
        $('#addPaymentForm')[0].reset();
        $('#addPaymentForm .is-invalid').removeClass('is-invalid');
        $('#addPaymentForm .invalid-feedback').remove();
        if ($('#account_id').data('select2')) {
            $('#account_id').val('').trigger('change');
        }
    });

    // Add payment amount validation
    $('#payment_amount').on('input', function() {
        var amount = parseFloat($(this).val()) || 0;
        var max = parseFloat($(this).attr('max')) || 0;
        
        if (amount <= 0) {
            $(this).get(0).setCustomValidity('Please enter an amount greater than 0');
        } else if (amount > max) {
            $(this).get(0).setCustomValidity('Amount cannot exceed ' + max.toFixed(2));
        } else {
            $(this).get(0).setCustomValidity('');
        }
        
        // Show validation message
        if (this.validationMessage) {
            $(this).addClass('is-invalid');
            let feedbackDiv = $(this).next('.invalid-feedback');
            if (feedbackDiv.length === 0) {
                $(this).after('<div class="invalid-feedback">' + this.validationMessage + '</div>');
            } else {
                feedbackDiv.text(this.validationMessage);
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });

    // Print button click handler
    $(document).on('click', '.print-btn', function() {
        var orderId = $(this).data('order-number');
        
        // Open print window in a new tab/window
        var printWindow = window.open('php_action/printSalesOrder.php?id=' + orderId, '_blank');
        
        // Optional: Focus on the new window
        if (printWindow) {
            printWindow.focus();
        }
    });

    // Handle toggle status button click
    $(document).on('click', '.toggle-status-btn', function() {
        var orderId = $(this).data('id');
        var currentStatus = $(this).data('current-status');
        var newStatus = currentStatus === 'pending' ? 'completed' : 'pending';
        
        if (confirm('Are you sure you want to change the order status to ' + newStatus + '?')) {
            $.ajax({
                url: 'php_action/toggleSalesOrderStatus.php',
                type: 'POST',
                data: {
                    order_id: orderId,
                    new_status: newStatus
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $.notify({
                            message: response.message
                        }, {
                            type: 'success'
                        });
                        refreshSalesTable();
                    } else {
                        $.notify({
                            message: response.message
                        }, {
                            type: 'danger'
                        });
                    }
                },
                error: function() {
                    $.notify({
                        message: 'Error occurred while updating order status'
                    }, {
                        type: 'danger'
                    });
                }
            });
        }
    });

    // Handle toggle status button click in view modal
    $(document).on('click', '.toggle-status-modal-btn', function() {
        var currentStatus = $('#view_order_status .label').text().toLowerCase();
        var orderId = $('#viewSalesOrderModal').data('order-id');
        var newStatus = currentStatus === 'pending' ? 'completed' : 'pending';
        
        if (confirm('Are you sure you want to change the order status to ' + newStatus + '?')) {
            $.ajax({
                url: 'php_action/toggleSalesOrderStatus.php',
                type: 'POST',
                data: {
                    order_id: orderId,
                    new_status: newStatus
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update the status badge in the modal
                        $('#view_order_status').html(getOrderStatusBadge(newStatus));
                        
                        // Show success message
                        $.notify({
                            message: response.message
                        }, {
                            type: 'success'
                        });
                        
                        refreshSalesTable();
                    } else {
                        $.notify({
                            message: response.message
                        }, {
                            type: 'danger'
                        });
                    }
                },
                error: function() {
                    $.notify({
                        message: 'Error occurred while updating order status'
                    }, {
                        type: 'danger'
                    });
                }
            });
        }
    });

    // Handle payment status toggle button click
    $(document).on('click', '.toggle-payment-status-btn', function() {
        var orderId = $('#viewSalesOrderModal').data('order-id');
        
        if (confirm('Are you sure you want to mark this order as fully paid? This will create a payment record for the remaining balance.')) {
            $.ajax({
                url: 'php_action/toggleSalesPaymentStatus.php',
                type: 'POST',
                data: {
                    order_id: orderId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#view_payment_status').html(getPaymentStatusBadge('paid'));
                        $('.toggle-payment-status-btn, #addPaymentBtn').hide();
                        
                        $.notify({
                            message: response.message
                        }, {
                            type: 'success'
                        });
                        
                        refreshPaymentHistory(orderId);
                        refreshSalesTable();
                    } else {
                        $.notify({
                            message: response.message
                        }, {
                            type: 'danger'
                        });
                    }
                },
                error: function() {
                    $.notify({
                        message: 'Error occurred while updating payment status'
                    }, {
                        type: 'danger'
                    });
                }
            });
        }
    });

    // Function to refresh payment history in modal
    function refreshPaymentHistory(orderId) {
        $.ajax({
            url: 'php_action/fetchSalesOrderDetails.php',
            type: 'POST',
            data: { order_id: orderId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.payments) {
                    var paymentsHtml = '';
                    response.data.payments.forEach(function(payment) {
                        paymentsHtml += '<tr>' +
                            '<td>' + formatDate(payment.payment_date) + '</td>' +
                            '<td class="text-right">' + formatAmount(payment.amount) + '</td>' +
                            '<td>' + (payment.payment_method || 'N/A') + '</td>' +
                            '<td>' + (payment.reference_number || '') + '</td>' +
                            '<td>' + (payment.notes || '') + '</td>' +
                        '</tr>';
                    });
                    $('#viewPaymentsTable tbody').html(paymentsHtml);
                }
            }
        });
    }

    // Function to refresh DataTable
    function refreshSalesTable() {
        if (window.salesTable) {
            window.salesTable.ajax.reload(null, false);
        }
    }
}); 