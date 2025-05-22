<?php
function getQuotationEmailTemplate($quotationNumber, $clientName, $message) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background-color: #1976D2;
                color: white;
                padding: 20px;
                text-align: center;
            }
            .content {
                padding: 20px;
                background: #fff;
            }
            .footer {
                text-align: center;
                padding: 20px;
                font-size: 12px;
                color: #666;
                border-top: 1px solid #eee;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                background-color: #1976D2;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 20px;
            }
            .company-info {
                margin-top: 20px;
                font-size: 12px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <h2>LEBAWI NET TRADING PLC</h2>
            </div>
            
            <div class="content">
                <p>Dear ' . htmlspecialchars($clientName) . ',</p>
                
                <p>Thank you for your interest in our products/services. Please find attached our quotation #' . htmlspecialchars($quotationNumber) . ' for your review.</p>
                
                ' . ($message ? '<p>' . nl2br(htmlspecialchars($message)) . '</p>' : '') . '
                
                <p>Key points to note:</p>
                <ul>
                    <li>This quotation is valid for 15 days from the date of issue</li>
                    <li>All prices are in ETB and include 15% VAT</li>
                    <li>Payment terms: As per agreement</li>
                </ul>
                
                <p>If you have any questions or need clarification, please don\'t hesitate to contact us.</p>
                
                <div class="company-info">
                    <p><strong>LEBAWI NET TRADING PLC</strong></p>
                    <p>TIN: 0072010209</p>
                    <p>+251924067895 | +251901000231</p>
                    <p>info.lebawi.net | www.lebawi.net</p>
                    <p>205, Rewina Building, 22 Square, Bole, Addis Ababa, Ethiopia</p>
                </div>
            </div>
            
            <div class="footer">
                <p>This is an automated email. Please do not reply directly to this message.</p>
            </div>
        </div>
    </body>
    </html>';
}
?> 