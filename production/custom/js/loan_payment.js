$(document).ready(function() {
    // Initialize datepicker
    $('#payment_date').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });

    // Initialize select2 for dropdowns
    $('.select2').select2();

    // Initialize DataTable for payments list
    var paymentsTable = $('#paymentsTable').DataTable({
        'ajax': 'php_action/fetchLoanPayments.php',
        'order': [[0, 'desc']],
        'dom': 'Bfrtip',
        'buttons': ['copy', 'csv', 'excel', 'pdf', 'print'],
        'columns': [
            { data: 'payment_id' },
            { data: 'loan_id' },
            { data: 'client_name' },
            { 
                data: 'payment_amount',
                render: function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        style: 'currency',
                        currency: 'ETB'
                    });
                }
            },
            { data: 'payment_date' },
            { data: 'payment_method' },
            { data: 'account_name' },
            { data: 'reference_number' },
            { 
                data: null,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                                Action <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <li><a href="#" class="view-payment" data-id="${row.payment_id}"><i class="fa fa-eye"></i> View</a></li>
                                <li><a href="#" class="print-receipt" data-id="${row.payment_id}"><i class="fa fa-print"></i> Print Receipt</a></li>
                            </ul>
                        </div>`;
                }
            }
        ]
    });

    // Form validation and submission
    $('#addLoanPaymentForm').validate({
        rules: {
            loan_id: { required: true },
            payment_amount: { 
                required: true,
                number: true,
                min: 0.01
            },
            payment_date: { required: true },
            payment_method_id: { required: true },
            account_id: { required: true }
        },
        messages: {
            loan_id: "Please select a loan",
            payment_amount: {
                required: "Please enter payment amount",
                number: "Please enter a valid number",
                min: "Amount must be greater than 0"
            },
            payment_date: "Please select payment date",
            payment_method_id: "Please select payment method",
            account_id: "Please select account"
        },
        submitHandler: function(form) {
            var formData = new FormData(form);
            
            $.ajax({
                url: 'php_action/createLoanPayment.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.messages
                        }).then((result) => {
                            // Reset form
                            $('#addLoanPaymentForm')[0].reset();
                            $('.select2').val('').trigger('change');
                            
                            // Close modal
                            $('#addPaymentModal').modal('hide');
                            
                            // Refresh table
                            paymentsTable.ajax.reload();
                            
                            // Print receipt if needed
                            if(response.payment_id) {
                                window.open('print_payment_receipt.php?id=' + response.payment_id, '_blank');
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.messages
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while processing your request.'
                    });
                }
            });
        }
    });

    // Load loan details when loan is selected
    $('#loan_id').on('change', function() {
        var loanId = $(this).val();
        if(loanId) {
            $.ajax({
                url: 'php_action/fetchLoanDetails.php',
                type: 'POST',
                data: { loan_id: loanId },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        $('#loan_details').html(`
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Client:</strong> ${response.data.client_name}</p>
                                    <p><strong>Loan Amount:</strong> ${response.data.loan_amount}</p>
                                    <p><strong>Interest Rate:</strong> ${response.data.interest_rate}%</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Total Amount:</strong> ${response.data.total_amount}</p>
                                    <p><strong>Paid Amount:</strong> ${response.data.paid_amount}</p>
                                    <p><strong>Remaining:</strong> ${response.data.remaining_amount}</p>
                                </div>
                            </div>
                        `);
                    }
                }
            });
        } else {
            $('#loan_details').html('');
        }
    });

    // View payment details
    $(document).on('click', '.view-payment', function(e) {
        e.preventDefault();
        var paymentId = $(this).data('id');
        
        $.ajax({
            url: 'php_action/fetchPaymentDetails.php',
            type: 'POST',
            data: { payment_id: paymentId },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#viewPaymentModal').modal('show');
                    $('#paymentDetails').html(`
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Payment ID</th>
                                    <td>${response.data.payment_id}</td>
                                    <th>Loan ID</th>
                                    <td>${response.data.loan_id}</td>
                                </tr>
                                <tr>
                                    <th>Client</th>
                                    <td>${response.data.client_name}</td>
                                    <th>Amount</th>
                                    <td>${response.data.payment_amount}</td>
                                </tr>
                                <tr>
                                    <th>Payment Date</th>
                                    <td>${response.data.payment_date}</td>
                                    <th>Payment Method</th>
                                    <td>${response.data.payment_method}</td>
                                </tr>
                                <tr>
                                    <th>Account</th>
                                    <td>${response.data.account_name}</td>
                                    <th>Reference</th>
                                    <td>${response.data.reference_number || '-'}</td>
                                </tr>
                                <tr>
                                    <th>Notes</th>
                                    <td colspan="3">${response.data.notes || '-'}</td>
                                </tr>
                            </table>
                        </div>
                    `);
                }
            }
        });
    });

    // Print receipt
    $(document).on('click', '.print-receipt', function(e) {
        e.preventDefault();
        var paymentId = $(this).data('id');
        window.open('print_payment_receipt.php?id=' + paymentId, '_blank');
    });
}); 