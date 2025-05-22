<?php 
require_once 'php_action/core.php';
require_once 'includes/header.php';

// Check if sale ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>No sale ID provided.</div>";
    require_once 'includes/footer.php';
    exit;
}

$saleId = $_GET['id'];

// Get sale details
$query = "SELECT 
            so.id,
            so.order_number as sale_number,
            so.order_date as date,
            c.company_name as client_name,
            so.subtotal,
            so.tax_amount as vat,
            so.withholding_amount as withholding,
            so.discount_amount as discount,
            so.total_amount as grand_total,
            so.paid_amount,
            (so.total_amount - so.paid_amount) as balance,
            so.payment_status,
            u.username as created_by
          FROM sales_orders so
          LEFT JOIN clients c ON so.client_id = c.id
          LEFT JOIN users u ON so.created_by = u.user_id
          WHERE so.id = ?";
          
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $saleId);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    echo "<div class='alert alert-danger'>Sale not found.</div>";
    require_once 'includes/footer.php';
    exit;
}

$sale = $result->fetch_assoc();

// Get payments for this sale
$query = "SELECT 
            sp.id,
            sp.payment_date,
            sp.amount,
            sp.payment_method,
            sp.reference_number,
            sp.notes,
            sp.status,
            sp.payment_proof,
            a.account_owner,
            a.account_platform,
            a.currency,
            u.username as created_by,
            sp.created_at
          FROM sales_payments sp
          LEFT JOIN accounts a ON sp.account_id = a.id
          LEFT JOIN users u ON sp.created_by = u.user_id
          WHERE sp.sales_order_id = ?
          ORDER BY sp.payment_date DESC, sp.id DESC";
          
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $saleId);
$stmt->execute();
$paymentsResult = $stmt->get_result();

// Format currency
function formatCurrency($amount) {
    return number_format((float)$amount, 2, '.', ',');
}
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="sales_detail.php">Sales</a></li>
            <li class="active">Payment History</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="glyphicon glyphicon-credit-card"></i> Payment History for Sale #<?php echo $sale['sale_number']; ?>
                    <div class="pull-right">
                        <a href="#" class="btn btn-primary btn-sm" onclick="updatePaymentStatus(<?php echo $saleId; ?>)">
                            <i class="glyphicon glyphicon-plus"></i> Add New Payment
                        </a>
                        <a href="sales_detail.php" class="btn btn-default btn-sm">
                            <i class="glyphicon glyphicon-arrow-left"></i> Back to Sales
                        </a>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Sale Information</h4>
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 30%;">Sale Number</th>
                                <td><?php echo $sale['sale_number']; ?></td>
                            </tr>
                            <tr>
                                <th>Client</th>
                                <td><?php echo $sale['client_name']; ?></td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td><?php echo date('d/m/Y', strtotime($sale['date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Created By</th>
                                <td><?php echo $sale['created_by']; ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h4>Payment Information</h4>
                        <table class="table table-bordered">
                            <tr>
                                <th style="width: 30%;">Status</th>
                                <td>
                                    <?php 
                                    $statusClass = '';
                                    switch(strtolower($sale['payment_status'])) {
                                        case 'paid': $statusClass = 'success'; break;
                                        case 'partial': $statusClass = 'warning'; break;
                                        default: $statusClass = 'danger';
                                    }
                                    ?>
                                    <span class="label label-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($sale['payment_status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Total Amount</th>
                                <td><?php echo formatCurrency($sale['grand_total']); ?></td>
                            </tr>
                            <tr>
                                <th>Paid Amount</th>
                                <td><?php echo formatCurrency($sale['paid_amount']); ?></td>
                            </tr>
                            <tr>
                                <th>Balance</th>
                                <td><?php echo formatCurrency($sale['balance']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <h4>Payment History</h4>
                        <?php if ($paymentsResult->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Account</th>
                                            <th>Reference</th>
                                            <th>Status</th>
                                            <th>Created By</th>
                                            <th>Notes</th>
                                            <th>Payment Proof</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $counter = 1;
                                        while ($payment = $paymentsResult->fetch_assoc()): 
                                            $statusClass = '';
                                            switch(strtolower($payment['status'])) {
                                                case 'approved': $statusClass = 'success'; break;
                                                case 'pending': $statusClass = 'warning'; break;
                                                case 'rejected': $statusClass = 'danger'; break;
                                                default: $statusClass = 'default';
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo $counter++; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                                                <td class="text-right"><?php echo formatCurrency($payment['amount']); ?></td>
                                                <td><?php echo $payment['payment_method']; ?></td>
                                                <td><?php echo $payment['account_owner'] . ' - ' . $payment['account_platform'] . ' (' . $payment['currency'] . ')'; ?></td>
                                                <td><?php echo $payment['reference_number'] ? $payment['reference_number'] : '-'; ?></td>
                                                <td>
                                                    <span class="label label-<?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($payment['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $payment['created_by']; ?></td>
                                                <td><?php echo $payment['notes'] ? $payment['notes'] : '-'; ?></td>
                                                <td>
                                                    <?php if ($payment['payment_proof'] && $payment['payment_proof'] != 'NULL' && $payment['payment_proof'] != ''): ?>
                                                        <a href="<?php echo $payment['payment_proof']; ?>" class="btn btn-xs btn-info" target="_blank">
                                                            <i class="glyphicon glyphicon-eye-open"></i> View
                                                        </a>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No payment records found for this sale.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Payment Modal (reused from sales_detail.php) -->
<div class="modal fade" id="updatePaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-usd"></i> Add New Payment</h4>
            </div>
            <form id="addPaymentForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Sale Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%;">Sale Number</th>
                                    <td id="update_sale_number"></td>
                                </tr>
                                <tr>
                                    <th>Client</th>
                                    <td id="update_client_name"></td>
                                </tr>
                                <tr>
                                    <th>Date</th>
                                    <td id="update_sale_date"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Payment Information</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 30%;">Status</th>
                                    <td id="update_payment_status"></td>
                                </tr>
                                <tr>
                                    <th>Total Amount</th>
                                    <td id="update_total_amount"></td>
                                </tr>
                                <tr>
                                    <th>Paid Amount</th>
                                    <td id="update_paid_amount"></td>
                                </tr>
                                <tr>
                                    <th>Balance</th>
                                    <td id="update_balance"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Add New Payment</h5>
                            <div class="form-group">
                                <label for="select_account">Account <span class="text-danger">*</span></label>
                                <select class="form-control" id="select_account" name="account_id" required>
                                    <option value="">Select Account</option>
                                    <!-- Will be populated from the database -->
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" required value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_method">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-control" id="payment_method" name="payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Check">Check</option>
                                    <option value="Creditcard">Credit Card</option>
                                    <option value="Paypal">PayPal</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_amount">Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="payment_amount" name="amount" required step="0.01" min="0.01">
                                <small class="text-muted">Maximum amount: <span id="max_amount">0.00</span></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="reference_number">Reference Number</label>
                                <input type="text" class="form-control" id="reference_number" name="reference_number" placeholder="Reference number, transaction ID, etc.">
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_proof">Payment Proof (Image/PDF)</label>
                                <input type="file" id="payment_proof" name="payment_proof" accept="image/*,application/pdf">
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_notes">Notes</label>
                                <textarea class="form-control" id="payment_notes" name="notes" rows="3" placeholder="Additional notes or comments"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="savePaymentBtn">
                        <i class="glyphicon glyphicon-plus"></i> Add Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Required JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="custom/js/sales_detail.js"></script>
<script>
$(document).ready(function() {
    // Handle payment form submission (reused from sales_detail.js)
    $('#addPaymentForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate the amount against the balance
        var amount = parseFloat($('#payment_amount').val());
        var balance = parseFloat($('#payment_amount').attr('max'));
        
        if (amount <= 0) {
            alert('Payment amount must be greater than zero');
            return false;
        }
        
        if (amount > balance) {
            alert('Payment amount cannot exceed the remaining balance of ' + formatCurrency(balance));
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
                    
                    // Show success message
                    alert('Payment added successfully');
                    
                    // Reset form
                    $('#addPaymentForm')[0].reset();
                    
                    // Refresh the page to show the new payment
                    location.reload();
                } else {
                    alert('Error: ' + (response.message || 'Could not add payment'));
                }
            },
            error: function(xhr, status, error) {
                alert('Error adding payment. Please try again later.');
                console.error('AJAX Error:', status, error);
            }
        });
    });
});

// Update payment status - Reused and simplified for this page
function updatePaymentStatus(saleId) {
    // First load the accounts with fallback mechanism
    console.log('Fetching accounts for payment form...');
    
    // Clear and add placeholder to the account dropdown
    $('#select_account').empty().append('<option value="">Loading accounts...</option>');
    
    // Try primary account fetching method
    $.ajax({
        url: 'php_action/fetchAccountsForSales.php',
        type: 'GET',
        dataType: 'json',
        cache: false,
        success: function(accountsResponse) {
            console.log('Account response:', accountsResponse);
            
            // Clear the dropdown and add default option
            $('#select_account').empty().append('<option value="">Select Account</option>');
            
            // Add account options if available
            if(accountsResponse.success && accountsResponse.data && accountsResponse.data.length > 0) {
                $.each(accountsResponse.data, function(index, account) {
                    var displayName = account.display_name || (account.account_owner + ' - ' + account.account_platform + ' (' + account.currency + ')');
                    $('#select_account').append(`<option value="${account.id}">${displayName}</option>`);
                });
                console.log(`Loaded ${accountsResponse.data.length} accounts`);
            } else {
                console.error('No accounts found or request failed, trying fallback...');
                fetchAccountsFallback();
            }
            
            // Proceed with loading sale details
            fetchSaleDetails();
        },
        error: function(xhr, status, error) {
            console.error('Failed to fetch accounts:', {status: status, error: error});
            fetchAccountsFallback();
            
            // Proceed with loading sale details
            fetchSaleDetails();
        }
    });
    
    // Fallback function for fetching accounts
    function fetchAccountsFallback() {
        console.log('Trying fallback account fetch method...');
        
        $.ajax({
            url: 'php_action/fetchAccounts.php',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function(response) {
                console.log('Fallback account response:', response);
                
                // Clear the dropdown and add default option
                $('#select_account').empty().append('<option value="">Select Account</option>');
                
                if(response.success && response.data && response.data.length > 0) {
                    // Add account options
                    $.each(response.data, function(index, account) {
                        var displayName = account.owner + ' - ' + account.name + ' (' + account.currency + ')';
                        $('#select_account').append(`<option value="${account.id}">${displayName}</option>`);
                    });
                    console.log(`Loaded ${response.data.length} accounts (fallback)`);
                } else {
                    // No accounts found
                    $('#select_account').empty().append('<option value="">No accounts found</option>');
                    
                    setTimeout(function() {
                        alert('No accounts found. Please add accounts before processing payments.');
                    }, 500);
                }
            },
            error: function(xhr, status, error) {
                console.error('Fallback accounts fetch failed:', {status: status, error: error});
                $('#select_account').empty().append('<option value="">Error loading accounts</option>');
            }
        });
    }
    
    // Function to fetch sale details
    function fetchSaleDetails() {
        $.ajax({
            url: 'php_action/fetchSingleSale.php',
            type: 'POST',
            data: { sale_id: saleId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Store sale ID in the update payment modal
                    $('#updatePaymentModal').data('sale-id', saleId);
                    
                    // Fill sale information
                    $('#update_sale_number').text(response.data.sale_number);
                    $('#update_client_name').text(response.data.client_name);
                    $('#update_sale_date').text(moment(response.data.date).format('DD/MM/YYYY'));
                    
                    // Fill payment information
                    var statusClass = '';
                    switch(response.data.payment_status.toLowerCase()) {
                        case 'paid': statusClass = 'success'; break;
                        case 'partial': statusClass = 'warning'; break;
                        default: statusClass = 'danger';
                    }
                    $('#update_payment_status').html(`<span class="label label-${statusClass}">${response.data.payment_status}</span>`);
                    $('#update_total_amount').text(formatCurrency(response.data.grand_total));
                    $('#update_paid_amount').text(formatCurrency(response.data.paid_amount));
                    $('#update_balance').text(formatCurrency(response.data.balance));
                    
                    // Set the maximum amount for new payment
                    $('#payment_amount').attr('max', response.data.balance);
                    $('#max_amount').text(formatCurrency(response.data.balance));
                    
                    // Show modal
                    $('#updatePaymentModal').modal('show');
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
}

// Helper function to format currency
function formatCurrency(amount) {
    return parseFloat(amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
</script>

<?php require_once 'includes/footer.php'; ?> 