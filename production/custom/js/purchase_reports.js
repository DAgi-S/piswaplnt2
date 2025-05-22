$(document).ready(function() {
    // Initialize select picker
    $('.selectpicker').selectpicker();

    // Load suppliers
    loadSuppliers();

    // Handle date range selection
    $('#dateRange').on('change', function() {
        if($(this).val() === 'custom') {
            $('#customDateRange').show();
        } else {
            $('#customDateRange').hide();
        }
    });

    // Initialize DataTable
    var reportTable = $("#purchaseReportTable").DataTable({
        'ajax': {
            url: 'php_action/fetchPurchaseReports.php',
            type: 'POST',
            data: function(data) {
                // Add filter parameters
                data.dateRange = $('#dateRange').val();
                data.startDate = $('#startDate').val();
                data.endDate = $('#endDate').val();
                data.supplier = $('#supplier').val();
                data.paymentStatus = $('#paymentStatus').val();
                return data;
            }
        },
        'order': [[1, 'desc']], // Sort by date descending
        'dom': 'Bfrtip',
        'buttons': [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        'columnDefs': [
            {
                'targets': [8], // Payment Status column
                'render': function(data, type, row) {
                    var statusClass = '';
                    switch(data.toLowerCase()) {
                        case 'unpaid':
                            statusClass = 'danger';
                            break;
                        case 'partially_paid':
                            statusClass = 'warning';
                            break;
                        case 'paid':
                            statusClass = 'success';
                            break;
                    }
                    return '<span class="label label-'+statusClass+'">'+data+'</span>';
                }
            },
            {
                'targets': [9], // Options column
                'orderable': false,
                'render': function(data, type, row) {
                    return '<button class="btn btn-default btn-sm" onclick="viewPurchaseDetails(\''+row[0]+'\')">' +
                           '<i class="fa fa-eye"></i></button>';
                }
            },
            {
                'targets': [3,4,5,6,7], // Amount columns
                'render': function(data, type, row) {
                    return parseFloat(data).toFixed(2);
                }
            }
        ]
    });

    // Handle filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        reportTable.ajax.reload();
        updateSummary();
    });

    // Initial summary update
    updateSummary();

    // Handle Excel export
    $('#exportExcel').on('click', function() {
        var params = {
            dateRange: $('#dateRange').val(),
            startDate: $('#startDate').val(),
            endDate: $('#endDate').val(),
            supplier: $('#supplier').val(),
            paymentStatus: $('#paymentStatus').val()
        };

        window.location.href = 'php_action/exportPurchaseReport.php?' + $.param(params);
    });
});

// Load suppliers into dropdown
function loadSuppliers() {
    $.ajax({
        url: 'php_action/fetchSuppliers.php',
        type: 'post',
        dataType: 'json',
        success: function(response) {
            if(response.success == true) {
                var html = '<option value="">All Suppliers</option>';
                response.data.forEach(function(supplier) {
                    html += '<option value="'+supplier.id+'">'+supplier.company_name+'</option>';
                });
                $("#supplier").html(html);
                $("#supplier").selectpicker('refresh');
            }
        }
    });
}

// Update summary cards
function updateSummary() {
    $.ajax({
        url: 'php_action/fetchPurchaseSummary.php',
        type: 'POST',
        data: {
            dateRange: $('#dateRange').val(),
            startDate: $('#startDate').val(),
            endDate: $('#endDate').val(),
            supplier: $('#supplier').val(),
            paymentStatus: $('#paymentStatus').val()
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#totalPurchases').text(response.total_purchases);
                $('#totalAmount').text(parseFloat(response.total_amount).toFixed(2));
                $('#paidAmount').text(parseFloat(response.paid_amount).toFixed(2));
                $('#dueAmount').text(parseFloat(response.due_amount).toFixed(2));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error updating summary:', error);
        }
    });
}

// View purchase details
function viewPurchaseDetails(purchaseNumber) {
    $.ajax({
        url: 'php_action/fetchPurchaseDetails.php',
        type: 'post',
        data: {purchaseNumber: purchaseNumber},
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Populate purchase details
                $('#view_purchase_number').text(response.data.purchase_number);
                $('#view_supplier').text(response.data.supplier_name);
                $('#view_purchase_date').text(response.data.purchase_date);
                $('#view_sub_total').text(response.data.sub_total);
                $('#view_vat_amount').text(response.data.vat_amount);
                $('#view_grand_total').text(response.data.grand_total);

                // Populate items table
                var itemsHtml = '';
                response.data.items.forEach(function(item) {
                    itemsHtml += '<tr>' +
                        '<td>' + item.material_name + '</td>' +
                        '<td>' + item.quantity + '</td>' +
                        '<td>' + item.rate + '</td>' +
                        '<td>' + item.total + '</td>' +
                    '</tr>';
                });
                $('#purchaseItemsTable').html(itemsHtml);

                // Fetch and populate payment history
                $.ajax({
                    url: 'php_action/fetchPurchasePayments.php',
                    type: 'post',
                    data: {purchaseNumber: purchaseNumber},
                    dataType: 'json',
                    success: function(paymentResponse) {
                        var paymentsHtml = '';
                        if(paymentResponse.success && paymentResponse.payments.length > 0) {
                            paymentResponse.payments.forEach(function(payment) {
                                paymentsHtml += '<tr>' +
                                    '<td>' + payment.payment_date + '</td>' +
                                    '<td>' + payment.amount + '</td>' +
                                    '<td>' + payment.payment_method + '</td>' +
                                    '<td>' + payment.reference_number + '</td>' +
                                    '<td>' + payment.notes + '</td>' +
                                '</tr>';
                            });
                        } else {
                            paymentsHtml = '<tr><td colspan="5" class="text-center">No payment records found</td></tr>';
                        }
                        $('#paymentHistoryTable').html(paymentsHtml);
                    }
                });

                // Show modal
                $('#viewPurchaseModal').modal('show');
            }
        }
    });
} 