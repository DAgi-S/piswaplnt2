<?php
require_once 'core.php';
require_once 'db_connect.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Invalid order ID');
}

$orderId = intval($_GET['id']);

// Helper function to safely handle null values
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Fetch company settings
$settingsQuery = "SELECT setting_key, setting_value FROM company_settings";
$settingsResult = $connect->query($settingsQuery);
$settings = array();
while($row = $settingsResult->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Fetch order details
$sql = "SELECT so.*, 
        c.company_name, c.phone, c.email, c.address,
        COALESCE(a.account_owner, 'System') as created_by_name
        FROM sales_orders so
        LEFT JOIN clients c ON so.client_id = c.id
        LEFT JOIN accounts a ON so.created_by = a.id
        WHERE so.order_number = ?";

$stmt = $connect->prepare($sql);
$stmt->bind_param('s', $_GET['id']);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die('Order not found');
}

// Fetch order items with proper product information
$itemsSql = "SELECT 
    soi.*,
    pp.name as product_name,
    pp.product_code,
    COALESCE(pp.unit, 'units') as unit
FROM sales_order_items soi
LEFT JOIN production_products pp ON soi.product_id = pp.id
WHERE soi.sales_order_id = ?
ORDER BY soi.id ASC";

$itemsStmt = $connect->prepare($itemsSql);
$itemsStmt->bind_param('i', $order['id']);
$itemsStmt->execute();
$items = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch payment history
$paymentsSql = "SELECT 
    sp.*,
    COALESCE(a.account_owner, 'System') as processed_by
FROM sales_payments sp
LEFT JOIN accounts a ON sp.created_by = a.id
WHERE sp.sales_order_id = ?
ORDER BY sp.payment_date ASC";

$paymentsStmt = $connect->prepare($paymentsSql);
$paymentsStmt->bind_param('i', $order['id']);
$paymentsStmt->execute();
$payments = $paymentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// HTML Template
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Order #<?php echo e($order['order_number']); ?></title>
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
        .company-logo {
            max-width: 150px;
            max-height: 80px;
            margin-bottom: 10px;
            object-fit: contain;
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
        .info-box p {
            margin: 0;
            line-height: 1.4;
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
            font-size: 11px;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            float: right;
            width: 250px;
            margin-top: 10px;
        }
        .totals table {
            margin-top: 10px;
            background: #f9f9f9;
        }
        .totals table td {
            padding: 5px 8px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 10px;
            color: #666;
            clear: both;
        }
        .status-label {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 2px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        .status-unpaid { background: #f8d7da; color: #721c24; }
        .status-partial { background: #fff3cd; color: #856404; }
        .status-paid { background: #d4edda; color: #155724; }
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .info-box {
                break-inside: avoid;
            }
            table {
                break-inside: auto;
            }
            tr {
                break-inside: avoid;
                break-after: auto;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <?php if(!empty($settings['company_logo'])): ?>
            <img src="../<?php echo e($settings['company_logo']); ?>" alt="Company Logo" class="company-logo">
        <?php endif; ?>
        <div class="company-name"><?php echo e($settings['company_name']); ?></div>
        <div class="company-details">
            <?php if(!empty($settings['company_tin'])): ?>
                TIN: <?php echo e($settings['company_tin']); ?><br>
            <?php endif; ?>
            <?php if(!empty($settings['company_phone'])): ?>
                Tel: <?php echo e($settings['company_phone']); ?><br>
            <?php endif; ?>
            <?php if(!empty($settings['company_email'])): ?>
                Email: <?php echo e($settings['company_email']); ?><br>
            <?php endif; ?>
            <?php if(!empty($settings['company_website'])): ?>
                Website: <?php echo e($settings['company_website']); ?><br>
            <?php endif; ?>
            <?php if(!empty($settings['company_address'])): ?>
                Address: <?php echo e($settings['company_address']); ?>
            <?php endif; ?>
        </div>
        <div class="document-title">SALES ORDER</div>
    </div>

    <div class="info-section">
        <div class="info-grid">
            <div class="info-box">
                <h3>Order Information</h3>
                <p>
                    <strong>Order Number:</strong> <?php echo e($order['order_number']); ?><br>
                    <strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['order_date'])); ?><br>
                    <strong>Status:</strong> 
                    <span class="status-label status-<?php echo strtolower($order['order_status']); ?>">
                        <?php echo e($order['order_status']); ?>
                    </span><br>
                    <strong>Payment Status:</strong> 
                    <span class="status-label status-<?php echo strtolower($order['payment_status']); ?>">
                        <?php echo e($order['payment_status']); ?>
                    </span>
                </p>
            </div>
            <div class="info-box">
                <h3>Client Information</h3>
                <p>
                    <strong>Company:</strong> <?php echo e($order['company_name']); ?><br>
                    <strong>Phone:</strong> <?php echo e($order['phone']); ?><br>
                    <strong>Email:</strong> <?php echo e($order['email']); ?><br>
                    <strong>Address:</strong> <?php echo e($order['address']); ?>
                </p>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Code</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Tax Rate</th>
                <th class="text-right">Tax Amount</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo e($item['product_name']); ?></td>
                <td><?php echo e($item['product_code']); ?></td>
                <td class="text-right"><?php echo number_format($item['quantity'], 2) . ' ' . e($item['unit']); ?></td>
                <td class="text-right"><?php echo number_format($item['unit_price'], 2); ?></td>
                <td class="text-right"><?php echo number_format($item['tax_rate'], 2); ?>%</td>
                <td class="text-right"><?php echo floatval($item['tax_amount']); ?></td>
                <td class="text-right"><?php echo floatval($item['discount_amount']); ?></td>
                <td class="text-right"><?php echo number_format($item['total'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td><strong>Subtotal:</strong></td>
                <td class="text-right"><?php echo floatval($order['subtotal']); ?></td>
            </tr>
            <tr>
                <td><strong>VAT (15%):</strong></td>
                <td class="text-right"><?php echo floatval($order['tax_amount']); ?></td>
            </tr>
            <tr>
                <td><strong>Total:</strong></td>
                <td class="text-right"><?php echo floatval($order['subtotal'] + $order['tax_amount']); ?></td>
            </tr>
            <?php if ($order['withholding_amount'] > 0): ?>
            <tr>
                <td><strong>Withholding (2%):</strong></td>
                <td class="text-right"><?php echo floatval($order['withholding_amount']); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td><strong>Grand Total:</strong></td>
                <td class="text-right"><?php echo floatval($order['subtotal'] + $order['tax_amount'] - ($order['withholding_amount'] ?? 0)); ?></td>
            </tr>
            <?php if ($order['discount_amount'] > 0): ?>
            <tr>
                <td><strong>Discount Amount:</strong></td>
                <td class="text-right"><?php echo floatval($order['discount_amount']); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td><strong>Payable Amount:</strong></td>
                <td class="text-right"><strong><?php echo floatval($order['total_amount']); ?></strong></td>
            </tr>
            <tr>
                <td><strong>Paid Amount:</strong></td>
                <td class="text-right"><?php echo floatval($order['paid_amount']); ?></td>
            </tr>
            <tr>
                <td><strong>Balance:</strong></td>
                <td class="text-right"><?php echo floatval($order['balance']); ?></td>
            </tr>
        </table>
    </div>

    <?php if (!empty($payments)): ?>
    <div style="clear: both; margin-top: 20px;">
        <h3 style="font-size: 14px; margin-bottom: 10px;">Payment History</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Processed By</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?php echo date('F j, Y', strtotime($payment['payment_date'])); ?></td>
                    <td class="text-right"><?php echo floatval($payment['amount']); ?></td>
                    <td><?php echo e($payment['payment_method']); ?></td>
                    <td><?php echo e($payment['reference_number']); ?></td>
                    <td><?php echo e($payment['processed_by']); ?></td>
                    <td><?php echo e($payment['notes']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>
            Created by: <?php echo e($order['created_by_name']); ?><br>
            Date: <?php echo date('F j, Y H:i:s', strtotime($order['created_at'])); ?><br>
            <?php if (!empty($order['notes'])): ?>
            Notes: <?php echo e($order['notes']); ?>
            <?php endif; ?>
        </p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html> 