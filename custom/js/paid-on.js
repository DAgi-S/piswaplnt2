var managePaymentTable;

$(document).ready(function() {
    // Initialize DataTable with AJAX source
    managePaymentTable = $('#managePaymentTable').DataTable({
        'ajax': {
            'url': 'php_action/fetchPayments.php',
            'data': function(d) {
                // Add filter parameters
                d.typeFilter = $('#typeFilter').val();
                d.platformFilter = $('#platformFilter').val();
                d.dateFilter = $('#dateFilter').val();
            }
        },
        'order': [],
        'columns': [
            {data: null, render: function(data, type, row, meta) {
                return meta.row + 1;
            }},
            {data: 'name'},
            {data: 'type'},
            {data: 'platform'},
            {data: 'amount', render: function(data) {
                return parseFloat(data).toFixed(2);
            }},
            {data: 'id'},
            {data: null, render: function(data) {
                return '<div class="btn-group">' +
                    '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                    'Action <span class="caret"></span>' +
                    '</button>' +
                    '<ul class="dropdown-menu">' +
                    '<li><a href="#" onclick="viewPaymentDetails(' + data.id + ')"><i class="glyphicon glyphicon-eye-open"></i> View Details</a></li>' +
                    '<li><a href="#" onclick="editPaidOn(' + data.id + ')"><i class="glyphicon glyphicon-edit"></i> Change Paid On</a></li>' +
                    '<li><a href="#" onclick="printPayment(' + data.id + ')"><i class="glyphicon glyphicon-print"></i> Print</a></li>' +
                    '</ul>' +
                    '</div>';
            }}
        ]
    });

    // Handle filter button click
    $('#filterBtn').on('click', function() {
        managePaymentTable.ajax.reload();
    });

    // Handle reset button click
    $('#resetBtn').on('click', function() {
        $('#typeFilter').val('');
        $('#platformFilter').val('');
        $('#dateFilter').val('');
        managePaymentTable.ajax.reload();
    });

    // Handle edit paid on form submission
    $("#editPaidOnForm").unbind('submit').bind('submit', function() {
        var form = $(this);
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'json',
            success:function(response) {
                if(response.success == true) {
                    $("#edit-paidon-messages").html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                        '</div>');

                    managePaymentTable.ajax.reload(null, false);
                    $("#editPaidOnModal").modal('hide');
                } else {
                    $("#edit-paidon-messages").html('<div class="alert alert-warning">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.messages +
                        '</div>');
                }
            }
        });
        return false;
    });
});

function viewPaymentDetails(paymentId) {
    $.ajax({
        url: 'php_action/getPaymentDetails.php',
        type: 'post',
        data: {paymentId: paymentId},
        dataType: 'json',
        success:function(response) {
            // Create HTML content for payment details
            var html = '<table class="table table-bordered">';
            html += '<tr><th>Digital Swap ID</th><td>' + response.id + '</td></tr>';
            html += '<tr><th>Account Name</th><td>' + response.name + '</td></tr>';
            html += '<tr><th>Type</th><td>' + response.type + '</td></tr>';
            html += '<tr><th>Platform</th><td>' + response.platform + '</td></tr>';
            html += '<tr><th>Amount</th><td>' + parseFloat(response.amount).toFixed(2) + '</td></tr>';
            html += '<tr><th>Transaction Date</th><td>' + response.transaction_date + '</td></tr>';
            if(response.comment) {
                html += '<tr><th>Comment</th><td>' + response.comment + '</td></tr>';
            }
            html += '</table>';
            
            $('#payment-details-content').html(html);
            $('#viewPaymentModal').modal('show');
        }
    });
}

function editPaidOn(paymentId) {
    $('#paymentId').val(paymentId);
    $.ajax({
        url: 'php_action/getPaymentDetails.php',
        type: 'post',
        data: {paymentId: paymentId},
        dataType: 'json',
        success:function(response) {
            $("#editPlatform").val(response.platform);
            $("#editPaidOnModal").modal('show');
        }
    });
}

function printPayment(paymentId) {
    window.open('php_action/printPayment.php?id=' + paymentId, '_blank');
} 