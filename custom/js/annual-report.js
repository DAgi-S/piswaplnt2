$(document).ready(function() {
    // Track overall totals
    var overallTotals = {
        usd: { deposits: 0, withdrawals: 0 },
        etb: { deposits: 0, withdrawals: 0 }
    };

    // Handle Generate Report button click
    $('#generate-report').click(function() {
        var usdAccounts = [];
        var etbAccounts = [];
        
        // Collect selected USD accounts
        $('input[name="usd_accounts[]"]:checked').each(function() {
            usdAccounts.push($(this).val());
        });
        
        // Collect selected ETB accounts
        $('input[name="etb_accounts[]"]:checked').each(function() {
            etbAccounts.push($(this).val());
        });
        
        // Validate selections
        if (usdAccounts.length === 0 && etbAccounts.length === 0) {
            alert('Please select at least one account.');
            return;
        }
        
        var fromYear = $('#from-year').val();
        var toYear = $('#to-year').val();
        
        if (parseInt(fromYear) > parseInt(toYear)) {
            alert('From Year cannot be greater than To Year.');
            return;
        }
        
        // Reset overall totals
        overallTotals = {
            usd: { deposits: 0, withdrawals: 0 },
            etb: { deposits: 0, withdrawals: 0 }
        };
        
        // Clear previous report
        $('#report-container').empty();
        
        // Update account owner display
        updateAccountOwnerDisplay();
        
        // Fetch data for each year in the range
        for (var year = fromYear; year <= toYear; year++) {
            fetchAnnualTransactions(year, usdAccounts, etbAccounts);
        }
    });
    
    function updateAccountOwnerDisplay() {
        var owners = [];
        $('input[type="checkbox"]:checked').each(function() {
            owners.push($(this).data('owner'));
        });
        $('#account-owner').text(owners.join(', '));
        
        var fromYear = $('#from-year').val();
        var toYear = $('#to-year').val();
        $('#report-period').text(fromYear + ' - ' + toYear);
    }
    
    function fetchAnnualTransactions(year, usdAccounts, etbAccounts) {
        $.ajax({
            url: 'php_action/fetchAnnualTransactions.php',
            type: 'GET',
            data: {
                year: year,
                usd_accounts: usdAccounts,
                etb_accounts: etbAccounts
            },
            success: function(response) {
                if (response.success) {
                    updateYearSection(year, response.data);
                    updateOverallTotals(response.data);
                    updateOverallSummary(year);
                } else {
                    alert('Error fetching transactions. Please try again.');
                }
            },
            error: function() {
                alert('Error fetching transactions. Please try again.');
            }
        });
    }
    
    function updateYearSection(year, data) {
        var yearSection = $('<div>').addClass('year-section');
        yearSection.append($('<h4>').text('Year ' + year));
        
        // USD Section
        if (Object.keys(data.usd).length > 0) {
            yearSection.append($('<h5>').text('USD Transactions'));
            yearSection.append(createCurrencyTable('usd', year, data.usd));
        }
        
        // ETB Section
        if (Object.keys(data.etb).length > 0) {
            yearSection.append($('<h5>').text('ETB Transactions'));
            yearSection.append(createCurrencyTable('etb', year, data.etb));
        }
        
        // Add to report container
        $('#report-container').append(yearSection);
    }
    
    function updateOverallTotals(data) {
        // Update USD totals
        Object.keys(data.usd).forEach(function(accountId) {
            var account = data.usd[accountId];
            for (var month = 1; month <= 12; month++) {
                overallTotals.usd.deposits += account.months[month].deposits;
                overallTotals.usd.withdrawals += account.months[month].withdrawals;
            }
        });
        
        // Update ETB totals
        Object.keys(data.etb).forEach(function(accountId) {
            var account = data.etb[accountId];
            for (var month = 1; month <= 12; month++) {
                overallTotals.etb.deposits += account.months[month].deposits;
                overallTotals.etb.withdrawals += account.months[month].withdrawals;
            }
        });
    }
    
    function updateOverallSummary(year) {
        // Remove existing summary if present
        $('#overall-summary').remove();
        $('#instructions-section').remove();
        
        var summarySection = $('<div>').addClass('year-section').attr('id', 'overall-summary');
        summarySection.append($('<h4>').text('Overall Summary'));
        
        // Create summary table
        var table = $('<table>').addClass('table table-bordered');
        var thead = $('<thead>').appendTo(table);
        var tbody = $('<tbody>').appendTo(table);
        
        // Header
        thead.append($('<tr>')
            .append($('<th>').text('Currency'))
            .append($('<th>').text('Total Deposits'))
            .append($('<th>').text('Total Withdrawals'))
            .append($('<th>').text('Net Balance')));
        
        // USD Row
        var usdBalance = overallTotals.usd.deposits + overallTotals.usd.withdrawals;
        tbody.append($('<tr>')
            .append($('<td>').text('USD'))
            .append($('<td>').text(formatAmount(overallTotals.usd.deposits)))
            .append($('<td>').text(formatAmount(Math.abs(overallTotals.usd.withdrawals))))
            .append($('<td>').text(formatAmount(usdBalance))));
        
        // ETB Row
        var etbBalance = overallTotals.etb.deposits + overallTotals.etb.withdrawals;
        tbody.append($('<tr>')
            .append($('<td>').text('ETB'))
            .append($('<td>').text(formatAmount(overallTotals.etb.deposits)))
            .append($('<td>').text(formatAmount(Math.abs(overallTotals.etb.withdrawals))))
            .append($('<td>').text(formatAmount(etbBalance))));
        
        summarySection.append(table);
        $('#report-container').append(summarySection);

        // Check if this is the last year being processed
        var fromYear = $('#from-year').val();
        var toYear = $('#to-year').val();
        if (year == toYear) {
            // Add instructions section only after the last year's data
            addInstructionsSection();
        }
    }
    
    function addInstructionsSection() {
        var instructionsSection = $('<div>').addClass('year-section').attr('id', 'instructions-section');
        instructionsSection.append($('<h4>').text('Instructions & Notes'));

        var content = $('<div>').addClass('instructions-content');
        
        // Report Generation
        content.append($('<h5>').text('Report Generation'));
        var genList = $('<ul>');
        genList.append($('<li>').text('Select one or more USD and/or ETB accounts from the checkboxes at the top.'));
        genList.append($('<li>').text('Choose a date range using the "From Year" and "To Year" dropdowns.'));
        genList.append($('<li>').text('Click "Generate Report" to create the report.'));
        genList.append($('<li>').text('Use the "Print Report" button to print or save as PDF.'));
        content.append(genList);

        // Calculations Explanation
        content.append($('<h5>').text('Understanding the Calculations'));
        var calcList = $('<ul>');
        calcList.append($('<li>').text('Monthly Deposits: Sum of all deposit transactions for that month.'));
        calcList.append($('<li>').text('Monthly Withdrawals: Sum of all withdrawal transactions for that month (shown as positive numbers).'));
        calcList.append($('<li>').text('Monthly Balance: Running balance that accumulates from the start of the year.'));
        calcList.append($('<li>').text('Year Total: Sum of all transactions for the entire year.'));
        calcList.append($('<li>').text('Overall Summary: Combined totals across all selected years and accounts.'));
        content.append(calcList);

        // Report Structure
        content.append($('<h5>').text('Report Structure'));
        var structList = $('<ul>');
        structList.append($('<li>').text('Year Sections: Each year\'s data is shown in a separate section.'));
        structList.append($('<li>').text('Currency Sections: USD and ETB transactions are displayed separately.'));
        structList.append($('<li>').text('Monthly Breakdown: Shows transactions for each month of the year.'));
        structList.append($('<li>').text('Account Details: Each account shows deposits, withdrawals, and running balance.'));
        structList.append($('<li>').text('Overall Summary: Total figures for the entire selected period.'));
        content.append(structList);

        // Important Notes
        content.append($('<h5>').text('Important Notes'));
        var notesList = $('<ul>');
        notesList.append($('<li>').text('All amounts are shown in their respective currencies (USD or ETB).'));
        notesList.append($('<li>').text('Withdrawals are stored as negative numbers but displayed as positive for readability.'));
        notesList.append($('<li>').text('Running balances include all transactions from the start of the year.'));
        notesList.append($('<li>').text('The report only includes transactions marked as active in the system.'));
        notesList.append($('<li>').text('Transactions are grouped by month based on their transaction date.'));
        content.append(notesList);

        // Print Instructions
        content.append($('<h5>').text('Printing Tips'));
        var printList = $('<ul>');
        printList.append($('<li>').text('Use the "Print Report" button for best formatting.'));
        printList.append($('<li>').text('The print version automatically hides the selection controls.'));
        printList.append($('<li>').text('Page breaks are optimized to keep related data together.'));
        printList.append($('<li>').text('Landscape orientation is recommended for better readability.'));
        content.append(printList);

        instructionsSection.append(content);
        $('#report-container').append(instructionsSection);

        // Add some styling
        $('.instructions-content h5').css({
            'margin-top': '15px',
            'margin-bottom': '10px',
            'color': '#2c5282'
        });

        $('.instructions-content ul').css({
            'margin-bottom': '15px',
            'padding-left': '20px'
        });

        $('.instructions-content li').css({
            'margin-bottom': '5px',
            'line-height': '1.4'
        });
    }
    
    function createCurrencyTable(currency, year, accounts) {
        var table = $('<table>').addClass('table table-bordered currency-table');
        var thead = $('<thead>').appendTo(table);
        var tbody = $('<tbody>').appendTo(table);
        
        // Header row
        var headerRow = $('<tr>');
        headerRow.append($('<th>').text('Account'));
        headerRow.append($('<th>').text('Platform'));
        for (var i = 1; i <= 12; i++) {
            headerRow.append($('<th>').text(getMonthName(i)));
        }
        headerRow.append($('<th>').text('Total'));
        thead.append(headerRow);
        
        // Data rows for each account
        Object.keys(accounts).forEach(function(accountId) {
            var account = accounts[accountId];
            
            // Deposits row
            var depositRow = $('<tr>');
            depositRow.append($('<td>').text(account.account_owner));
            depositRow.append($('<td>').text(account.account_platform + ' (Deposits)'));
            
            var totalDeposits = 0;
            for (var month = 1; month <= 12; month++) {
                var deposits = account.months[month].deposits;
                totalDeposits += deposits;
                depositRow.append($('<td>').text(formatAmount(deposits)));
            }
            depositRow.append($('<td>').text(formatAmount(totalDeposits)));
            tbody.append(depositRow);
            
            // Withdrawals row
            var withdrawRow = $('<tr>');
            withdrawRow.append($('<td>').text(account.account_owner));
            withdrawRow.append($('<td>').text(account.account_platform + ' (Withdrawals)'));
            
            var totalWithdrawals = 0;
            for (var month = 1; month <= 12; month++) {
                var withdrawals = account.months[month].withdrawals;
                totalWithdrawals += withdrawals;
                withdrawRow.append($('<td>').text(formatAmount(Math.abs(withdrawals))));
            }
            withdrawRow.append($('<td>').text(formatAmount(Math.abs(totalWithdrawals))));
            tbody.append(withdrawRow);
            
            // Balance row
            var balanceRow = $('<tr>').addClass('table-info');
            balanceRow.append($('<td>').text(account.account_owner));
            balanceRow.append($('<td>').text(account.account_platform + ' (Balance)'));
            
            for (var month = 1; month <= 12; month++) {
                balanceRow.append($('<td>').text(formatAmount(account.months[month].balance)));
            }
            balanceRow.append($('<td>').text(formatAmount(account.months[12].balance)));
            tbody.append(balanceRow);
        });
        
        return table;
    }
    
    function getMonthName(month) {
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[month - 1];
    }
    
    function formatAmount(amount) {
        return amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
}); 