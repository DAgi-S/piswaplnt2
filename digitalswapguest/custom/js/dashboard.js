$(document).ready(function() {
    let transactionChart = null;

    // Function to format currency with safety checks
    function formatCurrency(amount, currency = 'ETB') {
        const numAmount = parseFloat(amount);
        if (isNaN(numAmount)) return currency + ' 0.00';
        return currency + ' ' + numAmount.toFixed(2);
    }

    // Function to format date with safety checks
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return '';
        return date.toLocaleDateString('en-US', { 
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    // Function to handle custom date range visibility
    function toggleCustomDateRange() {
        const timeFrame = $('#timeFrameSelect').val();
        $('#customDateRange').toggle(timeFrame === 'custom');
    }

    // Function to update transaction chart
    function updateTransactionChart(monthlyData) {
        const ctx = document.getElementById('transactionChart');
        if (!ctx) {
            console.error('Chart canvas element not found');
            return;
        }

        try {
            if (transactionChart) {
                transactionChart.destroy();
            }

            if (!monthlyData || monthlyData.length === 0) {
                console.log('No monthly data available for chart');
                return;
            }

            const labels = monthlyData.map(data => data.month);
            const deposits = monthlyData.map(data => data.deposits);
            const withdrawals = monthlyData.map(data => data.withdrawals);
            const netChanges = monthlyData.map(data => data.net_change);

            transactionChart = new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Deposits',
                            data: deposits,
                            backgroundColor: 'rgba(40, 167, 69, 0.5)',
                            borderColor: 'rgb(40, 167, 69)',
                            borderWidth: 1
                        },
                        {
                            label: 'Withdrawals',
                            data: withdrawals,
                            backgroundColor: 'rgba(255, 193, 7, 0.5)',
                            borderColor: 'rgb(255, 193, 7)',
                            borderWidth: 1
                        },
                        {
                            label: 'Net Change',
                            data: netChanges,
                            type: 'line',
                            fill: false,
                            borderColor: 'rgb(0, 123, 255)',
                            tension: 0.1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        title: {
                            display: true,
                            text: 'Transaction History'
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error updating chart:', error);
        }
    }

    // Function to update summary cards
    function updateSummaryCards(summary) {
        try {
            $('#current-balance').text(summary.current_balance || '0.00');
            $('#recent-deposits').text(summary.recent_deposits || '0.00');
            $('#recent-withdrawals').text(summary.recent_withdrawals || '0.00');
            $('#total-transactions').text(summary.total_transactions || '0');
        } catch (error) {
            console.error('Error updating summary cards:', error);
        }
    }

    // Function to update account balances table
    function updateAccountBalances(accounts) {
        try {
            if (!accounts || accounts.length === 0) {
                $('#account-balances').html('<tr><td colspan="4" class="text-center">No account data available</td></tr>');
                return;
            }

            const accountBalancesHtml = accounts.map(account => `
                <tr>
                    <td>${account.account_owner || ''}</td>
                    <td>${account.account_platform || ''}</td>
                    <td>${account.currency || ''}</td>
                    <td class="text-end">${account.formatted_balance || '0.00'}</td>
                </tr>
            `).join('');
            $('#account-balances').html(accountBalancesHtml);
        } catch (error) {
            console.error('Error updating account balances:', error);
        }
    }

    // Function to update recent transactions
    function updateRecentTransactions(transactions, currency) {
        try {
            if (!transactions || transactions.length === 0) {
                $('#recent-transactions-table').html('<tr><td colspan="3" class="text-center">No recent transactions</td></tr>');
                return;
            }

            const recentTransactionsHtml = transactions.map(tx => `
                <tr>
                    <td>${formatDate(tx.date)}</td>
                    <td>${tx.type || ''}</td>
                    <td class="text-end">${formatCurrency(tx.amount, currency)}</td>
                </tr>
            `).join('');
            $('#recent-transactions-table').html(recentTransactionsHtml);
        } catch (error) {
            console.error('Error updating recent transactions:', error);
        }
    }

    // Function to fetch dashboard data
    function fetchDashboardData(startDate = null, endDate = null) {
        let url = 'php_action/fetchDashboardAnalytics.php';
        if (startDate && endDate) {
            url += `?startDate=${startDate}&endDate=${endDate}`;
        }

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Dashboard data received:', response);

                if (response.error) {
                    console.error('Server error:', response.message);
                    return;
                }

                if (!response.has_accounts) {
                    $('#statsSection').hide();
                    $('#noAccountsMessage').show();
                    return;
                }

                $('#statsSection').show();
                $('#noAccountsMessage').hide();

                // Update all dashboard components
                updateSummaryCards(response.summary);
                updateAccountBalances(response.account_balances);
                updateRecentTransactions(response.recent_transactions, response.currency);
                updateTransactionChart(response.monthly_data);
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                if (xhr.status === 401) {
                    window.location.href = 'index.php';
                }
            }
        });
    }

    // Handle date range changes
    $('#timeFrameSelect').on('change', function() {
        const timeFrame = $(this).val();
        let startDate, endDate;
        const today = new Date();
        
        switch(timeFrame) {
            case '6':
                startDate = new Date(today.setMonth(today.getMonth() - 6));
                endDate = new Date();
                break;
            case '12':
                startDate = new Date(today.setMonth(today.getMonth() - 12));
                endDate = new Date();
                break;
            case '24':
                startDate = new Date(today.setMonth(today.getMonth() - 24));
                endDate = new Date();
                break;
            case 'thisYear':
                startDate = new Date(today.getFullYear(), 0, 1);
                endDate = new Date();
                break;
            case 'lastYear':
                startDate = new Date(today.getFullYear() - 1, 0, 1);
                endDate = new Date(today.getFullYear() - 1, 11, 31);
                break;
            case 'custom':
                return; // Don't fetch data for custom range until dates are selected
        }

        if (timeFrame !== 'custom') {
            fetchDashboardData(
                startDate.toISOString().split('T')[0],
                endDate.toISOString().split('T')[0]
            );
        }
    });

    // Handle custom date range changes
    $('#startDate, #endDate').on('change', function() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        if (startDate && endDate) {
            fetchDashboardData(startDate, endDate);
        }
    });

    // Initialize event handlers
    $('#timeFrameSelect').on('change', toggleCustomDateRange);
    toggleCustomDateRange();

    // Initial data fetch
    fetchDashboardData();
}); 