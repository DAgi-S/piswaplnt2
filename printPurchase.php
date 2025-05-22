<?php
// Start output buffering
ob_start();

// Include core functionality
require_once 'php_action/core.php';

// Check for permissions and valid purchase ID
if(!isset($_GET['id']) || !hasPermission('view_purchases')) {
    ob_end_clean(); // Clear any output
    header('location: managePurchases.php');
    exit();
}

$purchaseId = (int)$_GET['id'];

// Fetch purchase details with prepared statement
$sql = "SELECT p.*, s.company_name, s.contact_person, s.phone, s.address 
        FROM purchases p 
        JOIN suppliers s ON p.supplier_id = s.id 
        WHERE p.id = ?";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $purchaseId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    ob_end_clean(); // Clear any output
    header('location: managePurchases.php');
    exit();
}

$purchase = $result->fetch_array();

// Fetch purchase items with prepared statement
$sql = "SELECT pi.*, pr.name as product_name 
        FROM purchase_items pi 
        JOIN products pr ON pi.product_id = pr.product_id 
        WHERE pi.purchase_id = ?";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $purchaseId);
$stmt->execute();
$items = $stmt->get_result();

// Now we can safely output HTML
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Purchase Order #<?php echo htmlspecialchars($purchase['purchase_number']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap-theme.min.css">
    
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .print-container {
            max-width: 21cm;
            margin: 0 auto 20px;
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #1976D2;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #1976D2;
            margin: 10px 0;
        }
        .company-info {
            font-size: 11px;
            color: #333;
            line-height: 1.3;
        }
        .document-title {
            font-size: 18px;
            font-weight: bold;
            color: #1976D2;
            margin: 15px 0;
            text-align: center;
            text-transform: uppercase;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .info-section {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .info-item {
            margin: 5px 0;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 11px;
            background: white;
        }
        th {
            background-color: #1976D2;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 11px;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .totals-section {
            margin-top: 20px;
            float: right;
            width: 350px;
        }
        .totals-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .totals-table td {
            padding: 5px;
        }
        .totals-table td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .grand-total {
            background-color: #1976D2;
            color: white;
        }
        .signature-section {
            clear: both;
            margin-top: 40px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .signature-box {
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .action-buttons {
            text-align: center;
            margin: 20px 0;
        }
        .btn {
            padding: 8px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        .btn-primary {
            background: #1976D2;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        @media print {
            body {
                padding: 0;
                background: white;
            }
            .print-container {
                box-shadow: none;
                padding: 0;
                margin: 0;
            }
            .action-buttons {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="action-buttons">
        <button onclick="window.print()" class="btn btn-primary">Print Purchase Order</button>
        <button onclick="window.location.href='managePurchases.php'" class="btn btn-secondary">Back to Purchases</button>
    </div>

    <div class="print-container">
        <div class="header">
            <div class="company-name">LEBAWI NET TRADING PLC</div>
            <div class="company-info">
                <div>TIN: 0072010209</div>
                <div>+251924067895 | +251901000231</div>
                <div>info.lebawi.net | www.lebawi.net</div>
                <div>205, Rewina Building, 22 Square</div>
                <div>Bole, Addis Ababa, Ethiopia</div>
            </div>
        </div>

        <div class="document-title">Purchase Order</div>

        <div class="info-section">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Purchase No:</span> 
                    <?php echo htmlspecialchars($purchase['purchase_number']); ?>
                </div>
                <div class="info-item">
                    <span class="info-label">Date:</span> 
                    <?php echo date('d M Y', strtotime($purchase['purchase_date'])); ?>
                </div>
                <div class="info-item">
                    <span class="info-label">Status:</span> 
                    <?php echo ($purchase['status'] == 1 ? 'Completed' : 'Pending'); ?>
                </div>
            </div>
        </div>

        <div class="info-section">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Company:</span> 
                    <?php echo htmlspecialchars($purchase['company_name']); ?>
                </div>
                <div class="info-item">
                    <span class="info-label">Contact:</span> 
                    <?php echo htmlspecialchars($purchase['contact_person']); ?>
                </div>
                <div class="info-item">
                    <span class="info-label">Phone:</span> 
                    <?php echo htmlspecialchars($purchase['phone']); ?>
                </div>
                <div class="info-item">
                    <span class="info-label">Address:</span> 
                    <?php echo htmlspecialchars($purchase['address']); ?>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 45%;">Product Description</th>
                    <th style="width: 15%;" class="text-right">Unit Price</th>
                    <th style="width: 15%;" class="text-right">Quantity</th>
                    <th style="width: 20%;" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $count = 1;
                while($item = $items->fetch_array()) {
                    echo '<tr>';
                    echo '<td>' . $count++ . '</td>';
                    echo '<td>' . htmlspecialchars($item['product_name']) . '</td>';
                    echo '<td class="text-right">' . number_format($item['rate'], 2) . ' ETB</td>';
                    echo '<td class="text-right">' . number_format($item['quantity'], 0) . '</td>';
                    echo '<td class="text-right">' . number_format($item['total'], 2) . ' ETB</td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>

        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td colspan="3" class="text-right"><strong>Sub Total:</strong></td>
                    <td class="text-right"><?php echo number_format($purchase['sub_total'], 2); ?> ETB</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right"><strong>VAT (15%):</strong></td>
                    <td class="text-right"><?php echo number_format($purchase['vat'], 2); ?> ETB</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-right"><strong>Total (Sub Total + VAT):</strong></td>
                    <td class="text-right"><?php 
                        $total = $purchase['sub_total'] + $purchase['vat'];
                        echo number_format($total, 2); 
                    ?> ETB</td>
                </tr>
                <?php if($purchase['withholding_tax_enabled']): ?>
                <tr>
                    <td colspan="3" class="text-right"><strong>Withholding Tax (2%):</strong></td>
                    <td class="text-right"><?php 
                        $withholdingAmount = $purchase['sub_total'] * 0.02;
                        echo number_format($withholdingAmount, 2); 
                    ?> ETB</td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="3" class="text-right"><strong>Grand Total (Total - Withholding):</strong></td>
                    <td class="text-right"><strong><?php 
                        $withholdingAmount = $purchase['withholding_tax_enabled'] ? ($purchase['sub_total'] * 0.02) : 0;
                        $grandTotal = $total - $withholdingAmount;
                        echo number_format($grandTotal, 2); 
                    ?> ETB</strong></td>
                </tr>
            </table>
        </div>

        <div style="clear: both;"></div>

        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Prepared By</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Approved By</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Received By</div>
            </div>
        </div>

        <div class="footer">
            <p>This is a computer generated document. No signature is required.</p>
            <p>For any inquiries, please contact us at +251-901-000-251 or email at info@lebawi.net</p>
        </div>
    </div>

    <!-- jQuery -->
    <script src="assests/jquery/jquery.min.js"></script>
    <!-- Bootstrap JavaScript -->
    <script src="assests/bootstrap/js/bootstrap.min.js"></script>
</body>
</html>
<?php
// End output buffering and send output
ob_end_flush();
?> 