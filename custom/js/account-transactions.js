$(document).ready(function() {
    // Debug flag
    const DEBUG = true;
    
    function debug(message, data) {
        if (DEBUG) {
            console.log('[Debug]', message, data || '');
        }
    }

    // Add image preview modal to the page
    if (!$('#imagePreviewModal').length) {
        $('body').append(`
            <div class="modal fade" id="imagePreviewModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title">Transaction Receipt</h4>
                        </div>
                        <div class="modal-body text-center">
                            <div class="receipt-preview-container" style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; min-height: 400px; display: flex; align-items: center; justify-content: center;">
                                <img src="" alt="Receipt Preview" style="max-width: 100%; max-height: 600px; object-fit: contain;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }

    // Function to handle image error
    function handleImageError(imgElement) {
        if (!imgElement.hasAttribute('data-error-handled')) {
            imgElement.setAttribute('data-error-handled', 'true');
            $(imgElement).closest('.thumbnail-wrapper').html(
                '<div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #999;">' +
                '<i class="fa fa-image fa-3x"></i><br>' +
                '<span>No Image</span>' +
                '</div>'
            );
        }
    }

    // Function to show image preview modal
    function showImagePreview(imagePath) {
        debug('Opening preview modal for image:', imagePath);
        var previewModal = $('#imagePreviewModal');
        var previewImage = previewModal.find('img');
        
        // Remove any existing error handlers
        previewImage.off('error');
        
        // Set the image source and error handler
        previewImage.attr('src', imagePath)
            .on('error', function() {
                handleImageError(this);
            });
        
        // Show the modal
        previewModal.modal('show');
    }

    // Handle thumbnail click
    $(document).on('click', '.transaction-receipt-thumbnail', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var imagePath = $(this).data('full-image');
        if (imagePath) {
            showImagePreview(imagePath);
        }
    });

    // Initialize DataTable
    var transactionTable = $('#transactionTable').DataTable({
        "order": [[ 0, "desc" ]],
        "pageLength": 25,
        "columns": [
            { 
                "data": "transaction_date",
                "title": "Transaction Date"
            },
            { "data": "account_owner" },
            { "data": "platform" },
            { "data": "type" },
            { 
                "data": "amount",
                "render": function(data, type, row) {
                    return parseFloat(data).toFixed(2);
                }
            },
            { 
                "data": "status",
                "render": function(data, type, row) {
                    return data === 1 ? '<span class="label label-success">Completed</span>' : 
                           '<span class="label label-warning">Pending</span>';
                }
            },
            {
                "data": null,
                "render": function(data, type, row) {
                    return '<button class="btn btn-info btn-sm view-details" data-id="' + row.id + '">' +
                           '<i class="fa fa-eye"></i> View</button>';
                }
            }
        ]
    });

    // Load accounts into dropdown
    function loadAccounts() {
        debug('Loading accounts...');
        $.ajax({
            url: 'php_action/fetchAccounts.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                debug('Received response:', response);
                if(response.success) {
                    var accounts = response.data;
                    debug('Found ' + accounts.length + ' accounts');
                    
                    var select = $('#accountSelect');
                    select.empty().append('<option value="">Select an account...</option>');
                    
                    accounts.forEach(function(account) {
                        var optionText = account.account_owner + ' - ' + account.account_platform + 
                                       ' (' + account.currency + ')';
                        debug('Adding option: ' + optionText);
                        select.append(new Option(optionText, account.id));
                    });
                    
                    // Trigger Select2 to update
                    select.trigger('change');
                } else {
                    console.error('Error in response:', response.messages);
                    Swal.fire('Error', response.messages || 'Error loading accounts', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                Swal.fire('Error', 'Error loading accounts: ' + error, 'error');
            }
        });
    }

    // Load transactions for selected account
    function loadTransactions(accountId) {
        debug('Loading transactions for account ID: ' + accountId);
        $.ajax({
            url: 'php_action/fetchAccountTransactions.php',
            type: 'GET',
            data: { account_id: accountId },
            dataType: 'json',
            success: function(response) {
                debug('Received transactions:', response);
                if(response.success) {
                    transactionTable.clear().rows.add(response.data).draw();
                    $('#transactionPanel').show();
                } else {
                    console.error('Error loading transactions:', response.messages);
                    Swal.fire('Error', response.messages || 'Error loading transactions', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                Swal.fire('Error', 'Error loading transactions: ' + error, 'error');
            }
        });
    }

    // Handle account selection change
    $('#accountSelect').on('change', function() {
        var accountId = $(this).val();
        debug('Account selected: ' + accountId);
        if(accountId) {
            loadTransactions(accountId);
        } else {
            $('#transactionPanel').hide();
            transactionTable.clear().draw();
        }
    });

    // Update the view details handler
    $('#transactionTable').on('click', '.view-details', function() {
        var transactionId = $(this).data('id');
        debug('Viewing details for transaction ID:', transactionId);
        
        $.ajax({
            url: 'php_action/transactionOperations.php',
            type: 'GET',
            data: { 
                action: 'view',
                id: transactionId 
            },
            dataType: 'json',
            success: function(response) {
                debug('Received transaction details:', response);
                if(response.success) {
                    var transaction = response.data;
                    var imageHtml = '';
                    
                    if (transaction.has_receipt && transaction.receipt_url) {
                        debug('Transaction has receipt:', transaction.receipt_url);
                        imageHtml = '<tr><th>Receipt:</th><td>' +
                            '<div class="receipt-container">' +
                            '<div class="thumbnail-wrapper" style="display: inline-block; margin-bottom: 5px;">' +
                            '<div style="width: 200px; height: 200px; overflow: hidden; border: 1px solid #ddd; border-radius: 4px; background: #f8f9fa; display: flex; align-items: center; justify-content: center;">' +
                            '<img src="' + transaction.receipt_url + '" ' +
                            'class="transaction-receipt-thumbnail" ' +
                            'data-full-image="' + transaction.receipt_url + '" ' +
                            'style="width: 200px; height: 200px; object-fit: contain; cursor: pointer;" ' +
                            'onerror="handleImageError(this);">' +
                            '</div>' +
                            '</div>' +
                            '<div style="display: block;">' +
                            '<small class="text-muted">Click image to view full size</small>' +
                            '</div>' +
                            '</div></td></tr>';
                    }
                    
                    var detailsHtml = '<div class="table-responsive">' +
                        '<table class="table">' +
                        '<tr><th>Transaction ID:</th><td>' + transaction.id + '</td></tr>' +
                        '<tr><th>Date:</th><td>' + transaction.transaction_date + '</td></tr>' +
                        '<tr><th>Account Owner:</th><td>' + transaction.account_owner + '</td></tr>' +
                        '<tr><th>Platform:</th><td>' + transaction.platform + '</td></tr>' +
                        '<tr><th>Type:</th><td>' + transaction.type + '</td></tr>' +
                        '<tr><th>Amount:</th><td>' + parseFloat(transaction.amount).toFixed(2) + ' ' + transaction.currency + '</td></tr>' +
                        '<tr><th>Status:</th><td>' + (transaction.status === 1 ? 'Completed' : 'Pending') + '</td></tr>' +
                        '<tr><th>Comment:</th><td>' + (transaction.comment || 'N/A') + '</td></tr>' +
                        imageHtml +
                        '</table></div>';
                    
                    $('#transactionDetailsModal .modal-body').html(detailsHtml);
                    $('#transactionDetailsModal').modal('show');
                } else {
                    console.error('Error loading transaction details:', response.message);
                    Swal.fire('Error', response.message || 'Error loading transaction details', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr: xhr, status: status, error: error});
                Swal.fire('Error', 'Error loading transaction details: ' + error, 'error');
            }
        });
    });

    debug('Setting up complete, loading accounts...');
    // Load accounts when page loads
    loadAccounts();
}); 