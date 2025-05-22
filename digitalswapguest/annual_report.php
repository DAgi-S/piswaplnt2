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
        <div class="row">
            <div class="col-md-6">
                <h5>Select Year Range</h5>
                <div class="form-group mb-3">
                    <label for="from-year" class="form-label">From Year:</label>
                    <select id="from-year" class="form-control">
                        <?php
                        $currentYear = date('Y');
                        for($year = $currentYear; $year >= $currentYear - 10; $year--) {
                            echo "<option value='$year'>$year</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label for="to-year" class="form-label">To Year:</label>
                    <select id="to-year" class="form-control">
                        <?php
                        for($year = $currentYear; $year >= $currentYear - 10; $year--) {
                            echo "<option value='$year'>$year</option>";
                        }
                        ?>
                    </select>
                </div>
                <button id="generate-report" class="btn btn-primary">
                    <i class="fa fa-refresh"></i> Generate Report
                </button>
            </div>
        </div>
    </div>

    <!-- Report Content -->
    <div id="printable-area" class="report-container">
        <div class="report-header">
            <h2>Annual Transaction Report</h2>
            <div class="account-info">
                <p><strong>Account Owner:</strong> <?php echo htmlspecialchars($guestData['account_owner'] ?? 'N/A'); ?></p>
                <p><strong>Platform:</strong> <?php echo htmlspecialchars($guestData['account_platform'] ?? 'N/A'); ?></p>
                <p><strong>Currency:</strong> <?php echo htmlspecialchars($guestData['currency'] ?? 'ETB'); ?></p>
                <p><strong>Period:</strong> <span id="report-period"></span></p>
            </div>
        </div>

        <div id="report-container">
            <!-- Year sections will be added here dynamically -->
        </div>

        <!-- Overall Summary -->
        <div class="month-section">
            <h4>Overall Summary</h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th class="text-right">Total Deposits</th>
                            <th class="text-right">Total Withdrawals</th>
                            <th class="text-right">Net Change</th>
                        </tr>
                    </thead>
                    <tbody id="overall-summary">
                        <!-- Will be populated dynamically -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td><strong>Grand Total</strong></td>
                            <td class="text-right"><strong id="grand-total-deposits">0.00</strong></td>
                            <td class="text-right"><strong id="grand-total-withdrawals">0.00</strong></td>
                            <td class="text-right"><strong id="grand-total-net">0.00</strong></td>
                        </tr>
                    </tfoot>
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
.year-section {
    margin-bottom: 40px;
}
.year-section h3 {
    color: var(--primary-color);
    font-size: 20px;
    margin-bottom: 20px;
}
@media print {
    nav.navbar,
    .navbar,
    .breadcrumb,
    .no-print,
    .print-button {
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
    }
    
    .table th, .table td {
        font-size: 11px !important;
        padding: 4px 8px !important;
    }
    
    .table th {
        font-weight: 600 !important;
    }
    
    tfoot tr td {
        font-weight: 600 !important;
    }
}
</style>

<script>
$(document).ready(function() {
    /**
     * Format currency consistently with PHP side
     * @param {number|string} amount - Amount to format
     * @param {string} [currency] - Optional currency symbol
     * @returns {string} Formatted amount
     */
    function formatCurrency(amount, currency = '') {
        // Convert to float and format with 2 decimal places
        const formatted = parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        return currency ? `${currency} ${formatted}` : formatted;
    }

    function generateReport() {
        const fromYear = $('#from-year').val();
        const toYear = $('#to-year').val();

        if (parseInt(fromYear) > parseInt(toYear)) {
            alert('From Year cannot be greater than To Year');
            return;
        }

        $('#report-period').text(fromYear + ' - ' + toYear);
        $('#report-container').empty();
        $('#overall-summary').empty();

        // Show loading state
        const loadingHtml = '<div class="text-center p-5"><i class="fa fa-spinner fa-spin fa-3x"></i><p class="mt-3">Generating report...</p></div>';
        $('#report-container').html(loadingHtml);

        $.ajax({
            url: 'php_action/fetchAnnualReport.php',
            type: 'POST',
            data: { 
                fromYear: fromYear,
                toYear: toYear
            },
            dataType: 'json',
            success: function(response) {
                if (response.error) {
                    alert(response.message);
                    $('#report-container').html('<div class="alert alert-danger">Error: ' + response.message + '</div>');
                    return;
                }

                $('#report-container').empty();
                const currency = response.currency;

                // Process yearly data
                response.yearly.forEach(function(yearData) {
                    // Create year section
                    const yearSection = $(`
                        <div class="month-section year-section">
                            <h4>Year ${yearData.year}</h4>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Month</th>
                                            <th class="text-right">Deposits</th>
                                            <th class="text-right">Withdrawals</th>
                                            <th class="text-right">Net Change</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${yearData.months.map(month => `
                                            <tr>
                                                <td>${month.name}</td>
                                                <td class="text-right">${currency} ${month.deposits}</td>
                                                <td class="text-right">${currency} ${month.withdrawals}</td>
                                                <td class="text-right">${currency} ${month.netChange}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td><strong>Year Total</strong></td>
                                            <td class="text-right"><strong>${currency} ${yearData.summary.totalDeposits}</strong></td>
                                            <td class="text-right"><strong>${currency} ${yearData.summary.totalWithdrawals}</strong></td>
                                            <td class="text-right"><strong>${currency} ${yearData.summary.netChange}</strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    `);

                    $('#report-container').append(yearSection);

                    // Add to overall summary
                    $('#overall-summary').append(`
                        <tr>
                            <td>${yearData.year}</td>
                            <td class="text-right">${currency} ${yearData.summary.totalDeposits}</td>
                            <td class="text-right">${currency} ${yearData.summary.totalWithdrawals}</td>
                            <td class="text-right">${currency} ${yearData.summary.netChange}</td>
                        </tr>
                    `);
                });

                // Update grand totals
                $('#grand-total-deposits').text(`${currency} ${response.grandTotal.totalDeposits}`);
                $('#grand-total-withdrawals').text(`${currency} ${response.grandTotal.totalWithdrawals}`);
                $('#grand-total-net').text(`${currency} ${response.grandTotal.netChange}`);
            },
            error: function(xhr, status, error) {
                alert('An error occurred while generating the report. Please try again.');
                console.error(error);
                $('#report-container').html('<div class="alert alert-danger">Error generating report. Please try again.</div>');
            }
        });
    }

    // Event handlers
    $('#generate-report').click(generateReport);
    
    // Generate report for current year on page load
    generateReport();
});
</script>

<?php require_once 'includes/footer.php'; ?> 