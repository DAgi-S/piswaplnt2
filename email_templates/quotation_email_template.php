<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #1976D2;
            padding: 20px;
            text-align: center;
        }
        .header img {
            max-width: 150px;
            height: auto;
        }
        .header h1 {
            color: white;
            margin: 10px 0;
            font-size: 24px;
        }
        .content {
            padding: 20px;
            background: #fff;
        }
        .footer {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .company-info {
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .highlight {
            color: #1976D2;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="<?php echo 'data:image/png;base64,' . base64_encode(file_get_contents('../assets/images/logo.png')); ?>" alt="LEBAWI NET TRADING PLC">
            <h1>LEBAWI NET TRADING PLC</h1>
        </div>
        
        <div class="content">
            <p>Dear <?php echo htmlspecialchars($quotation['company_name']); ?>,</p>
            
            <p>Thank you for your interest in our products/services. Please find attached our quotation #<?php echo htmlspecialchars($quotation['quotation_number']); ?> for your review.</p>
            
            <?php if (!empty($emailMessage)): ?>
            <p><?php echo nl2br(htmlspecialchars($emailMessage)); ?></p>
            <?php endif; ?>
            
            <p><strong>Important Information:</strong></p>
            <ul>
                <li>This quotation is valid for 15 days from the date of issue</li>
                <li>All prices are in ETB and include 15% VAT</li>
                <li>Payment terms: As per agreement</li>
            </ul>
            
            <div class="company-info">
                <p><strong>LEBAWI NET TRADING PLC</strong></p>
                <p>TIN: 0072010209</p>
                <p>Contact: +251924067895 | +251901000231</p>
                <p>Email: info@lebawi.net</p>
                <p>Website: www.lebawi.net</p>
                <p>Address: 205, Rewina Building, 22 Square, Bole, Addis Ababa, Ethiopia</p>
            </div>
        </div>
        
        <div class="footer">
            <p>This is an automated email. Please do not reply directly to this message.</p>
            <p>If you have any questions, please contact us directly using the information above.</p>
        </div>
    </div>
</body>
</html> 