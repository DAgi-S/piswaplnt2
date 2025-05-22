<?php
require_once 'core.php';

if(isset($_POST['purchaseId'])) {
    $purchaseId = $_POST['purchaseId'];
    
    // Fetch purchase details with prepared statement
    $sql = "SELECT p.*, s.company_name, s.contact_person, s.phone, s.address 
            FROM purchases p 
            JOIN suppliers s ON p.supplier_id = s.id 
            WHERE p.id = ?";
    
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $purchaseId);
    $stmt->execute();
    $result = $stmt->get_result();
    if (!$result) {
        die("Error: " . $connect->error);
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
    if (!$items) {
        die("Error: " . $connect->error);
    }
    
    // Generate print preview HTML
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Purchase Order #' . htmlspecialchars($purchase['purchase_number']) . '</title>
        <style>
            @page {
                size: A4;
                margin: 1cm;
            }
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.4;
                margin: 0;
                padding: 20px;
                color: #333;
                background: white;
            }
            * {
                box-sizing: border-box;
            }
            .print-container {
                max-width: 21cm;
                margin: 0 auto;
                background: white;
                padding: 20px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
            @media print {
                body {
                    padding: 0;
                    background: white;
                }
                .print-container {
                    box-shadow: none;
                    padding: 0;
                }
                .no-print {
                    display: none !important;
                }
            }
        </style>
    </head>
    <body>
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
                        ' . htmlspecialchars($purchase['purchase_number']) . '
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date:</span> 
                        ' . date('d M Y', strtotime($purchase['purchase_date'])) . '
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status:</span> 
                        ' . ($purchase['status'] == 1 ? 'Completed' : 'Pending') . '
                    </div>
                </div>
            </div>

            <div class="info-section">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Company:</span> 
                        ' . htmlspecialchars($purchase['company_name']) . '
                    </div>
                    <div class="info-item">
                        <span class="info-label">Contact:</span> 
                        ' . htmlspecialchars($purchase['contact_person']) . '
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone:</span> 
                        ' . htmlspecialchars($purchase['phone']) . '
                    </div>
                    <div class="info-item">
                        <span class="info-label">Address:</span> 
                        ' . htmlspecialchars($purchase['address']) . '
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
                <tbody>';
    
    $count = 1;
    while($item = $items->fetch_array()) {
        $html .= '
                    <tr>
                        <td>' . $count++ . '</td>
                        <td>' . htmlspecialchars($item['product_name']) . '</td>
                        <td class="text-right">' . number_format($item['rate'], 2) . ' ETB</td>
                        <td class="text-right">' . number_format($item['quantity'], 0) . '</td>
                        <td class="text-right">' . number_format($item['total'], 2) . ' ETB</td>
                    </tr>';
    }
    
    $html .= '
                </tbody>
            </table>

            <div class="totals-section">
                <table class="totals-table">
                    <tr>
                        <td>Sub Total:</td>
                        <td>' . number_format($purchase['sub_total'], 2) . ' ETB</td>
                    </tr>
                    <tr>
                        <td>VAT (15%):</td>
                        <td>' . number_format($purchase['vat'], 2) . ' ETB</td>
                    </tr>';
    
    if($purchase['withholding_tax_enabled']) {
        $html .= '
                    <tr>
                        <td>W/Tax (2%):</td>
                        <td>' . number_format($purchase['withholding_tax_amount'], 2) . ' ETB</td>
                    </tr>';
    }
    
    $html .= '
                    <tr class="grand-total">
                        <td>Grand Total:</td>
                        <td>' . number_format($purchase['grand_total'], 2) . ' ETB</td>
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
                <p>For any inquiries, please contact us at +251-911-123456 or email at info@lebawinet.com</p>
            </div>
        </div>

        <script>
            window.onload = function() {
                window.print();
            }
        </script>
    </body>
    </html>';
    
    echo $html;
} else {
    echo "No purchase ID provided";
} 