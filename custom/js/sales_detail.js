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
                // Fill sale information
                $('#view_sale_number').text(response.data.sale_number);
                $('#view_client_name').text(response.data.client_name);
                $('#view_sale_date').text(moment(response.data.date).format('DD/MM/YYYY'));
                $('#view_created_by').text(response.data.created_by);
                
                // Fill payment information
                $('#view_payment_status').html(`<span class="label label-${getStatusClass(response.data.payment_status)}">${response.data.payment_status}</span>`);
                $('#view_total_amount').text(formatCurrency(response.data.grand_total));
                $('#view_paid_amount').text(formatCurrency(response.data.paid_amount));
                $('#view_balance').text(formatCurrency(response.data.balance));
                
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
                
                // Fill payment history
                let paymentRows = '';
                response.data.payments.forEach(function(payment) {
                    paymentRows += `
                        <tr>
                            <td>${moment(payment.date).format('DD/MM/YYYY')}</td>
                            <td class="text-right">${formatCurrency(payment.amount)}</td>
                            <td>${payment.method}</td>
                            <td>${payment.reference || '-'}</td>
                            <td>${payment.notes || '-'}</td>
                        </tr>`;
                });
                $('#paymentHistoryTable tbody').html(paymentRows);
                
                // Show modal
                $('#viewSaleModal').modal('show');
            } else {
                alert('Error fetching sale details');
            }
        },
        error: function() {
            alert('Error fetching sale details');
        }
    });
}

// Print sale
function printSale(saleId) {
    window.open(`print_sale.php?id=${saleId}`, '_blank');
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