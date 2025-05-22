var manageLoansTable;
var paymentHistoryTable;

$(document).ready(function() {
    // Initialize DataTable for loans
    manageLoansTable = $('#loansTable').DataTable({
        'ajax': 'php_action/fetchLoans.php',
        'order': [],
        'columns': [
            { data: 'loan_id' },
            { data: 'borrower_name' },
            { data: 'loan_type' },
            { 
                data: 'loan_amount',
                render: function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        style: 'currency',
                        currency: 'ETB'
                    });
                }
            },
            { 
                data: 'interest_rate',
                render: function(data) {
                    return data + '%';
                }
            },
            { data: 'loan_date' },
            { data: 'due_date' },
            {
                data: 'status',
                render: function(data) {
                    let badgeClass = '';
                    switch(data.toLowerCase()) {
                        case 'active':
                            badgeClass = 'label-success';
                            break;
                        case 'overdue':
                            badgeClass = 'label-danger';
                            break;
                        case 'completed':
                            badgeClass = 'label-info';
                            break;
                        default:
                            badgeClass = 'label-default';
                    }
                    return '<span class="label ' + badgeClass + '">' + data + '</span>';
                }
            },
            {
                data: null,
                render: function(data) {
                    return '<div class="btn-group">' +
                           '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                           'Action <span class="caret"></span>' +
                           '</button>' +
                           '<ul class="dropdown-menu">' +
                           '<li><a href="#" class="view-loan" data-id="' + data.loan_id + '"><i class="fa fa-eye"></i> View</a></li>' +
                           '<li><a href="#" class="edit-loan" data-id="' + data.loan_id + '"><i class="fa fa-pencil"></i> Edit</a></li>' +
                           '<li><a href="#" class="add-payment" data-id="' + data.loan_id + '"><i class="fa fa-money"></i> Add Payment</a></li>' +
                           '</ul></div>';
                }
            }
        ]
    });

    // Load borrower types in add loan form
    $('#borrowerType').on('change', function() {
        var type = $(this).val();
        if(type) {
            $.ajax({
                url: 'php_action/fetchBorrowers.php',
                type: 'post',
                data: {type: type},
                dataType: 'json',
                success: function(response) {
                    $('#borrowerId').empty();
                    $('#borrowerId').append('<option value="">Select Borrower</option>');
                    $.each(response, function(index, item) {
                        $('#borrowerId').append('<option value="' + item.id + '">' + item.name + '</option>');
                    });
                }
            });
        }
    });

    // Load payment methods
    $.ajax({
        url: 'php_action/fetchPaymentMethods.php',
        type: 'get',
        dataType: 'json',
        success: function(response) {
            $('#paymentMethod').empty();
            $('#paymentMethod').append('<option value="">Select Method</option>');
            $.each(response, function(index, item) {
                $('#paymentMethod').append('<option value="' + item.id + '">' + item.name + '</option>');
            });
        }
    });

    // Load accounts
    $.ajax({
        url: 'php_action/fetchAccounts.php',
        type: 'get',
        dataType: 'json',
        success: function(response) {
            $('#account').empty();
            $('#account').append('<option value="">Select Account</option>');
            $.each(response, function(index, item) {
                $('#account').append('<option value="' + item.id + '">' + item.name + '</option>');
            });
        }
    });

    // Add new loan
    $('#addLoanForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'post',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addLoanModal').modal('hide');
                    $('#addLoanForm')[0].reset();
                    manageLoansTable.ajax.reload(null, false);
                    
                    $('.messages').html('<div class="alert alert-success alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        '<strong> <i class="glyphicon glyphicon-ok-sign"></i> </strong>' + response.messages +
                        '</div>');
                    
                    loadDashboardData();
                } else {
                    $('.messages').html('<div class="alert alert-warning alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        '<strong> <i class="glyphicon glyphicon-exclamation-sign"></i> </strong>' + response.messages +
                        '</div>');
                }
            }
        });
    });

    // View loan details
    $(document).on('click', '.view-loan', function(e) {
        e.preventDefault();
        var loanId = $(this).data('id');
        
        $.ajax({
            url: 'php_action/fetchLoanDetails.php',
            type: 'post',
            data: {loanId: loanId},
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    var loan = response.loan;
                    $('#view-loanId').text(loan.loan_id);
                    $('#view-borrower').text(loan.borrower_name);
                    $('#view-amount').text(parseFloat(loan.loan_amount).toLocaleString('en-US', {
                        style: 'currency',
                        currency: 'ETB'
                    }));
                    $('#view-interestRate').text(loan.interest_rate + '%');
                    $('#view-loanDate').text(loan.loan_date);
                    $('#view-dueDate').text(loan.due_date);
                    $('#view-status').html('<span class="label label-' + getLoanStatusClass(loan.status) + '">' + loan.status + '</span>');
                    
                    // Payment summary
                    $('#view-totalAmount').text(parseFloat(loan.total_amount).toLocaleString('en-US', {
                        style: 'currency',
                        currency: 'ETB'
                    }));
                    $('#view-paidAmount').text(parseFloat(loan.paid_amount).toLocaleString('en-US', {
                        style: 'currency',
                        currency: 'ETB'
                    }));
                    $('#view-remainingAmount').text(parseFloat(loan.remaining_amount).toLocaleString('en-US', {
                        style: 'currency',
                        currency: 'ETB'
                    }));
                    $('#view-nextPaymentDue').text(loan.next_payment_due);
                    
                    // Payment history
                    var paymentHistory = '';
                    $.each(response.payments, function(index, payment) {
                        paymentHistory += '<tr>' +
                            '<td>' + payment.payment_date + '</td>' +
                            '<td>' + parseFloat(payment.amount).toLocaleString('en-US', {
                                style: 'currency',
                                currency: 'ETB'
                            }) + '</td>' +
                            '<td>' + payment.payment_method + '</td>' +
                            '<td>' + payment.reference_number + '</td>' +
                            '<td><span class="label label-' + getPaymentStatusClass(payment.status) + '">' + payment.status + '</span></td>' +
                            '</tr>';
                    });
                    $('#paymentHistoryBody').html(paymentHistory);
                    
                    $('#viewLoanModal').modal('show');
                }
            }
        });
    });

    // Add payment button in view modal
    $('#addPaymentBtn').on('click', function() {
        var loanId = $('#view-loanId').text();
        $('#paymentLoanId').val(loanId);
        $('#viewLoanModal').modal('hide');
        $('#addPaymentModal').modal('show');
    });

    // Add new payment
    $('#addPaymentForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'post',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#addPaymentModal').modal('hide');
                    $('#addPaymentForm')[0].reset();
                    manageLoansTable.ajax.reload(null, false);
                    
                    $('.messages').html('<div class="alert alert-success alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        '<strong> <i class="glyphicon glyphicon-ok-sign"></i> </strong>' + response.messages +
                        '</div>');
                    
                    loadDashboardData();
                } else {
                    $('.messages').html('<div class="alert alert-warning alert-dismissible" role="alert">' +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        '<strong> <i class="glyphicon glyphicon-exclamation-sign"></i> </strong>' + response.messages +
                        '</div>');
                }
            }
        });
    });

    // Load dashboard data
    function loadDashboardData() {
        $.ajax({
            url: 'php_action/fetchLoanDashboard.php',
            type: 'get',
            dataType: 'json',
            success: function(response) {
                $('#totalActiveLoans').text(response.active_loans);
                $('#totalPaidAmount').text(parseFloat(response.total_paid).toLocaleString('en-US', {
                    style: 'currency',
                    currency: 'ETB'
                }));
                $('#pendingPayments').text(response.pending_payments);
                $('#overdueLoans').text(response.overdue_loans);
            }
        });
    }

    // Helper functions
    function getLoanStatusClass(status) {
        switch(status.toLowerCase()) {
            case 'active':
                return 'success';
            case 'overdue':
                return 'danger';
            case 'completed':
                return 'info';
            default:
                return 'default';
        }
    }

    function getPaymentStatusClass(status) {
        switch(status.toLowerCase()) {
            case 'confirmed':
                return 'success';
            case 'pending':
                return 'warning';
            case 'rejected':
                return 'danger';
            default:
                return 'default';
        }
    }

    // Initial load
    loadDashboardData();
}); 