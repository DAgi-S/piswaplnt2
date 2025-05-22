$(document).ready(function() {
    // Fetch data for stock movement chart
    $.ajax({
        url: 'php_action/fetchStockMovementTrends.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                createStockMovementChart(response.data);
            } else {
                console.error('Error fetching stock movement trends:', response.messages);
            }
        },
        error: function() {
            console.error('Error fetching stock movement trends');
        }
    });

    // Fetch data for stock status chart
    $.ajax({
        url: 'php_action/fetchStockStatusDistribution.php',
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                createStockStatusChart(response.data);
            } else {
                console.error('Error fetching stock status distribution:', response.messages);
            }
        },
        error: function() {
            console.error('Error fetching stock status distribution');
        }
    });
});

// Create Stock Movement Chart
function createStockMovementChart(data) {
    var ctx = document.getElementById('stockMovementChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: [
                {
                    label: 'Inbound',
                    data: data.inbound,
                    backgroundColor: '#5cb85c',
                    borderColor: '#5cb85c',
                    borderWidth: 1
                },
                {
                    label: 'Outbound',
                    data: data.outbound,
                    backgroundColor: '#f0ad4e',
                    borderColor: '#f0ad4e',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Movements'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'top'
                },
                title: {
                    display: false
                }
            }
        }
    });
}

// Create Stock Status Chart
function createStockStatusChart(data) {
    var ctx = document.getElementById('stockStatusChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Normal', 'Low Stock', 'Critical', 'Out of Stock'],
            datasets: [{
                data: [
                    data.normal,
                    data.low,
                    data.critical,
                    data.outOfStock
                ],
                backgroundColor: [
                    '#5cb85c',  // Normal - Green
                    '#f0ad4e',  // Low Stock - Yellow
                    '#d9534f',  // Critical - Red
                    '#292b2c'   // Out of Stock - Black
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                title: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            var label = context.label || '';
                            var value = context.raw || 0;
                            var total = context.dataset.data.reduce((a, b) => a + b, 0);
                            var percentage = Math.round((value / total) * 100);
                            return label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
} 