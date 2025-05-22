<?php
//Email Template for Quotation
function generateQuotationEmailTemplate($quotation, $items) {
    $styles = 'body{font-family:Arial,sans-serif;font-size:14px;line-height:1.4;color:#333;margin:0;padding:20px}.header{margin-bottom:20px;padding-bottom:10px;border-bottom:2px solid #2196F3}.company-info{text-align:right;font-size:12px;color:#333;line-height:1.3}.company-name{font-size:24px;font-weight:700;color:#1976D2;margin:10px 0}.document-title{font-size:20px;font-weight:700;color:#1976D2;margin:15px 0;text-align:center;text-transform:uppercase}table{width:100%;border-collapse:collapse;margin:20px 0}th{background-color:#1976D2;color:#fff;padding:8px;text-align:left}td{padding:8px;border:1px solid #ddd}.totals{width:350px;margin-left:auto}.grand-total{background-color:#1976D2;color:#fff;font-weight:700}.footer{margin-top:30px;text-align:center;font-size:12px;color:#666;border-top:1px solid #ddd;padding-top:20px}';

    $html = "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Quotation #{$quotation['quotation_number']}</title><style>$styles</style></head><body>";
    
    // Header
    $html .= "<div class='header'><div class='company-info'><div class='company-name'>LEBAWI NET TRADING PLC</div>
    <div>TIN: 0072010209</div><div>+251924067895 | +251901000231</div><div>info.lebawi.net | www.lebawi.net</div>
    <div>205, Rewina Building, 22 Square</div><div>Bole, Addis Ababa, Ethiopia</div></div></div>";

    // Document Title
    $html .= "<div class='document-title'>QUOTATION</div>";

    // Quotation Details
    $html .= "<table><tr><td><strong>Quotation #:</strong> " . htmlspecialchars($quotation['quotation_number']) . 
            "</td><td style='text-align:right'><strong>Date:</strong> " . date('d M Y', strtotime($quotation['created_at'])) . "</td></tr></table>";

    // Client Details
    $html .= "<table><tr><td><strong>Company:</strong> " . htmlspecialchars($quotation['company_name']) . 
            "</td><td><strong>TIN:</strong> " . htmlspecialchars($quotation['tin_number']) . 
            "</td></tr><tr><td><strong>Address:</strong> " . htmlspecialchars($quotation['address']) . 
            "</td><td><strong>Phone:</strong> " . htmlspecialchars($quotation['phone']) . "</td></tr></table>";

    // Items Table
    $html .= "<table><thead><tr><th style='width:5%'>#</th><th style='width:45%'>Description</th>
    <th style='width:15%'>Unit Price</th><th style='width:15%'>Quantity</th><th style='width:20%'>Amount</th></tr></thead><tbody>";

    foreach ($items as $i => $item) {
        $html .= "<tr><td>" . ($i + 1) . "</td><td>" . htmlspecialchars($item['product_name']) . 
                "</td><td>" . number_format($item['unit_price'], 2) . " ETB</td><td>" . $item['quantity'] . 
                "</td><td>" . number_format($item['total_price'], 2) . " ETB</td></tr>";
    }

    $html .= "</tbody></table>";

    // Totals
    $html .= "<div class='totals'><table><tr><td><strong>Sub Total:</strong></td><td>" . 
            number_format($quotation['sub_total'], 2) . " ETB</td></tr><tr><td><strong>VAT (15%):</strong></td><td>" . 
            number_format($quotation['vat_amount'], 2) . " ETB</td></tr><tr class='grand-total'><td><strong>Grand Total:</strong></td><td>" . 
            number_format($quotation['grand_total'], 2) . " ETB</td></tr></table></div>";

    // Notes
    if (!empty($quotation['note'])) {
        $html .= "<div style='margin-top:20px;padding:15px;background:#f8f9fa;border:1px solid #ddd'><strong>Note:</strong><br>" . 
                nl2br(htmlspecialchars($quotation['note'])) . "</div>";
    }

    // Footer
    $html .= "<div class='footer'><p><strong>YOUR BUSINESS PARTNER</strong></p><p>Valid for 15 days from the date of issue</p></div></body></html>";

    return $html;
}
?> 