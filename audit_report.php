<!DOCTYPE html>
<html>
<head>
    <title>Audit Report</title>
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
        .month-section {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .month-section h4 {
            font-size: 16px;
            margin-bottom: 10px;
        }
        .month-section h5 {
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
            .month-section {
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
                        $usdQuery = "SELECT id, account_owner, account_platform FROM accounts WHERE currency = 'USD'";
                        $usdResult = $connect->query($usdQuery);
                        
                        // Fetch ETB accounts
                        $etbQuery = "SELECT id, account_owner, account_platform FROM accounts WHERE currency = 'ETB'";
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
                    <h5>Select Year</h5>
                    <select id="year-select" class="form-control">
                        <?php
                        $currentYear = date('Y');
                        for($year = $currentYear; $year >= $currentYear - 5; $year--) {
                            $selected = ($year == $currentYear) ? 'selected' : '';
                            echo "<option value='$year' $selected>$year</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <h5>&nbsp;</h5>
                    <button id="generate-report" class="btn btn-primary">Generate Report</button>
                </div>
            </div>
        </div>

        <div id="printable-area">
            <div class="report-header">
                <h2>AUDIT REPORTS</h2>
                <div class="account-info">
                    <p><strong>Account owner:</strong> <span id="account-owner">Select accounts above</span></p>
                    <p><strong>Year:</strong> <span id="report-year">2024</span></p>
                </div>
            </div>

            <!-- Monthly Sections -->
            <?php
            $months = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ];

            foreach ($months as $monthNum => $monthName) {
                echo "
                <div class='month-section' id='month-{$monthNum}'>
                    <h4>Month - {$monthName}</h4>
                    <div class='row'>
                        <!-- USD Table -->
                        <div class='col-md-6'>
                            <h5>USD</h5>
                            <table class='table table-bordered currency-table'>
                                <thead>
                                    <tr>
                                        <th>Number</th>
                                        <th>Transaction date</th>
                                        <th>Deposited to</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody id='usd-transactions-{$monthNum}'>
                                    <!-- Transaction rows will be populated dynamically -->
                                </tbody>
                            </table>
                            <div class='totals-section'>
                                <div class='row'>
                                    <div class='col-md-12'>
                                        <p><strong>Total Deposit:</strong> <span id='usd-total-deposit-{$monthNum}'>0.00</span></p>
                                        <p><strong>Total Withdraw:</strong> <span id='usd-total-withdraw-{$monthNum}'>0.00</span></p>
                                        <p><strong>Monthly closing:</strong> <span id='usd-monthly-closing-{$monthNum}'>0.00</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ETB Table -->
                        <div class='col-md-6'>
                            <h5>ETB</h5>
                            <table class='table table-bordered currency-table'>
                                <thead>
                                    <tr>
                                        <th>Number</th>
                                        <th>Transaction date</th>
                                        <th>Deposited to</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody id='etb-transactions-{$monthNum}'>
                                    <!-- Transaction rows will be populated dynamically -->
                                </tbody>
                            </table>
                            <div class='totals-section'>
                                <div class='row'>
                                    <div class='col-md-12'>
                                        <p><strong>Total Deposit:</strong> <span id='etb-total-deposit-{$monthNum}'>0.00</span></p>
                                        <p><strong>Total Withdraw:</strong> <span id='etb-total-withdraw-{$monthNum}'>0.00</span></p>
                                        <p><strong>Monthly closing:</strong> <span id='etb-monthly-closing-{$monthNum}'>0.00</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>";
            }
            ?>

            <!-- Year Summary Section -->
            <div class="month-section">
                <h4>Each Month report</h4>
                <div class="row">
                    <!-- USD Summary -->
                    <div class="col-md-6">
                        <h5>USD</h5>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Months</th>
                                    <th>Deposit</th>
                                    <th>Withdraw</th>
                                </tr>
                            </thead>
                            <tbody id="usd-monthly-summary">
                                <?php
                                foreach ($months as $monthName) {
                                    echo "<tr>
                                        <td>{$monthName}</td>
                                        <td>0.00</td>
                                        <td>0.00</td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3">
                                        <strong>Total Deposit:</strong> <span id="usd-year-deposit">0.00</span><br>
                                        <strong>Total Withdraw:</strong> <span id="usd-year-withdraw">0.00</span><br>
                                        <strong>Yearly closing:</strong> <span id="usd-year-closing">0.00</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- ETB Summary -->
                    <div class="col-md-6">
                        <h5>ETB</h5>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Months</th>
                                    <th>Deposit</th>
                                    <th>Withdraw</th>
                                </tr>
                            </thead>
                            <tbody id="etb-monthly-summary">
                                <?php
                                foreach ($months as $monthName) {
                                    echo "<tr>
                                        <td>{$monthName}</td>
                                        <td>0.00</td>
                                        <td>0.00</td>
                                    </tr>";
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3">
                                        <strong>Total Deposit:</strong> <span id="etb-year-deposit">0.00</span><br>
                                        <strong>Total Withdraw:</strong> <span id="etb-year-withdraw">0.00</span><br>
                                        <strong>Yearly closing:</strong> <span id="etb-year-closing">0.00</span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include necessary JavaScript files -->
    <script src="assests/jquery/jquery.min.js"></script>
    <script src="assests/bootstrap/js/bootstrap.min.js"></script>
    <script src="custom/js/audit-report.js"></script>

</body>
</html> 