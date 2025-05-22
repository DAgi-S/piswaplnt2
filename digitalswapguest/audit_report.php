<?php
require_once 'includes/header.php';
?>

<!-- Include shared report styles -->
<link rel="stylesheet" href="custom/css/reports.css">

<div class="container">
    <button onclick="window.print()" class="btn btn-primary print-button">
        <i class="fa fa-print"></i> Print Report
    </button>

    <!-- Filters Section -->
    <div class="filters-section no-print">
        <?php
        error_log("Active account session check in audit_report.php:");
        error_log("Session data: " . print_r($_SESSION, true));
        error_log("Current user data: " . print_r($currentUser, true));
        ?>
        <div class="row">
            <div class="col-md-6">
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

    <div id="printable-area" class="report-container">
        <div class="report-header">
            <h2>AUDIT REPORT</h2>
            <div class="account-info">
                <p><strong>Account Owner:</strong> <?php echo htmlspecialchars($guestData['account_owner'] ?? ''); ?></p>
                <p><strong>Platform:</strong> <?php echo htmlspecialchars($guestData['account_platform'] ?? ''); ?></p>
                <p><strong>Currency:</strong> <?php echo htmlspecialchars($guestData['Currency'] ?? 'ETB'); ?></p>
                <p><strong>Year:</strong> <span id="report-year"><?php echo date('Y'); ?></span></p>
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
                <div class='table-responsive'>
                    <table class='table table-bordered currency-table'>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th class='text-right'>Amount</th>
                                <th class='text-right'>Balance</th>
                            </tr>
                        </thead>
                        <tbody id='transactions-{$monthNum}'>
                            <!-- Transaction rows will be populated dynamically -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan='4' class='text-right'><strong>Monthly Summary:</strong></td>
                                <td class='text-right'>
                                    <div><strong>Deposits:</strong> <span id='deposits-{$monthNum}'>0.00</span></div>
                                    <div><strong>Withdrawals:</strong> <span id='withdrawals-{$monthNum}'>0.00</span></div>
                                </td>
                                <td class='text-right'>
                                    <strong>Net Change:</strong> <span id='net-change-{$monthNum}'>0.00</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>";
        }
        ?>
    </div>
</div>

<style>
.report-header {
    text-align: center;
    margin-bottom: 20px;
}
.report-header h2 {
    font-size: 24px;
    margin-bottom: 15px;
    color: var(--primary-color);
}
.account-info p {
    margin-bottom: 5px;
    font-size: 14px;
}
.month-section {
    background: var(--bg-white);
    border-radius: var(--border-radius);
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: var(--shadow-sm);
}
.month-section h4 {
    color: var(--primary-color);
    font-size: 18px;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--primary-color);
}
.currency-table {
    margin-bottom: 0;
}
.filters-section {
    background: var(--bg-white);
    padding: 25px;
    border-radius: var(--border-radius);
    margin-bottom: 25px;
    box-shadow: var(--shadow-sm);
}
.print-button {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
    padding: 10px 20px;
}
@media print {
    nav.navbar,
    .navbar,
    .breadcrumb,
    .no-print,
    .print-button,
    .alert {
        display: none !important;
    }

    body {
        margin: 0 !important;
        padding: 0 !important;
        background: white;
    }

    .container {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    #printable-area {
        margin-top: 0 !important;
        padding-top: 0 !important;
        font-size: 11px !important;
    }

    .report-header h2 {
        font-size: 16px !important;
        margin-bottom: 10px !important;
        margin-top: 0 !important;
        padding-top: 0 !important;
    }

    .account-info p {
        font-size: 11px !important;
        margin-bottom: 3px !important;
    }

    .month-section h4 {
        font-size: 13px !important;
        margin-bottom: 10px !important;
        padding-bottom: 5px !important;
    }

    .table th, 
    .table td {
        font-size: 11px !important;
        padding: 4px 8px !important;
    }

    .table th {
        font-weight: 600 !important;
    }

    tfoot tr td {
        font-weight: 600 !important;
    }

    .month-section {
        page-break-inside: avoid;
        margin-bottom: 15px !important;
        padding: 15px !important;
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }

    .badge {
        font-size: 10px !important;
        padding: 3px 6px !important;
    }

    @page {
        margin: 1.5cm;
        size: A4;
    }
}
</style>

<script>
$(document).ready(function() {
    function formatCurrency(amount) {
        return parseFloat(amount).toFixed(2);
    }

    function generateReport() {
        const year = $('#year-select').val();
        $('#report-year').text(year);

        // Reset all tables
        $('[id^=transactions-]').empty();
        $('[id^=deposits-]').text('0.00');
        $('[id^=withdrawals-]').text('0.00');
        $('[id^=net-change-]').text('0.00');

        // Fetch data for the selected year
        $.ajax({
            url: 'php_action/fetchAuditReport.php',
            type: 'POST',
            data: { year: year },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    alert(response.message);
                    return;
                }

                // Process monthly data
                response.monthly.forEach(function(month) {
                    const monthNum = month.month;
                    const $tbody = $(`#transactions-${monthNum}`);
                    
                    if (month.transactions.length === 0) {
                        $tbody.html('<tr><td colspan="6" class="text-center">No transactions for this month</td></tr>');
                        return;
                    }
                    
                    // Add transactions
                    month.transactions.forEach(function(trans) {
                        const row = `<tr>
                            <td>${trans.date}</td>
                            <td>${trans.reference}</td>
                            <td>${trans.type}</td>
                            <td>${trans.description}</td>
                            <td class="text-right">${trans.amount}</td>
                            <td class="text-right">${trans.balance}</td>
                        </tr>`;
                        $tbody.append(row);
                    });

                    // Update monthly summary
                    $(`#deposits-${monthNum}`).text(formatCurrency(month.summary.deposits));
                    $(`#withdrawals-${monthNum}`).text(formatCurrency(month.summary.withdrawals));
                    $(`#net-change-${monthNum}`).text(formatCurrency(month.summary.netChange));
                });
            },
            error: function() {
                alert('An error occurred while generating the report');
            }
        });
    }

    $('#generate-report').click(generateReport);
    generateReport(); // Generate report for current year on page load
});
</script>

<?php require_once 'includes/footer.php'; ?> 