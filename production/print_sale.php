<?php
require_once 'php_action/core.php';

// Check if id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Error: No sale ID provided.";
    exit;
}

$saleId = $_GET['id'];

// Get sale details
$query = "SELECT 
            so.id,
            so.order_number as sale_number,
            so.order_date as date,
            c.company_name as client_name,
            c.tin_number as client_tin,
            c.phone as client_phone,
            c.email as client_email,
            c.address as client_address,
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

if ($result->num_rows == 0) {
    echo "Error: Sale not found.";
    exit;
}

$sale = $result->fetch_assoc();

// Get products
$query = "SELECT 
            p.name,
            soi.quantity,
            soi.unit_price,
            (soi.quantity * soi.unit_price) as total
          FROM sales_order_items soi
          LEFT JOIN production_products p ON soi.product_id = p.id
          WHERE soi.sales_order_id = ?";
          
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $saleId);
$stmt->execute();
$productsResult = $stmt->get_result();

$products = array();
while ($row = $productsResult->fetch_assoc()) {
    $products[] = $row;
}

// Get payments
$query = "SELECT 
            payment_date as date,
            amount,
            payment_method as method,
            reference_number as reference,
            notes
          FROM sales_payments
          WHERE sales_order_id = ?
          ORDER BY payment_date DESC";
          
$stmt = $connect->prepare($query);
$stmt->bind_param("i", $saleId);
$stmt->execute();
$paymentsResult = $stmt->get_result();

$payments = array();
while ($row = $paymentsResult->fetch_assoc()) {
    $payments[] = $row;
}

// Format currency
function formatCurrency($amount) {
    return number_format((float)$amount, 2, '.', ',');
}

// Define default company information
$company = array(
    'name' => 'Production Management System',
    'address' => 'Addis Ababa, Ethiopia',
    'phone' => '+251 11 123 4567',
    'email' => 'info@company.com',
    'tin' => 'TIN-12345678'
);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sale Invoice #<?php echo $sale['sale_number']; ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            font-size: 12px;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
        }
        .invoice-header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .company-details {
            float: left;
            width: 60%;
        }
        .invoice-details {
            float: right;
            width: 35%;
            text-align: right;
        }
        .client-details {
            margin-bottom: 20px;
        }
        .invoice-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .invoice-items th, .invoice-items td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        .invoice-items th {
            background-color: #f8f8f8;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            float: right;
            width: 35%;
        }
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals table th, .totals table td {
            padding: 5px;
            text-align: right;
        }
        .payment-history {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .payment-history table {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-history th, .payment-history td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        .payment-history th {
            background-color: #f8f8f8;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
            text-align: center;
            color: #999;
        }
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }
        @media print {
            body {
                padding: 0;
            }
            .invoice-container {
                max-width: 100%;
                border: none;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header clearfix">
            <div class="company-details">
                <h2><?php echo $company['name']; ?></h2>
                <p>
                    <?php echo $company['address']; ?><br>
                    Phone: <?php echo $company['phone']; ?><br>
                    Email: <?php echo $company['email']; ?><br>
                    TIN: <?php echo $company['tin']; ?>
                </p>
            </div>
            <div class="invoice-details">
                <h2>INVOICE</h2>
                <p>
                    Invoice #: <?php echo $sale['sale_number']; ?><br>
                    Date: <?php echo date('d/m/Y', strtotime($sale['date'])); ?><br>
                    Payment Status: <strong><?php echo ucfirst($sale['payment_status']); ?></strong>
                </p>
            </div>
        </div>
        
        <div class="client-details">
            <h3>Bill To:</h3>
            <p>
                <strong><?php echo $sale['client_name']; ?></strong><br>
                <?php if (!empty($sale['client_address'])): ?>
                Address: <?php echo $sale['client_address']; ?><br>
                <?php endif; ?>
                <?php if (!empty($sale['client_phone'])): ?>
                Phone: <?php echo $sale['client_phone']; ?><br>
                <?php endif; ?>
                <?php if (!empty($sale['client_email'])): ?>
                Email: <?php echo $sale['client_email']; ?><br>
                <?php endif; ?>
                <?php if (!empty($sale['client_tin'])): ?>
                TIN: <?php echo $sale['client_tin']; ?>
                <?php endif; ?>
            </p>
        </div>
        
        <table class="invoice-items">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="45%">Product</th>
                    <th width="15%">Quantity</th>
                    <th width="15%" class="text-right">Unit Price</th>
                    <th width="20%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $index => $product): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo $product['name']; ?></td>
                    <td><?php echo $product['quantity']; ?></td>
                    <td class="text-right"><?php echo formatCurrency($product['unit_price']); ?></td>
                    <td class="text-right"><?php echo formatCurrency($product['total']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="clearfix">
            <div class="totals">
                <table>
                    <tr>
                        <th>Subtotal:</th>
                        <td><?php echo formatCurrency($sale['subtotal']); ?></td>
                    </tr>
                    <tr>
                        <th>VAT (15%):</th>
                        <td><?php echo formatCurrency($sale['vat']); ?></td>
                    </tr>
                    <tr>
                        <th>WHT (2%):</th>
                        <td><?php echo formatCurrency($sale['withholding']); ?></td>
                    </tr>
                    <tr>
                        <th>Discount:</th>
                        <td><?php echo formatCurrency($sale['discount']); ?></td>
                    </tr>
                    <tr>
                        <th><strong>Grand Total:</strong></th>
                        <td><strong><?php echo formatCurrency($sale['grand_total']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Paid Amount:</th>
                        <td><?php echo formatCurrency($sale['paid_amount']); ?></td>
                    </tr>
                    <tr>
                        <th>Balance Due:</th>
                        <td><?php echo formatCurrency($sale['balance']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if (count($payments) > 0): ?>
        <div class="payment-history">
            <h3>Payment History</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($payment['date'])); ?></td>
                        <td><?php echo formatCurrency($payment['amount']); ?></td>
                        <td><?php echo $payment['method']; ?></td>
                        <td><?php echo !empty($payment['reference']) ? $payment['reference'] : '-'; ?></td>
                        <td><?php echo !empty($payment['notes']) ? $payment['notes'] : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This invoice was created by: <?php echo $sale['created_by']; ?></p>
            <p>Date Printed: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>
        
        <div class="no-print" style="margin-top: 20px; text-align: center;">
            <button onclick="window.print();" style="padding: 10px 20px; background: #1976D2; color: white; border: none; border-radius: 4px; cursor: pointer;">
                Print Invoice
            </button>
            <button onclick="window.close();" style="padding: 10px 20px; background: #999; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">
                Close
            </button>
        </div>
    </div>
    
    <script>
        window.onload = function() {
            // Auto print on page load
            //window.print();
        };
    </script>
</body>
</html> 