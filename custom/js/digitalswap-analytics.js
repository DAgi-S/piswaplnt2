// Update summary cards
async function updateSummaryCards() {
    try {
        const response = await fetch('php_action/getDigitalSwapReport.php');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        console.log('Summary data:', data);
        
        // Process ETB and USD data
        data.forEach(currencyData => {
            if (currencyData.currency === 'ETB') {
                // Update ETB summary cards with animation
                animateValue('etbBalance', 0, currencyData.current_balance, 1000, 'ETB');
                animateValue('etbDeposits', 0, currencyData.total_deposits, 1000, 'ETB');
                animateValue('etbWithdraws', 0, currencyData.total_withdrawals, 1000, 'ETB');
            } else if (currencyData.currency === 'USD') {
                // Update USD summary cards with animation
                animateValue('usdBalance', 0, currencyData.current_balance, 1000, 'USD');
                animateValue('usdDeposits', 0, currencyData.total_deposits, 1000, 'USD');
                animateValue('usdWithdraws', 0, currencyData.total_withdrawals, 1000, 'USD');
            }
        });
    } catch (error) {
        console.error('Error updating summary cards:', error);
        // Show error in the UI
        ['etbBalance', 'etbDeposits', 'etbWithdraws', 'usdBalance', 'usdDeposits', 'usdWithdraws'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = 'Error loading data';
                element.style.color = '#e74c3c';
            }
        });
    }
}

// Animate value change
function animateValue(elementId, start, end, duration, currency) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const startTime = performance.now();
    const startValue = start;
    const change = end - start;

    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);

        // Easing function for smooth animation
        const easeOutQuad = 1 - Math.pow(1 - progress, 2);
        const currentValue = startValue + (change * easeOutQuad);

        // Format with thousand separators and 2 decimal places
        const formattedValue = currentValue.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // Add color based on value
        const colorClass = currentValue >= 0 ? 'text-success' : 'text-danger';
        element.className = colorClass;
        element.textContent = `${currency} ${formattedValue}`;

        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }

    requestAnimationFrame(update);
}

// Initialize everything when the document is ready
document.addEventListener('DOMContentLoaded', () => {
    updateSummaryCards();
});

$(document).ready(function() {
    // Function to format numbers with commas and 2 decimal places
    function formatNumber(number) {
        return parseFloat(number || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Function to animate value changes
    function animateValue(elementId, startValue, endValue, currency) {
        const element = $(`#${elementId}`);
        if (!element.length) return;

        const duration = 1000;
        const start = startValue;
        const end = endValue;
        const range = end - start;
        
        let current = start;
        const increment = range / (duration / 16); // 60fps
        
        const animate = () => {
            current += increment;
            if ((increment >= 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                element.text(`${currency} ${formatNumber(current)}`);
                element.parent().parent().removeClass('loading');
                element.addClass(current >= 0 ? 'text-success' : 'text-danger');
                return;
            }
            
            element.text(`${currency} ${formatNumber(current)}`);
            requestAnimationFrame(animate);
        };
        
        animate();
    }

    // Function to set loading state
    function setLoadingState(currency) {
        const prefix = currency.toLowerCase();
        $(`.${prefix}-section .summary-card`).addClass('loading');
        
        // Set loading indicators
        const elements = [
            'Balance', 'Deposits', 'Withdraws',
            'TodayDeposits', 'TodayWithdraws', 'TodayTransactions',
            'MonthDeposits', 'MonthWithdraws'
        ];
        
        elements.forEach(elem => {
            $(`#${prefix}${elem}`).html('<span class="loading-text">Loading...</span>');
        });

        // Set loading for metrics
        ['AvgTransaction', 'LargestTransaction', 'MostActivePlatform', 'MostActiveOwner'].forEach(metric => {
            $(`#${prefix}${metric}`).html('<span class="loading-text">Loading...</span>');
        });
    }

    // Function to update metrics with animation
    function updateMetrics(currency, data) {
        const prefix = currency.toLowerCase();
        
        // Animate main values
        animateValue(`${prefix}Balance`, 0, data.balance, currency);
        animateValue(`${prefix}Deposits`, 0, data.deposits, currency);
        animateValue(`${prefix}Withdraws`, 0, data.withdrawals, currency);
        
        // Animate today's values
        animateValue(`${prefix}TodayDeposits`, 0, data.today.deposits, currency);
        animateValue(`${prefix}TodayWithdraws`, 0, data.today.withdrawals, currency);
        $(`#${prefix}TodayTransactions`).text(data.today.transactions || 0)
            .parent().parent().removeClass('loading');
        
        // Animate monthly values
        animateValue(`${prefix}MonthDeposits`, 0, data.this_month.deposits, currency);
        animateValue(`${prefix}MonthWithdraws`, 0, data.this_month.withdrawals, currency);
        
        // Update metrics
        const metricsContainer = $(`.${prefix}-section .metrics-strip`);
        $(`#${prefix}AvgTransaction`).text(`${currency} ${formatNumber(data.metrics.avg_transaction)}`);
        $(`#${prefix}LargestTransaction`).text(`${currency} ${formatNumber(data.metrics.largest_transaction)}`);
        $(`#${prefix}MostActivePlatform`).text(data.metrics.most_active_platform || 'N/A');
        $(`#${prefix}MostActiveOwner`).text(data.metrics.most_active_owner || 'N/A');
        metricsContainer.find('.metric-item').removeClass('loading');
    }

    // Function to set error state
    function setErrorState(currency, message = 'Error loading data') {
        const prefix = currency.toLowerCase();
        $(`.${prefix}-section .summary-card`).removeClass('loading').addClass('error-state');
        
        const elements = [
            'Balance', 'Deposits', 'Withdraws',
            'TodayDeposits', 'TodayWithdraws', 'TodayTransactions',
            'MonthDeposits', 'MonthWithdraws',
            'AvgTransaction', 'LargestTransaction', 'MostActivePlatform', 'MostActiveOwner'
        ];
        
        elements.forEach(elem => {
            $(`#${prefix}${elem}`).html(`<span class="error-text">${message}</span>`);
        });
    }

    // Function to update ETB metrics
    function updateETBMetrics() {
        setLoadingState('ETB');
        $.ajax({
            url: 'php_action/fetchETBSummary.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateMetrics('ETB', response);
                } else {
                    setErrorState('ETB', response.message);
                    console.error('ETB Summary Error:', response.message);
                }
            },
            error: function(xhr, status, error) {
                setErrorState('ETB', 'Failed to load data');
                console.error('ETB Summary AJAX Error:', error);
            }
        });
    }

    // Function to update USD metrics
    function updateUSDMetrics() {
        setLoadingState('USD');
        $.ajax({
            url: 'php_action/fetchUSDSummary.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    updateMetrics('USD', response);
                } else {
                    setErrorState('USD', response.message);
                    console.error('USD Summary Error:', response.message);
                }
            },
            error: function(xhr, status, error) {
                setErrorState('USD', 'Failed to load data');
                console.error('USD Summary AJAX Error:', error);
            }
        });
    }

    // Add these styles dynamically
    $('<style>')
        .text(`
            .loading-text {
                color: #666;
                font-style: italic;
            }
            .error-text {
                color: #e74c3c;
                font-style: italic;
            }
            .loading {
                position: relative;
                opacity: 0.7;
            }
            .loading::after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 20px;
                height: 20px;
                border: 2px solid #f3f3f3;
                border-top: 2px solid #3498db;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: translate(-50%, -50%) rotate(0deg); }
                100% { transform: translate(-50%, -50%) rotate(360deg); }
            }
        `)
        .appendTo('head');

    // Initial load
    updateETBMetrics();
    updateUSDMetrics();

    // Refresh metrics every 60 seconds
    setInterval(function() {
        updateETBMetrics();
        updateUSDMetrics();
    }, 60000);
}); 