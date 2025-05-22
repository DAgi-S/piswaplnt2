$(document).ready(function() {
    // Initialize DataTable
    var salesDetailTable = $('#salesDetailTable').DataTable({
        "ajax": {
            "url": "php_action/fetchSalesDetail.php",
            "type": "POST"
        },
        "columns": [
            { "data": "sale_number" },
            { 
                "data": "date",
                "render": function(data) {
                    return moment(data).format('DD/MM/YYYY');
                }
            },
            { "data": "client_name" },
            { 
                "data": "products",
                "render": function(data) {
                    return `<span title="${data}">${data.substring(0, 30)}${data.length > 30 ? '...' : ''}</span>`;
                }
            },
            { 
                "data": "subtotal",
                "className": "text-right",
                "render": function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                "data": "vat",
                "className": "text-right",
                "render": function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                "data": "withholding",
                "className": "text-right",
                "render": function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                "data": "discount",
                "className": "text-right",
                "render": function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                "data": "grand_total",
                "className": "text-right",
                "render": function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                "data": "paid_amount",
                "className": "text-right",
                "render": function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                "data": "balance",
                "className": "text-right",
                "render": function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            { 
                "data": "payment_status",
                "render": function(data) {
                    let badgeClass = '';
                    switch(data.toLowerCase()) {
                        case 'paid': badgeClass = 'success'; break;
                        case 'partial': badgeClass = 'warning'; break;
                        default: badgeClass = 'danger';
                    }
                    return '<span class="label label-' + badgeClass + '">' + 
                           data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                }
            },
            { "data": "created_by" },
            {
                "data": "sale_id",
                "orderable": false,
                "render": function(data) {
                    return `
                        <div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Action <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a href="#" onclick="viewSale(${data})"><i class="glyphicon glyphicon-eye-open"></i> View</a></li>
                                <li><a href="#" onclick="updatePaymentStatus(${data})"><i class="glyphicon glyphicon-usd"></i> Update Payment</a></li>
                                <li><a href="#" onclick="printSale(${data})"><i class="glyphicon glyphicon-print"></i> Print</a></li>
                            </ul>
                        </div>`;
                }
            }
        ],
        "order": [[1, "desc"]],
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        "processing": true,
        "serverSide": true,
        "responsive": true,
        "dom": '<"top"Blfrtip>',
        "buttons": [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        "language": {
            "processing": "Loading...",
            "lengthMenu": "_MENU_ records per page",
            "zeroRecords": "No matching records found",
            "info": "Showing _START_ to _END_ of _TOTAL_ records",
            "infoEmpty": "No records available",
            "infoFiltered": "(filtered from _MAX_ total records)",
            "search": "Search:",
            "paginate": {
                "first": "First",
                "last": "Last",
                "next": "Next",
                "previous": "Previous"
            }
        }
    });

    // Refresh table every 5 minutes
    setInterval(function() {
        salesDetailTable.ajax.reload(null, false);
    }, 300000);
    
    // Handle payment form submission
    $('#addPaymentForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate the amount against the balance
        var amount = parseFloat($('#payment_amount').val());
        var balance = parseFloat($('#payment_amount').attr('max'));
        
        if (amount <= 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Payment amount must be greater than zero'
                });
            } else {
                alert('Payment amount must be greater than zero');
            }
            return false;
        }
        
        if (amount > balance) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Payment amount cannot exceed the remaining balance of ' + formatCurrency(balance)
                });
            } else {
                alert('Payment amount cannot exceed the remaining balance of ' + formatCurrency(balance));
            }
            return false;
        }
        
        // Create FormData object for file uploads
        var formData = new FormData(this);
        
        // Add the sale ID
        var saleId = $('#updatePaymentModal').data('sale-id');
        formData.append('sale_id', saleId);
        
        $.ajax({
            url: 'php_action/addPayment.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Close modal
                    $('#updatePaymentModal').modal('hide');
                    
                    // Show success message using SweetAlert if available, otherwise use alert
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Payment added successfully'
                        });
                    } else {
                        alert('Payment added successfully');
                    }
                    
                    // Reset form
                    $('#addPaymentForm')[0].reset();
                    
                    // Refresh table
                    salesDetailTable.ajax.reload(null, false);
                } else {
                    // Show error message
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Could not add payment'
                        });
                    } else {
                        alert('Error: ' + (response.message || 'Could not add payment'));
                    }
                }
            },
            error: function(xhr, status, error) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error adding payment. Please try again later.'
                    });
                } else {
                    alert('Error adding payment. Please try again later.');
                }
                console.error('AJAX Error:', status, error);
            }
        });
    });

    // Initialize payment amount validation
    $(document).on('input', '#payment_amount', function() {
        var amount = parseFloat($(this).val());
        var max = parseFloat($(this).attr('max'));
        
        if (amount > max) {
            $(this).val(max);
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Amount Adjusted',
                    text: 'Amount has been adjusted to the maximum balance of ' + formatCurrency(max)
                });
            } else {
                alert('Amount has been adjusted to the maximum balance of ' + formatCurrency(max));
            }
        }
    });

    // Handle overpayment adjustment button
    $('#adjust_overpayment_btn').on('click', function() {
        const saleId = $(this).closest('.modal-content').data('sale-id') || $('#viewSaleModal').data('sale-id');
        showAdjustOverpaymentModal(saleId);
        return false;
    });
    
    // Handle overpayment adjustment form submission
    $('#adjustOverpaymentForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate the amount against the overpayment amount
        const amount = parseFloat($('#adjustment_amount').val());
        const maxAdjustment = parseFloat($('#max_adjustment').text().replace(/,/g, ''));
        
        if (amount <= 0) {
            alert('Adjustment amount must be greater than zero');
            return false;
        }
        
        if (amount > maxAdjustment) {
            alert('Adjustment amount cannot exceed the overpayment amount of ' + formatCurrency(maxAdjustment));
            return false;
        }
        
        // Show loading indicator
        $('#saveAdjustmentBtn').prop('disabled', true).html('<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Processing...');
        
        // Get form data
        const formData = {
            sale_id: $('#adjustOverpaymentModal').data('sale-id'),
            adjustment_amount: amount.toFixed(2),
            adjustment_type: $('#adjustment_type').val(),
            notes: $('#adjustment_notes').val()
        };
        
        console.log('Submitting adjustment:', formData);
        
        // Process the adjustment
        $.ajax({
            url: 'php_action/adjustPaymentOverage.php',
            type: 'POST',
            data: {
                sale_id: String(formData.sale_id),
                adjustment_amount: String(formData.adjustment_amount),
                adjustment_type: formData.adjustment_type,
                notes: formData.notes || ''
            },
            cache: false,
            dataType: 'json',
            contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
            success: function(response) {
                console.log('Adjustment response:', response);
                if (response.success) {
                    // Close modal
                    $('#adjustOverpaymentModal').modal('hide');
                    
                    // Show success message
                    alert(response.message || 'Overpayment adjustment processed successfully');
                    
                    // Reset form
                    $('#adjustOverpaymentForm')[0].reset();
                    
                    // Refresh table
                    $('#salesDetailTable').DataTable().ajax.reload(null, false);
                    
                    // If we're in the view modal, update that too
                    const viewingId = $('#viewSaleModal').data('sale-id');
                    if (viewingId && viewingId === formData.sale_id) {
                        viewSale(viewingId);
                    }
                } else {
                    let errorMessage = response.message || 'Could not process adjustment';
                    console.error('Adjustment error:', errorMessage);
                    
                    // Use SweetAlert if available, otherwise use basic alert
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Adjustment Error',
                            text: errorMessage,
                            footer: '<a href="php_action/directAdjustmentSimple.php" target="_blank">Try simple adjustment method</a>'
                        });
                    } else {
                        alert('Error: ' + errorMessage + '\n\nYou may try using the simple adjustment method instead.');
                    }
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Server error occurred';
                
                // Try to parse response if available
                try {
                    const responseObj = JSON.parse(xhr.responseText);
                    if (responseObj && responseObj.message) {
                        errorMessage = responseObj.message;
                    }
                } catch (e) {
                    // If parsing fails, try to extract error from response text
                    if (xhr.responseText && xhr.responseText.includes('Error')) {
                        // Look for common error patterns
                        const errorMatch = xhr.responseText.match(/Error:([^<]+)/);
                        if (errorMatch && errorMatch[1]) {
                            errorMessage = errorMatch[1].trim();
                        } else {
                            errorMessage = 'Server error: ' + (error || status || 'Unknown error');
                        }
                    }
                }
                
                console.error('AJAX Error Details:', {
                    status: status,
                    error: error,
                    response: xhr.responseText,
                    message: errorMessage
                });
                
                // Use SweetAlert if available, otherwise use basic alert
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Adjustment Failed',
                        text: errorMessage,
                        footer: '<a href="php_action/directAdjustmentSimple.php" target="_blank">Try simple adjustment method</a>'
                    });
                } else {
                    alert('Error processing adjustment: ' + errorMessage + '\n\nYou may try using the simple adjustment method instead.');
                }
            },
            complete: function() {
                // Re-enable button
                $('#saveAdjustmentBtn').prop('disabled', false).html('<i class="glyphicon glyphicon-ok"></i> Process Adjustment');
            }
        });
    });
});

// View sale details
function viewSale(saleId) {
    $.ajax({
        url: 'php_action/fetchSingleSale.php',
        type: 'POST',
        data: { sale_id: saleId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Store sale ID in both modal and print button for later use
                $('#viewSaleModal').data('sale-id', saleId);
                $('#printSaleBtn').data('sale-id', saleId);
                
                // Fill sale information
                $('#view_sale_number').text(response.data.sale_number);
                $('#view_client_name').text(response.data.client_name);
                $('#view_sale_date').text(moment(response.data.date).format('DD/MM/YYYY'));
                $('#view_created_by').text(response.data.created_by);
                
                // Fill payment information
                $('#view_payment_status').html(getPaymentStatusBadge(response.data.payment_status));
                $('#view_total_amount').text(formatCurrency(response.data.grand_total));
                $('#view_paid_amount').text(formatCurrency(response.data.paid_amount));
                $('#view_balance').text(formatCurrency(response.data.balance));
                
                // Check for overpayment (negative balance)
                const balance = parseFloat(response.data.balance);
                if (balance < 0) {
                    // Show overpayment button if balance is negative (overpaid)
                    $('#adjust_overpayment_btn').show();
                } else {
                    // Hide overpayment button if balance is zero or positive
                    $('#adjust_overpayment_btn').hide();
                }
                
                // Fill product details
                let productRows = '';
                response.data.products.forEach(function(product) {
                    productRows += `
                        <tr>
                            <td>${product.name}</td>
                            <td class="text-right">${product.quantity}</td>
                            <td class="text-right">${formatCurrency(product.unit_price)}</td>
                            <td class="text-right">${formatCurrency(product.total)}</td>
                        </tr>`;
                });
                $('#productDetailsTable tbody').html(productRows);
                
                // Fill totals
                $('#view_subtotal').text(formatCurrency(response.data.subtotal));
                $('#view_vat').text(formatCurrency(response.data.vat));
                $('#view_wht').text(formatCurrency(response.data.withholding));
                $('#view_discount').text(formatCurrency(response.data.discount));
                $('#view_grand_total').text(formatCurrency(response.data.grand_total));
                
                // Set up action buttons with the correct sale ID
                $('#update_payment_btn').off('click').on('click', function() {
                    $('#viewSaleModal').modal('hide');
                    updatePaymentStatus(saleId);
                    return false;
                });
                
                $('#view_payments_btn').attr('href', 'sale_payments.php?id=' + saleId);
                
                // Fill payment history
                let paymentRows = '';
                response.data.payments.forEach(function(payment) {
                    // Create payment proof link if available
                    let paymentProofDisplay = '-';
                    if (payment.payment_proof && payment.payment_proof !== 'NULL' && payment.payment_proof !== '') {
                        paymentProofDisplay = `<a href="${payment.payment_proof}" target="_blank" class="btn btn-xs btn-info"><i class="glyphicon glyphicon-eye-open"></i> View</a>`;
                    }
                    
                    paymentRows += `
                        <tr>
                            <td>${moment(payment.date).format('DD/MM/YYYY')}</td>
                            <td class="text-right">${formatCurrency(payment.amount)}</td>
                            <td>${payment.method}</td>
                            <td>${payment.reference || '-'}</td>
                            <td>${payment.notes || '-'}</td>
                            <td>${paymentProofDisplay}</td>
                        </tr>`;
                });
                $('#paymentHistoryTable tbody').html(paymentRows);
                
                // Show modal
                $('#viewSaleModal').modal('show');
            } else {
                alert('Error fetching sale details: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            alert('Error fetching sale details. Please try again later.');
            console.error('AJAX Error:', status, error);
        }
    });
}

// Print sale
function printSale(saleId) {
    window.open(`print_sale.php?id=${saleId}`, '_blank');
}

// Update payment status
function updatePaymentStatus(saleId) {
    // Fetch sale details to populate the payment form
    $.ajax({
        url: 'php_action/fetchSingleSale.php',
        type: 'POST',
        data: { sale_id: saleId },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Set sale ID in the modal data attribute
                $('#updatePaymentModal').data('sale-id', saleId);
                
                // Populate sale information in the modal
                $('#update_sale_number').text(response.data.sale_number);
                $('#update_client_name').text(response.data.client_name);
                $('#update_sale_date').text(moment(response.data.date).format('DD/MM/YYYY'));
                $('#update_payment_status').html(`<span class="label label-${getStatusClass(response.data.payment_status)}">${response.data.payment_status}</span>`);
                $('#update_total_amount').text(formatCurrency(response.data.grand_total));
                $('#update_paid_amount').text(formatCurrency(response.data.paid_amount));
                $('#update_balance').text(formatCurrency(response.data.balance));
                
                // Set max amount to balance
                $('#payment_amount').attr('max', response.data.balance);
                
                // Update the max amount text
                $('#max_amount').text(formatCurrency(response.data.balance));
                
                // Set current date for payment date field
                $('#payment_date').val(new Date().toISOString().split('T')[0]);
                
                // Fetch accounts for dropdown
                $.ajax({
                    url: 'php_action/fetchAccountsForSales.php',
                    type: 'GET',
                    dataType: 'json',
                    cache: false, // Prevent caching
                    beforeSend: function() {
                        console.log('Fetching accounts...');
                        // Clear previous options and add placeholder
                        $('#select_account').empty().append('<option value="">Loading accounts...</option>');
                    },
                    success: function(accountsResponse) {
                        console.log('Account response:', accountsResponse); // Debug log
                        
                        // Clear the dropdown
                        $('#select_account').empty().append('<option value="">Select Account</option>');
                        
                        // Check if we have valid data
                        if(accountsResponse.success && accountsResponse.data && accountsResponse.data.length > 0) {
                            // Add account options
                            $.each(accountsResponse.data, function(index, account) {
                                $('#select_account').append(`<option value="${account.id}">${account.display_name}</option>`);
                            });
                            console.log(`Loaded ${accountsResponse.data.length} accounts`);
                        } else {
                            console.error('No accounts found or request failed:', accountsResponse);
                            
                            // Try fallback method
                            fetchAccountsFallback();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to fetch accounts:', {status: status, error: error, response: xhr.responseText});
                        
                        // Try fallback method
                        fetchAccountsFallback();
                    }
                });
                
                // Fallback function for fetching accounts
                function fetchAccountsFallback() {
                    console.log('Trying fallback account fetch method...');
                    
                    // Clear the dropdown
                    $('#select_account').empty().append('<option value="">Loading accounts (fallback)...</option>');
                    
                    $.ajax({
                        url: 'php_action/fetchAccounts.php',
                        type: 'GET',
                        dataType: 'json',
                        cache: false,
                        success: function(response) {
                            console.log('Fallback account response:', response);
                            
                            // Clear the dropdown
                            $('#select_account').empty().append('<option value="">Select Account</option>');
                            
                            if(response.success && response.data && response.data.length > 0) {
                                // Add account options
                                $.each(response.data, function(index, account) {
                                    const displayName = account.owner + ' - ' + account.name + ' (' + account.currency + ')';
                                    $('#select_account').append(`<option value="${account.id}">${displayName}</option>`);
                                });
                                console.log(`Loaded ${response.data.length} accounts (fallback)`);
                            } else {
                                // Final failure - no accounts found
                                $('#select_account').empty().append('<option value="">No accounts found</option>');
                                
                                setTimeout(function() {
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'No Accounts Found',
                                            text: 'Please add accounts before processing payments',
                                            footer: '<a href="accounts.php">Manage Accounts</a>'
                                        });
                                    } else {
                                        alert('No accounts found. Please add accounts before processing payments.');
                                    }
                                }, 500);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Fallback accounts fetch failed:', {status: status, error: error, response: xhr.responseText});
                            
                            // Final failure - couldn't load accounts
                            $('#select_account').empty().append('<option value="">Error loading accounts</option>');
                            
                            setTimeout(function() {
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Failed to load accounts. Please try visiting the Accounts page first.',
                                        footer: '<a href="accounts.php">Manage Accounts</a>'
                                    });
                                } else {
                                    alert('Failed to load accounts. Please try visiting the Accounts page first.');
                                }
                            }, 500);
                        }
                    });
                }
                
                // Show the modal
                $('#updatePaymentModal').modal('show');
            } else {
                // Show error message
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Could not retrieve sale details'
                    });
                } else {
                    alert('Error: ' + (response.message || 'Could not retrieve sale details'));
                }
            }
        },
        error: function() {
            // Show error message
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to retrieve sale details. Please try again.'
                });
            } else {
                alert('Failed to retrieve sale details. Please try again.');
            }
        }
    });
}

// Helper functions
function formatCurrency(amount) {
    return parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function getStatusClass(status) {
    switch(status.toLowerCase()) {
        case 'paid': return 'success';
        case 'partial': return 'warning';
        default: return 'danger';
    }
}

// Generate payment status badge HTML
function getPaymentStatusBadge(status) {
    const statusClass = getStatusClass(status);
    return `<span class="label label-${statusClass}">${status}</span>`;
}

// Function to show adjust overpayment modal
function showAdjustOverpaymentModal(saleId) {
    if (!saleId) {
        alert('Sale ID is missing. Please try again.');
        return;
    }
    
    // Store sale ID in the modal
    $('#adjustOverpaymentModal').data('sale-id', saleId);
    
    // Fetch sale details
    $.ajax({
        url: 'php_action/fetchSingleSale.php',
        type: 'POST',
        data: { sale_id: saleId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                const totalAmount = parseFloat(data.grand_total);
                const paidAmount = parseFloat(data.paid_amount);
                const overpayment = paidAmount - totalAmount;
                
                // Check if there's actually an overpayment
                if (overpayment <= 0) {
                    alert('This sale does not have an overpayment to adjust.');
                    return;
                }
                
                // Fill overpayment modal fields
                $('#overpayment_sale_number').text(data.sale_number);
                $('#overpayment_client_name').text(data.client_name);
                $('#overpayment_total_amount').text(formatCurrency(totalAmount));
                $('#overpayment_paid_amount').text(formatCurrency(paidAmount));
                $('#overpayment_amount').text(formatCurrency(overpayment));
                
                // Set maximum adjustment amount
                $('#adjustment_amount').attr('max', overpayment);
                $('#max_adjustment').text(formatCurrency(overpayment));
                
                // Default adjustment amount to full overpayment
                $('#adjustment_amount').val(overpayment.toFixed(2));
                
                // Show modal
                $('#adjustOverpaymentModal').modal('show');
            } else {
                alert('Error fetching sale details: ' + (response.message || 'Unknown error'));
            }
        },
        error: function(xhr, status, error) {
            alert('Error fetching sale details. Please try again later.');
            console.error('AJAX Error:', status, error);
        }
    });
} 