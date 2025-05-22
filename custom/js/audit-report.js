$(document).ready(function() {
    // Handle Generate Report button click
    $('#generate-report').click(function() {
        const selectedUsdAccounts = $('input[name="usd_accounts[]"]:checked').map(function() {
            return $(this).val();
        }).get();
        
        const selectedEtbAccounts = $('input[name="etb_accounts[]"]:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedUsdAccounts.length === 0 && selectedEtbAccounts.length === 0) {
            alert('Please select at least one account');
            return;
        }

        const year = $('#year-select').val();

        // Update header information
        updateAccountOwnerDisplay();
        $('#report-year').text(year);

        // Fetch data for all months
        for (let month = 1; month <= 12; month++) {
            fetchMonthlyTransactions(month, year, selectedUsdAccounts, selectedEtbAccounts);
        }
    });

    // Function to update account owner display
    function updateAccountOwnerDisplay() {
        const selectedUsdOwners = $('input[name="usd_accounts[]"]:checked').map(function() {
            return $(this).data('owner');
        }).get();
        
        const selectedEtbOwners = $('input[name="etb_accounts[]"]:checked').map(function() {
            return $(this).data('owner');
        }).get();

        const allOwners = [...new Set([...selectedUsdOwners, ...selectedEtbOwners])];
        $('#account-owner').text(allOwners.join(', '));
    }

    // Function to fetch monthly transactions
    function fetchMonthlyTransactions(month, year, usdAccounts, etbAccounts) {
        $.ajax({
            url: 'php_action/fetchAuditTransactions.php',
            type: 'GET',
            data: {
                month: month,
                year: year,
                usd_accounts: usdAccounts,
                etb_accounts: etbAccounts
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update USD transactions
                    updateTransactionTable('usd', response.data.usd, month);
                    updateTotals('usd', response.data.usd_totals, month);

                    // Update ETB transactions
                    updateTransactionTable('etb', response.data.etb, month);
                    updateTotals('etb', response.data.etb_totals, month);

                    // Update monthly summary
                    updateMonthlySummary(response.data.monthly_summary);
                } else {
                    alert('Error: ' + response.messages);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching transactions:', error);
                alert('Error fetching transactions. Please try again.');
            }
        });
    }

    // Function to update transaction table
    function updateTransactionTable(currency, transactions, month) {
        const tbody = $(`#${currency}-transactions-${month}`);
        tbody.empty();

        if (!transactions || transactions.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center">No transactions found</td></tr>');
            return;
        }

        transactions.forEach((transaction, index) => {
            const row = `
                <tr>
                    <td>${index + 1}</td>
                    <td>${transaction.transaction_date}</td>
                    <td>${transaction.name}</td>
                    <td>${transaction.type}</td>
                    <td>${transaction.amount}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Function to update totals
    function updateTotals(currency, totals, month) {
        $(`#${currency}-total-deposit-${month}`).text(totals.total_deposit || '0.00');
        $(`#${currency}-total-withdraw-${month}`).text(totals.total_withdraw || '0.00');
        $(`#${currency}-monthly-closing-${month}`).text(totals.monthly_closing || '0.00');
    }

    // Function to update monthly summary
    function updateMonthlySummary(summary) {
        // Update USD summary
        if (summary.usd) {
            summary.usd.forEach((month, index) => {
                const usdRow = $(`#usd-monthly-summary tr:eq(${index})`);
                usdRow.find('td:eq(1)').text(month.deposit || '0.00');
                usdRow.find('td:eq(2)').text(month.withdraw || '0.00');
            });
        }

        // Update ETB summary
        if (summary.etb) {
            summary.etb.forEach((month, index) => {
                const etbRow = $(`#etb-monthly-summary tr:eq(${index})`);
                etbRow.find('td:eq(1)').text(month.deposit || '0.00');
                etbRow.find('td:eq(2)').text(month.withdraw || '0.00');
            });
        }

        // Update yearly totals
        if (summary.usd_year_totals) {
            $('#usd-year-deposit').text(summary.usd_year_totals.total_deposit || '0.00');
            $('#usd-year-withdraw').text(summary.usd_year_totals.total_withdraw || '0.00');
            $('#usd-year-closing').text(summary.usd_year_totals.yearly_closing || '0.00');
        }

        if (summary.etb_year_totals) {
            $('#etb-year-deposit').text(summary.etb_year_totals.total_deposit || '0.00');
            $('#etb-year-withdraw').text(summary.etb_year_totals.total_withdraw || '0.00');
            $('#etb-year-closing').text(summary.etb_year_totals.yearly_closing || '0.00');
        }
    }
}); 