// Print Management Module
window.POSPrint = {
    // Print settings
    settings: {
        companyName: 'Lebawi Net Trading PLC',
        address: '',
        phone: '',
        email: '',
        taxNumber: '',
        footer: 'Thank you for your business!'
    },

    // Initialize print settings
    init: function(settings = {}) {
        this.settings = { ...this.settings, ...settings };
        // Make printReceipt globally accessible
        window.printReceipt = this.printReceipt.bind(this);
    },

    // Generate invoice HTML
    generateInvoiceHTML: function(orderData) {
        const date = new Date().toLocaleDateString();
        const time = new Date().toLocaleTimeString();

        return `
            <div class="invoice-print">
                <div class="header">
                    <h2>${this.settings.companyName}</h2>
                    <p>${this.settings.address}</p>
                    <p>Tel: ${this.settings.phone}</p>
                    <p>Email: ${this.settings.email}</p>
                    <p>Tax No: ${this.settings.taxNumber}</p>
                </div>

                <div class="invoice-info">
                    <p><strong>Invoice #:</strong> ${orderData.order_number}</p>
                    <p><strong>Date:</strong> ${date}</p>
                    <p><strong>Time:</strong> ${time}</p>
                    <p><strong>Client:</strong> ${orderData.client_name}</p>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${this.generateItemsHTML(orderData.items)}
                    </tbody>
                </table>

                <div class="totals">
                    ${this.generateTotalsHTML(orderData)}
                </div>

                <div class="footer">
                    <p>${this.settings.footer}</p>
                </div>

                <div class="payment-info">
                    <div class="row">
                        <div>Payment Method:</div>
                        <div>${orderData.payment_method ? orderData.payment_method.charAt(0).toUpperCase() + orderData.payment_method.slice(1).toLowerCase() : 'N/A'}</div>
                    </div>
                    <div class="row">
                        <div>Amount Paid:</div>
                        <div>br ${parseFloat(orderData.paid_amount || 0).toFixed(2)}</div>
                    </div>
                </div>
            </div>
        `;
    },

    // Generate items HTML
    generateItemsHTML: function(items) {
        return items.map(item => `
            <tr>
                <td>${item.name}</td>
                <td>${item.quantity}</td>
                <td>br ${item.price.toFixed(2)}</td>
                <td>br ${(item.price * item.quantity).toFixed(2)}</td>
            </tr>
        `).join('');
    },

    // Generate totals HTML
    generateTotalsHTML: function(orderData) {
        return `
            <div class="totals-row">
                <span>Subtotal:</span>
                <span>br ${orderData.subtotal.toFixed(2)}</span>
            </div>
            ${orderData.discount_amount > 0 ? `
                <div class="totals-row">
                    <span>Discount:</span>
                    <span>br ${orderData.discount_amount.toFixed(2)}</span>
                </div>
            ` : ''}
            <div class="totals-row">
                <span>VAT (${POSCart.VAT_RATE}%):</span>
                <span>br ${orderData.tax_amount.toFixed(2)}</span>
            </div>
            ${orderData.withholding_amount > 0 ? `
                <div class="totals-row">
                    <span>Withholding (${POSCart.WITHHOLDING_RATE}%):</span>
                    <span>br ${orderData.withholding_amount.toFixed(2)}</span>
                </div>
            ` : ''}
            <div class="totals-row total">
                <span>Total:</span>
                <span>br ${orderData.total_amount.toFixed(2)}</span>
            </div>
        `;
    },

    // Print invoice
    printInvoice: function(orderData) {
        // Create print window
        const printWindow = window.open('', '_blank');
        
        // Add print styles
        printWindow.document.write(`
            <html>
            <head>
                <title>Invoice #${orderData.order_number}</title>
                <style>
                    @media print {
                        @page {
                            margin: 10mm;
                        }
                    }
                    body {
                        font-family: Arial, sans-serif;
                        font-size: 12px;
                        line-height: 1.4;
                        color: #000;
                    }
                    .invoice-print {
                        max-width: 80mm;
                        margin: 0 auto;
                        padding: 10px;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    .header h2 {
                        margin: 0 0 10px;
                        font-size: 16px;
                    }
                    .header p {
                        margin: 0;
                        font-size: 12px;
                    }
                    .invoice-info {
                        margin-bottom: 20px;
                    }
                    .invoice-info p {
                        margin: 5px 0;
                    }
                    .items-table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-bottom: 20px;
                    }
                    .items-table th,
                    .items-table td {
                        padding: 5px;
                        text-align: left;
                        border-bottom: 1px solid #ddd;
                    }
                    .totals {
                        margin-bottom: 20px;
                    }
                    .totals-row {
                        display: flex;
                        justify-content: space-between;
                        margin: 5px 0;
                    }
                    .total {
                        font-weight: bold;
                        font-size: 14px;
                        border-top: 1px solid #000;
                        padding-top: 5px;
                    }
                    .footer {
                        text-align: center;
                        margin-top: 20px;
                        padding-top: 10px;
                        border-top: 1px solid #ddd;
                    }
                </style>
            </head>
            <body>
        `);

        // Add invoice content
        printWindow.document.write(this.generateInvoiceHTML(orderData));
        
        // Close HTML
        printWindow.document.write('</body></html>');
        
        // Print
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
    },

    // Generate and download PDF
    downloadPDF: function(orderData) {
        // Implementation for PDF generation
        // This would typically use a library like jsPDF or make a server request
        console.log('PDF download not implemented');
    },

    // Email invoice
    emailInvoice: function(orderData, emailAddress) {
        // Implementation for emailing invoice
        // This would typically make a server request
        console.log('Email invoice not implemented');
    },

    // Print receipt
    printReceipt: async function(saleId) {
        try {
            console.log('Starting printReceipt with saleId:', saleId);
            
            // Fetch sale details with correct path
            const response = await fetch(`../production/php_action/fetchPosDetails.php?id=${saleId}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const result = await response.json();
            console.log('API Response:', result);
            
            if (!result.success || !result.data) {
                throw new Error(result.message || 'Failed to fetch sale details');
            }

            const data = result.data;
            console.log('Sale data:', data);
            console.log('Items array:', data.items);

            // Validate items array
            if (!data.items || !Array.isArray(data.items)) {
                console.error('Invalid items data:', data.items);
                throw new Error('Invalid items data received');
            }

            // Create print window
            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                throw new Error('Unable to open print window. Please allow popups for this site.');
            }

            // Format items section
            let itemsHtml = '<div class="text-center">No items found</div>';
            if (data.items.length > 0) {
                itemsHtml = data.items.map(item => {
                    console.log('Processing item:', item);
                    const quantity = parseFloat(item.quantity || 0);
                    const price = parseFloat(item.price || 0);
                    const total = quantity * price;
                    
                    return `
                        <div class="row">
                            <div style="flex: 2;">${item.name || 'Unknown Item'}</div>
                            <div style="flex: 1; text-align: center;">${quantity}</div>
                            <div style="flex: 1; text-align: right;">${price.toFixed(2)}</div>
                            <div style="flex: 1; text-align: right;">${total.toFixed(2)}</div>
                        </div>
                    `;
                }).join('');
            }

            // Create receipt content
            const content = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Receipt</title>
                    <style>
                        @page {
                            margin: 0;
                            size: 80mm auto;
                        }
                        body {
                            font-family: monospace;
                            margin: 0;
                            padding: 10px;
                            font-size: 12px;
                            width: 80mm;
                            background: white;
                        }
                        .text-center { text-align: center; }
                        .mb-10 { margin-bottom: 10px; }
                        .mt-10 { margin-top: 10px; }
                        .border-top { border-top: 1px dashed #000; padding-top: 10px; }
                        .border-bottom { border-bottom: 1px dashed #000; padding-bottom: 10px; }
                        .row { 
                            display: flex; 
                            justify-content: space-between; 
                            margin: 5px 0;
                            width: 100%;
                        }
                        .bold { font-weight: bold; }
                        .items-section {
                            margin: 10px 0;
                            padding: 10px 0;
                            border-top: 1px dashed #000;
                            border-bottom: 1px dashed #000;
                        }
                        .items-header {
                            display: flex;
                            justify-content: space-between;
                            border-bottom: 1px dashed #000;
                            padding-bottom: 5px;
                            margin-bottom: 5px;
                            font-weight: bold;
                        }
                    </style>
                </head>
                <body>
                    <div class="text-center mb-10">
                        <h2 style="margin: 0; font-size: 16px;">${this.settings.companyName}</h2>
                        <p style="margin: 5px 0;">Sales Receipt</p>
                        <p style="margin: 5px 0;">ID: ${String(data.id).padStart(9, '0')}</p>
                        <p style="margin: 5px 0;">Order #: ${data.order_number || 'N/A'}</p>
                        <p style="margin: 5px 0;">Date: ${new Date(data.order_date).toLocaleString()}</p>
                        <p style="margin: 5px 0;">Client: ${data.client_name || 'N/A'}</p>
                    </div>

                    <div class="items-section">
                        <div class="items-header">
                            <div style="flex: 2;">Item</div>
                            <div style="flex: 1; text-align: center;">Qty</div>
                            <div style="flex: 1; text-align: right;">Price</div>
                            <div style="flex: 1; text-align: right;">Total</div>
                        </div>
                        ${itemsHtml}
                    </div>

                    <div class="mt-10">
                        <div class="row">
                            <div>Subtotal:</div>
                            <div>br ${parseFloat(data.subtotal || 0).toFixed(2)}</div>
                        </div>
                        <div class="row">
                            <div>VAT (15%):</div>
                            <div>br ${parseFloat(data.tax_amount || 0).toFixed(2)}</div>
                        </div>
                        ${parseFloat(data.discount_amount || 0) > 0 ? `
                            <div class="row">
                                <div>Discount:</div>
                                <div>br ${parseFloat(data.discount_amount).toFixed(2)}</div>
                            </div>
                        ` : ''}
                        ${parseFloat(data.withholding_amount || 0) > 0 ? `
                            <div class="row">
                                <div>Withholding (2%):</div>
                                <div>br ${parseFloat(data.withholding_amount).toFixed(2)}</div>
                            </div>
                        ` : ''}
                        <div class="row bold border-top">
                            <div>Total:</div>
                            <div>br ${parseFloat(data.total_amount || 0).toFixed(2)}</div>
                        </div>
                        <div class="row">
                            <div>Payment Method:</div>
                            <div>${data.payment_method ? data.payment_method.charAt(0).toUpperCase() + data.payment_method.slice(1).toLowerCase() : 'N/A'}</div>
                        </div>
                        <div class="row">
                            <div>Amount Paid:</div>
                            <div>br ${parseFloat(data.paid_amount || 0).toFixed(2)}</div>
                        </div>
                    </div>

                    <div class="text-center border-top mt-10">
                        <p>${this.settings.footer}</p>
                    </div>
                </body>
                </html>
            `;

            // Write content to print window
            printWindow.document.write(content);
            printWindow.document.close();

            // Print after a short delay to ensure content is loaded
            setTimeout(() => {
                printWindow.print();
                // Close the window after printing
                setTimeout(() => {
                    printWindow.close();
                }, 500);
            }, 250);

        } catch (error) {
            console.error('Print error:', error);
            alert('Error printing receipt: ' + error.message);
        }
    }
};

// Initialize POSPrint when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.POSPrint.init({
        companyName: 'Lebawi Net Trading PLC',
        address: '',
        phone: '',
        email: '',
        taxNumber: '',
        footer: 'Thank you for your business!'
    });
}); 