<!DOCTYPE html>
<html>
<head>
    <title>Annual Report</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <style>
        .report-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .report-header h2 {
            font-size: 20px;
            margin-bottom: 10px;
        }
        .account-info p {
            margin-bottom: 5px;
            font-size: 12px;
        }
        .year-section {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .year-section h4 {
            font-size: 16px;
            margin-bottom: 10px;
        }
        .year-section h5 {
            font-size: 14px;
            margin-bottom: 8px;
        }
        .currency-table {
            margin-bottom: 10px;
        }
        .table {
            font-size: 11px;
            margin-bottom: 10px;
        }
        .table th, .table td {
            padding: 4px 6px;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .totals-section {
            border-top: 1px solid #ddd;
            padding-top: 5px;
            font-size: 11px;
        }
        .totals-section p {
            margin-bottom: 3px;
        }
        .filters-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 12px;
        }
        .account-checkbox {
            margin-right: 15px;
        }
        .print-button {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .container {
                margin: 0;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
            #printable-area, #printable-area * {
                visibility: visible;
            }
            #printable-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .year-section {
                page-break-inside: avoid;
                margin-bottom: 15px;
            }
            .filters-section {
                display: none;
            }
            .print-button {
                display: none;
            }
            @page {
                margin: 1.5cm;
                size: A4;
            }
            .row {
                margin: 0;
            }
            .col-md-6 {
                padding: 0 5px;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    <?php require_once 'php_action/db_connect.php'; ?>

    <div class="container">
        <button onclick="window.print()" class="btn btn-primary print-button">
            <i class="glyphicon glyphicon-print"></i> Print Report
        </button>

        <!-- Filters Section -->
        <div class="filters-section no-print">
            <div class="row">
                <div class="col-md-6">
                    <h5>Select Accounts</h5>
                    <div id="account-checkboxes">
                        <?php
                        // Fetch USD accounts
                        $usdQuery = "SELECT id, account_owner, account_platform FROM accounts WHERE Currency = 'USD' AND status = 1";
                        $usdResult = $connect->query($usdQuery);
                        
                        // Fetch ETB accounts
                        $etbQuery = "SELECT id, account_owner, account_platform FROM accounts WHERE Currency = 'ETB' AND status = 1";
                        $etbResult = $connect->query($etbQuery);
                        
                        echo "<div class='mb-3'><strong>USD Accounts:</strong><br>";
                        while($account = $usdResult->fetch_assoc()) {
                            echo "<label class='account-checkbox'>
                                    <input type='checkbox' name='usd_accounts[]' value='{$account['id']}' data-owner='{$account['account_owner']}'>
                                    {$account['account_owner']} ({$account['account_platform']})
                                  </label>";
                        }
                        
                        echo "</div><div class='mb-3'><strong>ETB Accounts:</strong><br>";
                        while($account = $etbResult->fetch_assoc()) {
                            echo "<label class='account-checkbox'>
                                    <input type='checkbox' name='etb_accounts[]' value='{$account['id']}' data-owner='{$account['account_owner']}'>
                                    {$account['account_owner']} ({$account['account_platform']})
                                  </label>";
                        }
                        echo "</div>";
                        ?>
                    </div>
                </div>
                <div class="col-md-3">
                    <h5>Select Year Range</h5>
                    <div class="form-group">
                        <label>From Year:</label>
                        <select id="from-year" class="form-control">
                            <?php
                            $currentYear = date('Y');
                            for($year = $currentYear; $year >= $currentYear - 10; $year--) {
                                echo "<option value='$year'>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>To Year:</label>
                        <select id="to-year" class="form-control">
                            <?php
                            for($year = $currentYear; $year >= $currentYear - 10; $year--) {
                                echo "<option value='$year'>$year</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button id="generate-report" class="btn btn-primary">Generate Report</button>
                </div>
            </div>
        </div>

        <!-- Report Content -->
        <div id="printable-area">
            <div class="report-header">
                <h2>Annual Transaction Report</h2>
                <div class="account-info">
                    <p><strong>Account Owner:</strong> <span id="account-owner"></span></p>
                    <p><strong>Period:</strong> <span id="report-period"></span></p>
                </div>
            </div>
            
            <div id="report-container">
                <!-- Year sections will be added here dynamically -->
            </div>
        </div>
    </div>

    <!-- Include jQuery -->
    <script src="assests/jquery/jquery.min.js"></script>
    <!-- Include Bootstrap JS -->
    <script src="assests/bootstrap/js/bootstrap.min.js"></script>
    <!-- Include Custom JS -->
    <script src="custom/js/annual-report.js"></script>
</body>
</html> 