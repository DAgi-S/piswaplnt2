<?php
require_once 'core.php';
require_once 'db_connect.php';
require_once 'middleware.php';

if (!isset($_GET['id']) || !hasPermission('view_quotations')) {
    exit('Access denied');
}

$quotationId = (int)$_GET['id'];

// Fetch quotation details
$sql = "SELECT q.*, c.*, u.username as created_by_name
        FROM quotations q
        LEFT JOIN clients c ON q.client_id = c.id
        LEFT JOIN users u ON q.created_by = u.user_id
        WHERE q.id = ?";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $quotationId);
$stmt->execute();
$result = $stmt->get_result();
$quotation = $result->fetch_assoc();

// Fetch quotation items
$sql = "SELECT qi.*, p.name as product_name
        FROM quotation_items qi
        LEFT JOIN products p ON qi.product_id = p.product_id
        WHERE qi.quotation_id = ?";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $quotationId);
$stmt->execute();
$items = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation #<?php echo htmlspecialchars($quotation['quotation_number']); ?></title>
    <style>
        @page {
            margin: 0.5cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2196F3;
        }
        .logo-section {
            text-align: left;
        }
        .logo {
            max-width: 120px;
            height: auto;
        }
        .company-info {
            text-align: right;
            font-size: 12px;
            color: #333;
            line-height: 1.3;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1976D2;
            margin: 10px 0;
        }
        .document-title {
            font-size: 20px;
            font-weight: bold;
            color: #1976D2;
            margin: 15px 0;
            text-align: center;
            text-transform: uppercase;
        }
        .quotation-info {
            margin: 10px 0;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .client-info {
            margin: 10px 0;
            padding: 8px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .section-title {
            font-size: 14px;
            margin-bottom: 5px;
            padding-bottom: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #fff;
        }
        th {
            background-color: #1976D2;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            line-height: 1.3;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .totals {
            float: right;
            width: 350px;
            margin-top: 20px;
        }
        .totals table {
            margin: 0;
        }
        .totals td {
            padding: 8px;
        }
        .totals td:last-child {
            font-weight: bold;
            text-align: right;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .note-section {
            clear: both;
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .note-section .section-title {
            font-size: 14px;
            color: #1976D2;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .note-section div:last-child {
            white-space: pre-line;
            line-height: 1.5;
        }
        .grand-total-row {
            background-color: #1976D2 !important;
            color: white;
            font-weight: bold;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-section">
            <img src="../assets/images/logo.png" alt="Company Logo" class="logo">
        </div>
        <div class="company-info">
            <div class="company-name">LEBAWI NET TRADING PLC</div>
            <div>TIN: 0072010209</div>
            <div>+251924067895 | +251901000231</div>
            <div>info.lebawi.net | www.lebawi.net</div>
            <div>205, Rewina Building, 22 Square</div>
            <div>Bole, Addis Ababa, Ethiopia</div>
        </div>
    </div>

    <div class="document-title">QUOTATION</div>

    <div class="quotation-info">
        <table>
            <tr>
                <td><strong>Quotation #:</strong> <?php echo htmlspecialchars($quotation['quotation_number']); ?></td>
                <td style="text-align: right;"><strong>Date:</strong> <?php echo date('d M Y', strtotime($quotation['created_at'])); ?></td>
            </tr>
        </table>
    </div>

    <div class="client-info">
        <div class="section-title">Client Information</div>
        <table>
            <tr>
                <td><strong>Company:</strong> <?php echo htmlspecialchars($quotation['company_name']); ?></td>
                <td><strong>TIN:</strong> <?php echo htmlspecialchars($quotation['tin_number']); ?></td>
            </tr>
            <tr>
                <td><strong>Address:</strong> <?php echo htmlspecialchars($quotation['address']); ?></td>
                <td><strong>Phone:</strong> <?php echo htmlspecialchars($quotation['phone']); ?></td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Description</th>
                <th style="width: 15%;">Unit Price</th>
                <th style="width: 15%;">Quantity</th>
                <th style="width: 20%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $counter = 1;
            while ($item = $items->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $counter++ . '</td>';
                echo '<td>' . htmlspecialchars($item['product_name']) . '</td>';
                echo '<td>' . number_format($item['unit_price'], 2) . ' ETB</td>';
                echo '<td>' . $item['quantity'] . '</td>';
                echo '<td>' . number_format($item['total_price'], 2) . ' ETB</td>';
                echo '</tr>';
            }
            ?>
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td><strong>Sub Total:</strong></td>
                <td><?php echo number_format($quotation['sub_total'], 2); ?> ETB</td>
            </tr>
            <tr>
                <td><strong>VAT (15%):</strong></td>
                <td><?php echo number_format($quotation['vat_amount'], 2); ?> ETB</td>
            </tr>
            <tr class="grand-total-row">
                <td><strong>Grand Total:</strong></td>
                <td><?php echo number_format($quotation['grand_total'], 2); ?> ETB</td>
            </tr>
        </table>
    </div>

    <?php if (!empty($quotation['note'])): ?>
    <div class="note-section">
        <div class="section-title"><strong>Note:</strong></div>
        <div><?php echo nl2br(htmlspecialchars($quotation['note'])); ?></div>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p><strong>YOUR BUSINESS PARTNER</strong></p>
        <p>Valid for 15 days from the date of issue</p>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #1976D2; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Print Quotation
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #666; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">
            Close
        </button>
    </div>
</body>
</html> 