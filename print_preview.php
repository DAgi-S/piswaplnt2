<?php
define('BASEPATH', true);
require_once 'includes/print_settings.php';

// Get template name from query string
$template_name = isset($_GET['template']) ? $_GET['template'] : 'default';

// Get print settings
$settings = get_print_styles($template_name);
$print_css = generate_print_css($template_name);
$company_info = get_print_company_info();

// Sample data for preview
$sample_data = [
    'invoice_no' => 'INV-2024-001',
    'date' => date('Y-m-d'),
    'customer' => [
        'name' => 'Sample Customer',
        'address' => '123 Sample Street',
        'city' => 'Sample City',
        'phone' => '+1234567890'
    ],
    'items' => [
        [
            'description' => 'Product 1',
            'quantity' => 2,
            'price' => 100.00,
            'total' => 200.00
        ],
        [
            'description' => 'Product 2',
            'quantity' => 1,
            'price' => 150.00,
            'total' => 150.00
        ],
        [
            'description' => 'Product 3',
            'quantity' => 3,
            'price' => 75.00,
            'total' => 225.00
        ]
    ],
    'subtotal' => 575.00,
    'tax' => 57.50,
    'total' => 632.50
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Preview - <?php echo ucfirst($template_name); ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        <?php echo $print_css; ?>
        
        .preview-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72px;
            opacity: 0.1;
            pointer-events: none;
            z-index: 1000;
        }
        
        .document-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .company-header {
            text-align: <?php echo $settings['header_alignment']; ?>;
            margin-bottom: 30px;
        }
        
        .company-logo {
            max-width: <?php echo $settings['logo_width']; ?>;
            height: auto;
        }
        
        .document-title {
            font-size: 24px;
            margin: 20px 0;
            color: #333;
        }
        
        .document-info {
            margin-bottom: 20px;
        }
        
        .customer-info {
            margin-bottom: 30px;
        }
        
        .items-table {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }
        
        .items-table th,
        .items-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        .items-table th {
            background: #f8f9fa;
        }
        
        .totals-section {
            width: 300px;
            margin-left: auto;
            margin-bottom: 30px;
        }
        
        .print-footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="preview-watermark">PREVIEW</div>
    
    <div class="document-container">
        <!-- Company Header -->
        <div class="company-header">
            <?php if ($settings['logo_path']): ?>
                <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Company Logo" class="company-logo">
            <?php endif; ?>
            <h2><?php echo htmlspecialchars($company_info['name']); ?></h2>
            <p><?php echo htmlspecialchars($company_info['address']); ?></p>
            <p>
                Phone: <?php echo htmlspecialchars($company_info['phone']); ?> |
                Email: <?php echo htmlspecialchars($company_info['email']); ?> |
                Web: <?php echo htmlspecialchars($company_info['website']); ?>
            </p>
        </div>
        
        <!-- Document Title -->
        <h1 class="document-title"><?php echo ucfirst($template_name); ?></h1>
        
        <!-- Document Info -->
        <div class="document-info row">
            <div class="col-6">
                <strong>Document No:</strong> <?php echo htmlspecialchars($sample_data['invoice_no']); ?><br>
                <strong>Date:</strong> <?php echo htmlspecialchars($sample_data['date']); ?>
            </div>
        </div>
        
        <!-- Customer Info -->
        <div class="customer-info">
            <h5>Bill To:</h5>
            <p>
                <?php echo htmlspecialchars($sample_data['customer']['name']); ?><br>
                <?php echo htmlspecialchars($sample_data['customer']['address']); ?><br>
                <?php echo htmlspecialchars($sample_data['customer']['city']); ?><br>
                Phone: <?php echo htmlspecialchars($sample_data['customer']['phone']); ?>
            </p>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sample_data['items'] as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td><?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo number_format($item['total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Totals Section -->
        <div class="totals-section">
            <table class="table table-sm">
                <tr>
                    <td><strong>Subtotal:</strong></td>
                    <td class="text-right"><?php echo number_format($sample_data['subtotal'], 2); ?></td>
                </tr>
                <tr>
                    <td><strong>Tax (10%):</strong></td>
                    <td class="text-right"><?php echo number_format($sample_data['tax'], 2); ?></td>
                </tr>
                <tr>
                    <td><strong>Total:</strong></td>
                    <td class="text-right"><strong><?php echo number_format($sample_data['total'], 2); ?></strong></td>
                </tr>
            </table>
        </div>
        
        <!-- Footer -->
        <div class="print-footer">
            <?php echo str_replace('{year}', date('Y'), htmlspecialchars($settings['footer_text'])); ?>
        </div>
    </div>
</body>
</html> 