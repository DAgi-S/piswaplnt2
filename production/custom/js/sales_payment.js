$(document).ready(function() {
    // Initialize DataTable
    window.paymentsTable = $('#paymentsTable').DataTable({
        "ajax": {
            "url": "php_action/fetchSalesPayments.php",
            "type": "POST",
            "data": function(d) {
                d.dateRange = $('#dateRange').val();
                d.startDate = $('#startDate').val();
                d.endDate = $('#endDate').val();
                d.paymentStatus = $('#paymentStatus').val();
                d.paymentMethod = $('#paymentMethod').val();
                d.account = $('#account').val();
                d.client = $('#client').val();
            }
        },
        "order": [[3, "desc"]], // Order by date column descending
        "columns": [
            {"data": "payment_id"},
            {"data": "order_number"},
            {"data": "client_name"},
            {
                "data": "payment_date",
                "render": function(data) {
                    if (!data) return '';
                    const date = new Date(data);
                    return date.toLocaleDateString('en-GB');
                }
            },
            {
                "data": "amount",
                "render": function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            {"data": "payment_method"},
            {"data": "account_name"},
            {
                "data": "status",
                "render": function(data) {
                    return getStatusBadge(data);
                }
            },
            {"data": "reference_number"},
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

    // Function to refresh DataTable
    function refreshPaymentsTable() {
        if (window.paymentsTable) {
            window.paymentsTable.ajax.reload(function() {
                // Update summary cards after table refresh is complete
                updateSummaryCards();
            }, false);
        }
    }

    // Helper function to generate status badges
    function getStatusBadge(status) {
        if (!status) return '<span class="badge badge-secondary">UNKNOWN</span>';
        
        const statusClasses = {
            'pending': 'badge-warning',
            'confirmed': 'badge-success',
            'rejected': 'badge-danger'
        };
        
        const badgeClass = statusClasses[status.toLowerCase()] || 'badge-secondary';
        return `<span class="badge ${badgeClass}">${status.toUpperCase()}</span>`;
    }

    // Function to generate action buttons
    function generateActionButtons(row) {
        var buttons = '<div class="btn-group btn-group-sm">';
        
        // View button
        buttons += '<button type="button" class="btn btn-default view-btn" ' +
                  'data-id="' + row.payment_id + '" ' +
                  'data-toggle="tooltip" title="View Details">' +
                  '<i class="fa fa-eye"></i></button>';
        
        // Edit button - only for pending payments
        if (row.status && row.status.toLowerCase() === 'pending') {
            buttons += '<button type="button" class="btn btn-primary edit-btn" ' +
                      'data-id="' + row.payment_id + '" ' +
                      'data-toggle="tooltip" title="Edit Payment">' +
                      '<i class="fa fa-edit"></i></button>';
        }
        
        // Delete button - only for pending payments
        if (row.status && row.status.toLowerCase() === 'pending') {
            buttons += '<button type="button" class="btn btn-danger delete-btn" ' +
                      'data-id="' + row.payment_id + '" ' +
                      'data-toggle="tooltip" title="Delete Payment">' +
                      '<i class="fa fa-trash"></i></button>';
        }
        
        buttons += '</div>';
        return buttons;
    }

    // Load Orders for Select
    function loadOrders() {
        $.ajax({
            url: 'php_action/fetchOrdersForPayment.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var options = '<option value="">Select Order</option>';
                    response.data.forEach(function(order) {
                        options += '<option value="' + order.id + '" ' +
                                  'data-balance="' + order.balance + '">' +
                                  order.order_number + 
                                  (order.transaction_id ? ' (' + order.transaction_id + ')' : '') +
                                  ' - ' + order.client_name + 
                                  ' (Balance: ' + parseFloat(order.balance).toLocaleString('en-US', {
                                      minimumFractionDigits: 2,
                                      maximumFractionDigits: 2
                                  }) + ')</option>';
                    });
                    $('#order_id').html(options);
                } else {
                    console.error('Failed to load orders:', response.messages);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages[0] || 'Failed to load orders'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load orders. Please try again.'
                });
            }
        });
    }

    // Load Accounts for Select
    function loadAccounts() {
        console.log('Loading accounts...');
        $.ajax({
            url: 'php_action/fetchAccountsForSales.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Account response:', response);
                if (response.success && response.data && Array.isArray(response.data)) {
                    // Update filter dropdown
                    let filterAccountSelect = $('#account');
                    filterAccountSelect.empty();
                    filterAccountSelect.append('<option value="">All Accounts</option>');
                    
                    // Update add payment modal dropdown
                    let addPaymentAccountSelect = $('#account_id');
                    addPaymentAccountSelect.empty();
                    addPaymentAccountSelect.append('<option value="">Select Account</option>');
                    
                    // Update edit payment modal dropdown
                    let updatePaymentAccountSelect = $('#update_account_id');
                    updatePaymentAccountSelect.empty();
                    updatePaymentAccountSelect.append('<option value="">Select Account</option>');
                    
                    response.data.forEach(function(account) {
                        let option = $('<option></option>')
                            .attr('value', account.id)
                            .text(account.display_name);
                        
                        // Add to filter dropdown
                        filterAccountSelect.append(option.clone());
                        
                        // Add to add payment modal
                        addPaymentAccountSelect.append(option.clone());
                        
                        // Add to edit payment modal
                        updatePaymentAccountSelect.append(option.clone());
                    });
                } else {
                    console.error('Invalid account data received:', response);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load accounts. Invalid data received.'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to connect to the server. Please check your connection.'
                });
            }
        });
    }

    // Load Clients for Select
    function loadClients() {
        $.get('php_action/fetchClients.php', function(response) {
            if (response.success) {
                var options = '<option value="">All Clients</option>';
                response.data.forEach(function(client) {
                    options += '<option value="' + client.id + '">' + 
                              client.company_name + '</option>';
                });
                $('#client').html(options);
            }
        });
    }

    // Initialize date fields
    function initializeDates() {
        const today = new Date().toISOString().split('T')[0];
        $('#payment_date, #update_payment_date').val(today);
    }

    // Order selection change handler
    $('#order_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var balance = selectedOption.data('balance') || 0;
        $('#max_amount').text(parseFloat(balance).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
    });

    // Date range change handler
    $('#dateRange').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#customDateRange').show();
        } else {
            $('#customDateRange').hide();
            $('#startDate, #endDate').val('');
            refreshPaymentsTable();
        }
    });

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        refreshPaymentsTable();
    });

    // Add payment form submission
    $('#addPaymentForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        var orderId = $('#order_id').val();
        var amount = parseFloat($('#amount').val());
        var balance = parseFloat($('#order_id option:selected').data('balance'));
        
        if (amount > balance) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Payment amount cannot exceed the order balance'
            });
            return false;
        }

        $.ajax({
            url: 'php_action/createSalesPayment.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.messages || 'Payment added successfully'
                    }).then(() => {
                        $('#addPaymentModal').modal('hide');
                        $('#addPaymentForm')[0].reset();
                        refreshPaymentsTable();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages || 'Failed to add payment'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to add payment. Please try again.'
                });
            }
        });
    });

    // View payment details
    $(document).on('click', '.view-btn', function() {
        var paymentId = $(this).data('id');
        
        $.ajax({
            url: 'php_action/fetchSalesPaymentDetails.php',
            type: 'POST',
            data: { payment_id: paymentId },
            dataType: 'json',
            success: function(response) {
                console.log('Payment details response:', response); // Debug log
                if (response.success) {
                    var payment = response.data;
                    
                    // Update modal content
                    $('#view_payment_id').text(payment.payment_id || payment.id);
                    $('#view_order_number').text(payment.order_number);
                    $('#view_client_name').text(payment.client_name);
                    $('#view_payment_date').text(new Date(payment.payment_date).toLocaleDateString('en-GB'));
                    $('#view_amount').text(parseFloat(payment.amount).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }));
                    $('#view_payment_method').text(payment.payment_method);
                    $('#view_account').text(payment.account_name);
                    $('#view_reference').text(payment.reference_number || 'N/A');
                    $('#view_status').html(getStatusBadge(payment.payment_status));
                    $('#view_notes').text(payment.notes || 'No notes');
                    
                    // Update payment proof
                    var proofContainer = $('#view_payment_proof');
                    if (payment.payment_proof) {
                        var fileExt = payment.payment_proof.split('.').pop().toLowerCase();
                        var proofPath = payment.payment_proof;
                        
                        // Handle file display based on type
                        if (fileExt === 'pdf') {
                            proofContainer.html(
                                '<div class="text-center">' +
                                '<embed src="' + proofPath + '" type="application/pdf" width="100%" height="400px">' +
                                '<a href="' + proofPath + '" class="btn btn-primary mt-2" target="_blank">' +
                                '<i class="fa fa-external-link"></i> Open PDF in New Tab</a>' +
                                '</div>'
                            );
                        } else {
                            proofContainer.html(
                                '<div class="text-center">' +
                                '<img src="' + proofPath + '" class="img-responsive" ' +
                                'style="max-width: 100%; margin: auto; border: 1px solid #ddd; padding: 5px;">' +
                                '<a href="' + proofPath + '" class="btn btn-primary mt-2" target="_blank">' +
                                '<i class="fa fa-external-link"></i> View Full Image</a>' +
                                '</div>'
                            );
                        }
                    } else {
                        proofContainer.html(
                            '<div class="alert alert-info text-center">' +
                            '<i class="fa fa-info-circle"></i> No payment proof uploaded' +
                            '</div>'
                        );
                    }
                    
                    // Show/hide action buttons based on status
                    var paymentStatus = (payment.payment_status || '').toLowerCase();
                    if (paymentStatus === 'pending') {
                        $('.confirmPaymentBtn, .rejectPaymentBtn').show();
                    } else {
                        $('.confirmPaymentBtn, .rejectPaymentBtn').hide();
                    }
                    
                    // Store payment ID for actions
                    $('#viewPaymentModal').data('payment-id', payment.payment_id || payment.id);
                    
                    // Show modal
                    $('#viewPaymentModal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages || 'Failed to load payment details'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load payment details'
                });
            }
        });
    });

    // Handle confirm payment button click
    $(document).on('click', '.confirmPaymentBtn', function() {
        var paymentId = $('#viewPaymentModal').data('payment-id');
        
        if (!paymentId) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Payment ID not found'
            });
            return;
        }
        
        Swal.fire({
            title: 'Confirm Payment',
            text: 'Are you sure you want to confirm this payment?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, confirm it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Processing...',
                    text: 'Please wait while we confirm the payment.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'php_action/confirmSalesPayment.php',
                    type: 'POST',
                    data: { payment_id: paymentId },
                    dataType: 'json'
                })
                .done(function(response) {
                    if (response.success) {
                        // Update status badge in modal
                        $('#view_status').html(getStatusBadge('confirmed'));
                        
                        // Hide confirm/reject buttons
                        $('.confirmPaymentBtn, .rejectPaymentBtn').hide();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Confirmed!',
                            text: 'Payment has been confirmed successfully.',
                            allowOutsideClick: false
                        }).then(() => {
                            // Close the modal
                            $('#viewPaymentModal').modal('hide');
                            
                            // Refresh the payments table
                            if (typeof paymentsTable !== 'undefined') {
                                paymentsTable.ajax.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.messages[0] || 'Failed to confirm payment'
                        });
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Error:', error);
                    console.error('Response:', xhr.responseText);
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to process the request. Please try again.'
                    });
                });
            }
        });
    });

    // Reject payment
    $('#rejectPaymentBtn').on('click', function() {
        var paymentId = $('#viewPaymentModal').data('payment-id');
        
        Swal.fire({
            title: 'Reject Payment',
            text: 'Are you sure you want to reject this payment?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, reject it!',
            cancelButtonText: 'No, cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'php_action/rejectSalesPayment.php',
                    type: 'POST',
                    data: { payment_id: paymentId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.messages || 'Payment rejected successfully'
                            }).then(() => {
                                $('#viewPaymentModal').modal('hide');
                                refreshPaymentsTable();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.messages || 'Failed to reject payment'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to reject payment'
                        });
                    }
                });
            }
        });
    });

    // Edit payment
    $(document).on('click', '.edit-btn', function() {
        var paymentId = $(this).data('id');
        
        $.ajax({
            url: 'php_action/fetchSalesPaymentDetails.php',
            type: 'POST',
            data: { payment_id: paymentId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var payment = response.data;
                    
                    // Update form fields
                    $('#update_payment_id').val(payment.payment_id);
                    $('#update_payment_date').val(payment.payment_date.split(' ')[0]);
                    $('#update_amount').val(payment.amount);
                    $('#update_account_id').val(payment.account_id);
                    $('#update_payment_method').val(payment.payment_method);
                    $('#update_reference_number').val(payment.reference_number);
                    $('#update_notes').val(payment.notes);
                    
                    // Update current proof display
                    if (payment.payment_proof) {
                        $('#current_proof').text('Current file: ' + payment.payment_proof.split('/').pop());
                    } else {
                        $('#current_proof').text('No file uploaded');
                    }
                    
                    // Show modal
                    $('#updatePaymentModal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages || 'Failed to load payment details'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load payment details'
                });
            }
        });
    });

    // Update payment form submission
    $('#updatePaymentForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        
        $.ajax({
            url: 'php_action/updateSalesPayment.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.messages || 'Payment updated successfully'
                    }).then(() => {
                        $('#updatePaymentModal').modal('hide');
                        $('#updatePaymentForm')[0].reset();
                        refreshPaymentsTable();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.messages || 'Failed to update payment'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update payment'
                });
            }
        });
    });

    // Delete payment
    $(document).on('click', '.delete-btn', function() {
        var paymentId = $(this).data('id');
        
        Swal.fire({
            title: 'Delete Payment',
            text: 'Are you sure you want to delete this payment?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'php_action/deleteSalesPayment.php',
                    type: 'POST',
                    data: { payment_id: paymentId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.messages || 'Payment deleted successfully'
                            }).then(() => {
                                refreshPaymentsTable();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.messages || 'Failed to delete payment'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to delete payment'
                        });
                    }
                });
            }
        });
    });

    // Initialize modals
    $('#addPaymentModal').on('show.bs.modal', function() {
        loadOrders();
        loadAccounts();
        initializeDates();
    });

    $('#updatePaymentModal').on('show.bs.modal', function() {
        loadAccounts();
    });

    // Initialize page
    loadClients();
    loadAccounts();
    initializeDates();

    // Initial load of summary cards
    updateSummaryCards();

    // Initialize page
    $('#customDateRange').hide();
    $('[data-toggle="tooltip"]').tooltip();

    // Account selection change handler
    $('#account_id, #update_account_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var owner = selectedOption.data('owner');
        var currency = selectedOption.data('currency');
        
        // Update currency display if needed
        var amountLabel = $(this).closest('form').find('label[for="amount"]');
        if (currency) {
            amountLabel.html('Amount (' + currency + ') <span class="text-danger">*</span>');
        } else {
            amountLabel.html('Amount <span class="text-danger">*</span>');
        }
    });
});

// Function to update summary cards
function updateSummaryCards() {
    var filterData = {
        dateRange: $('#dateRange').val(),
        startDate: $('#startDate').val(),
        endDate: $('#endDate').val(),
        paymentStatus: $('#paymentStatus').val(),
        paymentMethod: $('#paymentMethod').val(),
        account: $('#account').val(),
        client: $('#client').val()
    };

    $.ajax({
        url: 'php_action/fetchSalesPaymentSummary.php',
        type: 'POST',
        data: filterData,
        dataType: 'json',
        beforeSend: function() {
            // Show loading state
            $('#totalPayments, #totalAmount, #pendingPayments, #pendingAmount').html('<i class="fa fa-spinner fa-spin"></i>');
        },
        success: function(response) {
            if (response && response.success) {
                // Format numbers with commas and decimals
                var formatNumber = function(num) {
                    return parseFloat(num || 0).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                };

                // Update the summary cards
                $('#totalPayments').text(response.data.total_payments || 0);
                $('#totalAmount').text(formatNumber(response.data.total_amount));
                $('#pendingPayments').text(response.data.pending_payments || 0);
                $('#pendingAmount').text(formatNumber(response.data.pending_amount));
            } else {
                // Reset to zeros if there's an error
                $('#totalPayments').text('0');
                $('#totalAmount').text('0.00');
                $('#pendingPayments').text('0');
                $('#pendingAmount').text('0.00');
                
                console.error('Failed to fetch summary:', response ? response.messages : 'No response');
            }
        },
        error: function(xhr, status, error) {
            // Reset to zeros on error
            $('#totalPayments').text('0');
            $('#totalAmount').text('0.00');
            $('#pendingPayments').text('0');
            $('#pendingAmount').text('0.00');

            console.error('AJAX Error:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            // Show error message to user
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to update summary. Please try refreshing the page.'
            });
        }
    });
} 