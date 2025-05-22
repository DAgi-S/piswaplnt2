<?php
require_once 'includes/header.php';
?>

<div class="container">
    <button onclick="window.print()" class="btn btn-primary print-button">
        <i class="fa fa-print"></i> Print Report
    </button>

    <!-- Filters Section -->
    <div class="filters-section no-print">
        <div class="row">
            <div class="col-md-6">
                <h5>Select Period</h5>
                <div class="form-group">
                    <label>Year:</label>
                    <select id="year-select" class="form-control">
                        <?php
                        $currentYear = date('Y');
                        for($year = $currentYear; $year >= $currentYear - 5; $year--) {
                            echo "<option value='$year'>$year</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Month:</label>
                    <select id="month-select" class="form-control">
                        <?php
                        $months = [
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                        foreach ($months as $num => $name) {
                            $selected = ($num == date('n')) ? 'selected' : '';
                            echo "<option value='$num' $selected>$name</option>";
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
            <h2>Monthly Transaction Report</h2>
            <div class="account-info">
                <p><strong>Account Owner:</strong> <?php echo htmlspecialchars($guestData['account_owner']); ?></p>
                <p><strong>Platform:</strong> <?php echo htmlspecialchars($guestData['account_platform']); ?></p>
                <p><strong>Currency:</strong> <?php echo htmlspecialchars($guestData['Currency']); ?></p>
                <p><strong>Period:</strong> <span id="report-period"></span></p>
            </div>
        </div>

        <!-- Summary Section -->
        <div class="month-section">
            <h4>Monthly Summary</h4>
            <div class="row">
                <div class="col-md-3">
                    <div class="summary-box">
                        <div class="summary-title">Total Transactions</div>
                        <div class="summary-value" id="total-transactions">0</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-box">
                        <div class="summary-title">Total Deposits</div>
                        <div class="summary-value text-success" id="total-deposits">0.00</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-box">
                        <div class="summary-title">Total Withdrawals</div>
                        <div class="summary-value text-warning" id="total-withdrawals">0.00</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-box">
                        <div class="summary-title">Net Change</div>
                        <div class="summary-value" id="net-change">0.00</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Transactions -->
        <div class="month-section">
            <h4>Daily Transactions</h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th class="text-right">Deposits</th>
                            <th class="text-right">Withdrawals</th>
                            <th class="text-right">Net Change</th>
                            <th class="text-right">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody id="daily-transactions">
                        <!-- Will be populated dynamically -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Transaction Details -->
        <div class="month-section">
            <h4>Transaction Details</h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody id="transaction-details">
                        <!-- Will be populated dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
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
.summary-box {
    background: var(--bg-white);
    border-radius: var(--border-radius);
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: var(--shadow-sm);
    text-align: center;
}
.summary-title {
    color: var(--text-secondary);
    font-size: 14px;
    margin-bottom: 10px;
}
.summary-value {
    font-size: 24px;
    font-weight: 600;
    color: var(--text-primary);
}
.text-success {
    color: var(--success-color) !important;
}
.text-warning {
    color: var(--warning-color) !important;
}
@media print {
    body {
        background: white;
    }
    .container {
        width: 100%;
        max-width: none;
        padding: 0;
        margin: 0;
    }
    .no-print {
        display: none !important;
    }
    .month-section {
        page-break-inside: avoid;
        margin-bottom: 20px;
        box-shadow: none;
        border: 1px solid #ddd;
    }
    .print-button {
        display: none;
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
        const month = $('#month-select').val();
        const monthName = $('#month-select option:selected').text();

        $('#report-period').text(monthName + ' ' + year);

        // Reset tables
        $('#daily-transactions').empty();
        $('#transaction-details').empty();

        // Fetch data for the selected month
        $.ajax({
            url: 'php_action/fetchMonthlyReport.php',
            type: 'POST',
            data: { 
                year: year,
                month: month
            },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    alert(response.message);
                    return;
                }

                // Update summary
                $('#total-transactions').text(response.summary.totalTransactions);
                $('#total-deposits').text(formatCurrency(response.summary.totalDeposits));
                $('#total-withdrawals').text(formatCurrency(response.summary.totalWithdrawals));
                $('#net-change').text(formatCurrency(response.summary.netChange));

                // Update daily transactions
                response.daily.forEach(function(day) {
                    const row = `<tr>
                        <td>${day.date}</td>
                        <td class="text-right text-success">${formatCurrency(day.deposits)}</td>
                        <td class="text-right text-warning">${formatCurrency(day.withdrawals)}</td>
                        <td class="text-right">${formatCurrency(day.netChange)}</td>
                        <td class="text-right">${formatCurrency(day.runningBalance)}</td>
                    </tr>`;
                    $('#daily-transactions').append(row);
                });

                // Update transaction details
                response.transactions.forEach(function(trans) {
                    const row = `<tr>
                        <td>${trans.date}</td>
                        <td>${trans.reference}</td>
                        <td>${trans.type}</td>
                        <td>${trans.description}</td>
                        <td class="text-right">${trans.amount}</td>
                        <td class="text-right">${trans.balance}</td>
                    </tr>`;
                    $('#transaction-details').append(row);
                });
            },
            error: function() {
                alert('An error occurred while generating the report');
            }
        });
    }

    $('#generate-report').click(generateReport);
    generateReport(); // Generate report for current month on page load
});
</script>

<?php require_once 'includes/footer.php'; ?> 