$(document).ready(function() {
    // Initialize select picker
    $('.selectpicker').selectpicker();

    // Initialize date inputs with proper constraints
    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];
    
    $('#startDate, #endDate').attr('max', todayStr);
    $('#startDate').on('change', function() {
        $('#endDate').attr('min', $(this).val());
    });
    $('#endDate').on('change', function() {
        $('#startDate').attr('max', $(this).val());
    });

    // Set default date range
    const defaultRange = 'thisMonth';
    $('#dateRange').val(defaultRange);
    setDateRangeValues(defaultRange);

    // Load clients
    loadClients();

    // Handle date range selection
    $('#dateRange').on('change', function() {
        const selectedRange = $(this).val();
        if(selectedRange === 'custom') {
            $('#customDateRange').show();
        } else {
            $('#customDateRange').hide();
            setDateRangeValues(selectedRange);
            $('#filterForm').submit();
        }
    });

    // Initialize DataTable with improved configuration
    var reportTable = $("#salesReportTable").DataTable({
        'ajax': {
            url: 'php_action/fetchSalesReports.php',
            type: 'POST',
            data: function(data) {
                // Add filter parameters
                const filterData = {
                    dateRange: $('#dateRange').val(),
                    startDate: $('#startDate').val(),
                    endDate: $('#endDate').val(),
                    client: $('#client').val(),
                    paymentStatus: $('#paymentStatus').val()
                };
                return { ...data, ...filterData };
            },
            dataSrc: 'data'
        },
        'order': [[1, 'desc']], // Sort by date descending
        'dom': 'Bfrtip',
        'buttons': [
            'copy', 
            {
                extend: 'csv',
                text: 'CSV',
                filename: function() {
                    return 'Sales_Report_' + new Date().toISOString().split('T')[0];
                }
            },
            {
                extend: 'excel',
                text: 'Excel',
                filename: function() {
                    return 'Sales_Report_' + new Date().toISOString().split('T')[0];
                }
            },
            {
                extend: 'pdf',
                text: 'PDF',
                filename: function() {
                    return 'Sales_Report_' + new Date().toISOString().split('T')[0];
                }
            },
            'print'
        ],
        'columnDefs': [
            {
                'targets': [3,4,5,6,7], // Amount columns
                'render': function(data, type, row) {
                    if (type === 'display') {
                        return formatAmount(data);
                    }
                    return parseFloat(data); // Return numeric value for sorting/filtering
                }
            },
            {
                'targets': [8], // Payment Status column
                'render': function(data, type, row) {
                    if (type === 'display') {
                        return getStatusBadge(data, 'payment');
                    }
                    return data;
                }
            },
            {
                'targets': [9], // Options column
                'orderable': false,
                'searchable': false
            }
        ],
        'footerCallback': function(row, data, start, end, display) {
            var api = this.api();

            // Calculate totals for amount columns (indices 3,4,5,6,7)
            [3,4,5,6,7].forEach(function(colIndex) {
                var total = api
                    .column(colIndex, { page: 'current' })
                    .data()
                    .reduce(function(acc, val) {
                        return acc + parseFloat(val);
                    }, 0);

                $(api.column(colIndex).footer()).html(formatAmount(total));
            });
        },
        'processing': true,
        'serverSide': false,
        'responsive': true,
        'pageLength': 25,
        'language': {
            'processing': 'Loading...',
            'emptyTable': 'No sales records found',
            'zeroRecords': 'No matching records found'
        }
    });

    // Handle filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        reportTable.ajax.reload();
        updateSummary();
    });

    // Initial summary update
    updateSummary();

    // Handle Excel export with proper date formatting
    $('#exportExcel').on('click', function() {
        const params = {
            dateRange: $('#dateRange').val(),
            startDate: $('#startDate').val(),
            endDate: $('#endDate').val(),
            client: $('#client').val(),
            paymentStatus: $('#paymentStatus').val(),
            format: 'excel'
        };

        // Use proper date format for filename
        const filename = 'Sales_Report_' + new Date().toISOString().split('T')[0];
        window.location.href = 'php_action/exportSalesReport.php?' + $.param({ ...params, filename });
    });

    // Add this CSS to handle loading state
    $('<style>')
        .text(`
            .modal-content.loading {
                position: relative;
            }
            .modal-content.loading:after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255,255,255,0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            }
            .modal-content.loading:before {
                content: 'Loading...';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                z-index: 1001;
                background: #fff;
                padding: 10px 20px;
                border-radius: 4px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
        `)
        .appendTo('head');
});

// Helper function to set date range values
function setDateRangeValues(range) {
    const today = new Date();
    let startDate = new Date();
    let endDate = new Date();

    switch(range) {
        case 'today':
            startDate = today;
            endDate = today;
            break;
        case 'yesterday':
            startDate.setDate(today.getDate() - 1);
            endDate = startDate;
            break;
        case 'last7days':
            startDate.setDate(today.getDate() - 6);
            break;
        case 'last30days':
            startDate.setDate(today.getDate() - 29);
            break;
        case 'thisMonth':
            startDate = new Date(today.getFullYear(), today.getMonth(), 1);
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
        case 'lastMonth':
            startDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            endDate = new Date(today.getFullYear(), today.getMonth(), 0);
            break;
        default:
            return; // Don't update for custom range
    }

    $('#startDate').val(formatDate(startDate));
    $('#endDate').val(formatDate(endDate));
}

// Helper function to format dates
function formatDate(date) {
    if (!date) return 'N/A';
    
    // If date is a string, parse it first
    if (typeof date === 'string') {
        // Try to parse the date string
        const parsedDate = new Date(date);
        if (isNaN(parsedDate.getTime())) {
            return date; // Return original string if parsing fails
        }
        date = parsedDate;
    }
    
    // Format the date
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Helper function to format amounts consistently
function formatAmount(amount) {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '0.00';
    }
    return parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Load clients into dropdown
function loadClients() {
    $.ajax({
        url: 'php_action/fetchClients.php',
        type: 'post',
        dataType: 'json',
        success: function(response) {
            if(response.success === true) {
                var html = '<option value="">All Clients</option>';
                response.data.forEach(function(client) {
                    html += '<option value="'+client.id+'">'+
                        (client.company_name || 'Unknown Company')+
                        (client.contact_person ? ' ('+client.contact_person+')' : '')+
                        '</option>';
                });
                $("#client").html(html);
                $("#client").selectpicker('refresh');
            } else {
                console.error('Error loading clients:', response.messages);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
}

// Update summary cards with proper formatting
function updateSummary() {
    $.ajax({
        url: 'php_action/fetchSalesSummary.php',
        type: 'POST',
        data: {
            dateRange: $('#dateRange').val(),
            startDate: $('#startDate').val(),
            endDate: $('#endDate').val(),
            client: $('#client').val(),
            paymentStatus: $('#paymentStatus').val()
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#totalSales').text(response.total_sales);
                $('#totalRevenue').text(formatAmount(response.total_amount));
                $('#receivedAmount').text(formatAmount(response.received_amount));
                $('#outstandingAmount').text(formatAmount(response.outstanding_amount));

                // Update card colors based on values
                updateCardColors(response);
            } else {
                console.error('Error updating summary:', response.messages);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
}

// Helper function to update card colors based on values
function updateCardColors(data) {
    // Outstanding amount card
    const outstandingRatio = data.outstanding_amount / data.total_amount;
    let outstandingClass = 'panel-success';
    if (outstandingRatio > 0.5) {
        outstandingClass = 'panel-danger';
    } else if (outstandingRatio > 0.2) {
        outstandingClass = 'panel-warning';
    }
    $('#outstandingAmount').closest('.panel')
        .removeClass('panel-success panel-warning panel-danger')
        .addClass(outstandingClass);
}

// Function to view sale details
function viewSaleDetails(orderNumber) {
    const modalContent = $('#saleDetailsModal .modal-content');
    modalContent.addClass('loading');

    // Clear previous content
    $('#invoiceNumber').text('Loading...');
    $('#saleDate').text('Loading...');
    $('#orderStatus').text('Loading...');
    $('#paymentStatus').html('<span class="label label-default">Loading...</span>');
    
    // Clear client information
    $('#clientName').text('Loading...');
    $('#clientTin').text('Loading...');
    $('#clientPhone').text('Loading...');
    $('#clientEmail').text('Loading...');
    $('#clientAddress').text('Loading...');
    
    // Clear financial information
    $('#subTotal').text('0.00');
    $('#vatAmount').text('0.00');
    $('#withholdingAmount').text('0.00');
    $('#discountAmount').text('0.00');
    $('#grandTotal').text('0.00');
    $('#paidAmount').text('0.00');
    
    // Clear tables
    $('#itemsTableBody').empty();
    $('#paymentsTableBody').empty();

    // Update modal footer with Print Invoice button
    const modalFooter = $('#saleDetailsModal .modal-footer');
    modalFooter.html(`
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="printInvoice('${orderNumber}')">
            <i class="fa fa-print"></i> Print Invoice
        </button>
    `);

    // Show the modal
    $('#saleDetailsModal').modal('show');

    // Fetch sale details
    $.ajax({
        url: 'php_action/fetchSalesOnReport.php',
        type: 'POST',
        data: { order_number: orderNumber },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const data = response.data;
                
                // Update order information
                $('#invoiceNumber').text(data.invoice_number || 'N/A');
                $('#saleDate').text(formatDate(data.sale_date) || 'N/A');
                $('#orderStatus').html(getStatusBadge(data.order_status || 'N/A', 'order'));
                
                // Update payment status with proper badge
                const paymentStatus = data.calculated_payment_status || 'N/A';
                $('#paymentStatus').html(getStatusBadge(paymentStatus, 'payment'));
                
                // Update client information
                $('#clientName').text(data.client || 'N/A');
                $('#clientTin').text(data.client_tin || 'N/A');
                $('#clientPhone').text(data.client_phone || 'N/A');
                $('#clientEmail').text(data.client_email || 'N/A');
                $('#clientAddress').text(data.client_address || 'N/A');
                
                // Update financial information
                $('#subTotal').text(formatAmount(data.sub_total));
                $('#vatAmount').text(formatAmount(data.vat_amount));
                $('#withholdingAmount').text(formatAmount(data.withholding_amount));
                $('#discountAmount').text(formatAmount(data.discount_amount));
                $('#grandTotal').text(formatAmount(data.grand_total));
                $('#paidAmount').text(formatAmount(data.paid_amount));
                
                // Update items table
                if (data.items && data.items.length > 0) {
                    const itemsHtml = data.items.map(item => `
                        <tr>
                            <td>${item.product_name}</td>
                            <td class="text-right">${formatAmount(item.quantity)}</td>
                            <td class="text-right">${formatAmount(item.rate)}</td>
                            <td class="text-right">${formatAmount(item.tax_rate)}%</td>
                            <td class="text-right">${formatAmount(item.tax_amount)}</td>
                            <td class="text-right">${formatAmount(item.withholding_amount)}</td>
                            <td class="text-right">${formatAmount(item.discount_amount)}</td>
                            <td class="text-right">${formatAmount(item.subtotal)}</td>
                            <td class="text-right">${formatAmount(item.total)}</td>
                        </tr>
                    `).join('');
                    $('#itemsTableBody').html(itemsHtml);
                } else {
                    $('#itemsTableBody').html('<tr><td colspan="9" class="text-center">No items found</td></tr>');
                }
                
                // Update payments table
                if (data.payments && data.payments.length > 0) {
                    const paymentsHtml = data.payments.map(payment => `
                        <tr>
                            <td>${formatDate(payment.payment_date)}</td>
                            <td class="text-right">${formatAmount(payment.amount)}</td>
                            <td>${payment.payment_method || ''}</td>
                            <td>${payment.reference || ''}</td>
                            <td>${payment.notes || ''}</td>
                        </tr>
                    `).join('');
                    $('#paymentsTableBody').html(paymentsHtml);
                } else {
                    $('#paymentsTableBody').html('<tr><td colspan="5" class="text-center">No payments found</td></tr>');
                }
            } else {
                alert('Error fetching sale details: ' + response.messages);
            }
        },
        error: function(xhr, status, error) {
            alert('Error fetching sale details: ' + error);
        },
        complete: function() {
            modalContent.removeClass('loading');
        }
    });
}

// Helper function to generate status badges
function getStatusBadge(status, type) {
    let className = '';
    let text = status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
    
    if (type === 'payment') {
        switch(status.toLowerCase()) {
            case 'paid':
                className = 'success';
                break;
            case 'partially_paid':
            case 'partial':
                className = 'warning';
                text = 'Partially Paid';
                break;
            case 'unpaid':
                className = 'danger';
                break;
            default:
                className = 'default';
        }
    } else { // order status
        switch(status.toLowerCase()) {
            case 'completed':
                className = 'success';
                break;
            case 'pending':
                className = 'warning';
                break;
            case 'cancelled':
                className = 'danger';
                break;
            default:
                className = 'default';
        }
    }
    
    return `<span class="label label-${className}">${text}</span>`;
}

// Print invoice
function printInvoice(orderNumber) {
    // Open print window first to avoid popup blocking
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Loading...</title></head><body>Loading invoice data...</body></html>');

    $.ajax({
        url: 'php_action/fetchSalesOnReport.php',
        type: 'POST',
        data: { order_number: orderNumber },
        dataType: 'json',
        success: function(response) {
            if(response.success && response.data) {
                const data = response.data;
                
                // Create the invoice HTML
                var invoiceHtml = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="utf-8">
                        <title>Sales Invoice #${data.invoice_number}</title>
                        <style>
                            @page {
                                margin: 10mm;
                                size: A4;
                            }
                            body {
                                font-family: Arial, sans-serif;
                                font-size: 12px;
                                line-height: 1.3;
                                color: #333;
                                margin: 0;
                                padding: 10px;
                                background: #fff;
                            }
                            .header {
                                text-align: center;
                                margin-bottom: 15px;
                                padding-bottom: 10px;
                                border-bottom: 1px solid #eee;
                            }
                            .company-name {
                                font-size: 22px;
                                font-weight: bold;
                                margin-bottom: 5px;
                                color: #2c3e50;
                            }
                            .company-details {
                                font-size: 11px;
                                color: #666;
                                margin-bottom: 10px;
                                line-height: 1.4;
                            }
                            .document-title {
                                font-size: 18px;
                                margin: 10px 0;
                                color: #2c3e50;
                                font-weight: bold;
                                text-transform: uppercase;
                                letter-spacing: 1px;
                            }
                            .info-section {
                                margin-bottom: 15px;
                            }
                            .info-grid {
                                display: grid;
                                grid-template-columns: 1fr 1fr;
                                gap: 15px;
                                margin-bottom: 15px;
                            }
                            .info-box {
                                border: 1px solid #e1e1e1;
                                padding: 10px;
                                border-radius: 3px;
                                background: #f9f9f9;
                            }
                            .info-box h3 {
                                margin: 0 0 8px 0;
                                font-size: 13px;
                                color: #2c3e50;
                                border-bottom: 1px solid #e1e1e1;
                                padding-bottom: 5px;
                            }
                            table {
                                width: 100%;
                                border-collapse: collapse;
                                margin-bottom: 15px;
                                background: #fff;
                                font-size: 11px;
                            }
                            th, td {
                                padding: 6px 8px;
                                border: 1px solid #e1e1e1;
                                text-align: left;
                            }
                            th {
                                background-color: #f5f5f5;
                                font-weight: bold;
                                color: #2c3e50;
                            }
                            .text-right {
                                text-align: right;
                            }
                            .totals {
                                float: right;
                                width: 250px;
                                margin-top: 10px;
                            }
                            .footer {
                                margin-top: 30px;
                                padding-top: 10px;
                                border-top: 1px solid #eee;
                                page-break-inside: avoid;
                            }
                            @media print {
                                body { margin: 0; padding: 10px; }
                                .no-print { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <div class="company-name">PISTOCKLNT</div>
                            <div class="document-title">SALES INVOICE</div>
                        </div>

                        <div class="info-section">
                            <div class="info-grid">
                                <div class="info-box">
                                    <h3>Invoice Information</h3>
                                    <p>
                                        <strong>Invoice Number:</strong> ${data.invoice_number}<br>
                                        <strong>Date:</strong> ${formatDate(data.sale_date)}<br>
                                    </p>
                                </div>
                                <div class="info-box">
                                    <h3>Client Information</h3>
                                    <p>
                                        <strong>Company:</strong> ${data.client || 'N/A'}<br>
                                        <strong>TIN:</strong> ${data.client_tin || 'N/A'}<br>
                                        <strong>Phone:</strong> ${data.client_phone || 'N/A'}<br>
                                        <strong>Email:</strong> ${data.client_email || 'N/A'}<br>
                                        <strong>Address:</strong> ${data.client_address || 'N/A'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-right">Quantity</th>
                                    <th class="text-right">Rate</th>
                                    <th class="text-right">Tax Rate</th>
                                    <th class="text-right">Tax Amount</th>
                                    <th class="text-right">Withholding</th>
                                    <th class="text-right">Discount</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.items.map(item => `
                                    <tr>
                                        <td>${item.product_name}</td>
                                        <td class="text-right">${formatAmount(item.quantity)}</td>
                                        <td class="text-right">${formatAmount(item.rate)}</td>
                                        <td class="text-right">${formatAmount(item.tax_rate)}%</td>
                                        <td class="text-right">${formatAmount(item.tax_amount)}</td>
                                        <td class="text-right">${formatAmount(item.withholding_amount)}</td>
                                        <td class="text-right">${formatAmount(item.discount_amount)}</td>
                                        <td class="text-right">${formatAmount(item.total)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>

                        <div class="totals">
                            <table>
                                <tr>
                                    <td><strong>Subtotal:</strong></td>
                                    <td class="text-right">${formatAmount(data.sub_total)}</td>
                                </tr>
                                <tr>
                                    <td><strong>VAT Amount:</strong></td>
                                    <td class="text-right">${formatAmount(data.vat_amount)}</td>
                                </tr>
                                <tr>
                                    <td><strong>Withholding Amount:</strong></td>
                                    <td class="text-right">${formatAmount(data.withholding_amount)}</td>
                                </tr>
                                <tr>
                                    <td><strong>Discount Amount:</strong></td>
                                    <td class="text-right">${formatAmount(data.discount_amount)}</td>
                                </tr>
                                <tr>
                                    <td><strong>Grand Total:</strong></td>
                                    <td class="text-right"><strong>${formatAmount(data.grand_total)}</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Paid Amount:</strong></td>
                                    <td class="text-right">${formatAmount(data.paid_amount)}</td>
                                </tr>
                                <tr>
                                    <td><strong>Balance:</strong></td>
                                    <td class="text-right">${formatAmount(data.grand_total - data.paid_amount)}</td>
                                </tr>
                            </table>
                        </div>

                        ${data.payments && data.payments.length > 0 ? `
                            <div style="clear: both; margin-top: 20px;">
                                <h3 style="font-size: 14px; margin-bottom: 10px;">Payment History</h3>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th class="text-right">Amount</th>
                                            <th>Method</th>
                                            <th>Reference</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.payments.map(payment => `
                                            <tr>
                                                <td>${formatDate(payment.payment_date)}</td>
                                                <td class="text-right">${formatAmount(payment.amount)}</td>
                                                <td>${payment.payment_method || ''}</td>
                                                <td>${payment.reference || ''}</td>
                                                <td>${payment.notes || ''}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        ` : ''}

                        <div class="footer">
                            <div style="float: left; width: 45%;">
                                <p>
                                    _______________________<br>
                                    Prepared by<br>
                                    Name:<br>
                                    Date: ${formatDate(new Date())}
                                </p>
                            </div>
                            <div style="float: right; width: 45%;">
                                <p>
                                    _______________________<br>
                                    Received by<br>
                                    Name:<br>
                                    Date:
                                </p>
                            </div>
                        </div>

                        <script>
                            window.onload = function() {
                                window.print();
                                // Optional: Close the window after printing
                                // window.onafterprint = function() { window.close(); };
                            }
                        </script>
                    </body>
                    </html>`;

                // Write the HTML to the new window
                printWindow.document.open();
                printWindow.document.write(invoiceHtml);
                printWindow.document.close();
            } else {
                printWindow.document.open();
                printWindow.document.write(`
                    <html>
                        <head><title>Error</title></head>
                        <body>
                            <h3>Error loading invoice data</h3>
                            <p>${response.messages || 'Unknown error occurred'}</p>
                            <button onclick="window.close()">Close</button>
                        </body>
                    </html>
                `);
                printWindow.document.close();
            }
        },
        error: function(xhr, status, error) {
            printWindow.document.open();
            printWindow.document.write(`
                <html>
                    <head><title>Error</title></head>
                    <body>
                        <h3>Error loading invoice</h3>
                        <p>Failed to load invoice data. Please try again.</p>
                        <p>Error: ${error}</p>
                        <button onclick="window.close()">Close</button>
                    </body>
                </html>
            `);
            printWindow.document.close();
        }
    });
} 