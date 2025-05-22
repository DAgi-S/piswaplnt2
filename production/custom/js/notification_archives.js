$(document).ready(function() {
    let notificationTypeChart = null;
    let deliverySuccessChart = null;

    // Initialize DataTable
    const archivesTable = $('#archivesTable').DataTable({
        ajax: {
            url: 'php_action/fetchArchives.php',
            data: function(d) {
                const filters = $('#archiveFilterForm').serializeArray();
                filters.forEach(function(filter) {
                    d[filter.name] = filter.value;
                });
            }
        },
        columns: [
            { data: 'archive_id' },
            { data: 'type' },
            { data: 'title' },
            { data: 'username' },
            { data: 'channel' },
            { 
                data: 'status',
                render: function(data) {
                    const statusClasses = {
                        'delivered': 'label-success',
                        'failed': 'label-danger',
                        'expired': 'label-warning'
                    };
                    return `<span class="label ${statusClasses[data]}">${data}</span>`;
                }
            },
            { 
                data: 'created_at',
                render: function(data) {
                    return moment(data).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            { 
                data: 'delivered_at',
                render: function(data) {
                    return data ? moment(data).format('YYYY-MM-DD HH:mm:ss') : '-';
                }
            },
            {
                data: null,
                render: function(data) {
                    return `
                        <button class="btn btn-xs btn-info viewBtn" data-id="${data.archive_id}">
                            <i class="fa fa-eye"></i>
                        </button>
                        <button class="btn btn-xs btn-danger deleteBtn" data-id="${data.archive_id}">
                            <i class="fa fa-trash"></i>
                        </button>`;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true
    });

    // Handle filter form submission
    $('#archiveFilterForm').on('submit', function(e) {
        e.preventDefault();
        archivesTable.ajax.reload();
        updateStatistics();
        updateCharts();
    });

    // Export button click
    $('#exportBtn').on('click', function() {
        const filters = $('#archiveFilterForm').serialize();
        window.location.href = `php_action/exportArchives.php?${filters}`;
    });

    // Cleanup button click
    $('#cleanupBtn').on('click', function() {
        if (confirm('Are you sure you want to clean up old archives? This cannot be undone.')) {
            $.ajax({
                url: 'php_action/cleanupArchives.php',
                type: 'POST',
                success: function(response) {
                    if (response.success) {
                        toastr.success('Archives cleaned up successfully');
                        archivesTable.ajax.reload();
                        updateStatistics();
                        updateCharts();
                    } else {
                        toastr.error(response.message || 'Failed to clean up archives');
                    }
                }
            });
        }
    });

    // View button click
    $('#archivesTable').on('click', '.viewBtn', function() {
        const archiveId = $(this).data('id');
        $.ajax({
            url: 'php_action/getArchiveDetails.php',
            type: 'GET',
            data: { archive_id: archiveId },
            success: function(response) {
                if (response.success) {
                    const details = response.data;
                    let html = `
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Title</th>
                                    <td>${details.title}</td>
                                </tr>
                                <tr>
                                    <th>Message</th>
                                    <td>${details.message}</td>
                                </tr>
                                <tr>
                                    <th>User</th>
                                    <td>${details.username} (${details.email})</td>
                                </tr>
                                <tr>
                                    <th>Type</th>
                                    <td>${details.type}</td>
                                </tr>
                                <tr>
                                    <th>Priority</th>
                                    <td>${details.priority}</td>
                                </tr>
                                <tr>
                                    <th>Channel</th>
                                    <td>${details.channel}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>${details.status}</td>
                                </tr>
                                <tr>
                                    <th>Created</th>
                                    <td>${moment(details.created_at).format('YYYY-MM-DD HH:mm:ss')}</td>
                                </tr>
                                <tr>
                                    <th>Delivered</th>
                                    <td>${details.delivered_at ? moment(details.delivered_at).format('YYYY-MM-DD HH:mm:ss') : '-'}</td>
                                </tr>
                            </table>
                        </div>
                        ${details.metadata ? `
                            <h4>Additional Information</h4>
                            <pre>${JSON.stringify(JSON.parse(details.metadata), null, 2)}</pre>
                        ` : ''}
                    `;
                    $('#notificationDetails').html(html);
                    $('#detailsModal').modal('show');
                }
            }
        });
    });

    // Delete button click
    $('#archivesTable').on('click', '.deleteBtn', function() {
        const archiveId = $(this).data('id');
        if (confirm('Are you sure you want to delete this archive?')) {
            $.ajax({
                url: 'php_action/deleteArchive.php',
                type: 'POST',
                data: { archive_id: archiveId },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Archive deleted successfully');
                        archivesTable.ajax.reload();
                        updateStatistics();
                        updateCharts();
                    } else {
                        toastr.error(response.message || 'Failed to delete archive');
                    }
                }
            });
        }
    });

    // Update statistics
    function updateStatistics() {
        const filters = $('#archiveFilterForm').serialize();
        $.ajax({
            url: 'php_action/getArchiveStatistics.php',
            type: 'GET',
            data: filters,
            success: function(response) {
                if (response.success) {
                    $('#totalNotifications').text(response.stats.total_notifications);
                    $('#deliveredCount').text(response.stats.delivered);
                    $('#failedCount').text(response.stats.failed);
                    $('#uniqueUsers').text(response.stats.unique_users);
                }
            }
        });
    }

    // Update charts
    function updateCharts() {
        const filters = $('#archiveFilterForm').serialize();
        $.ajax({
            url: 'php_action/getArchiveAnalytics.php',
            type: 'GET',
            data: filters,
            success: function(response) {
                if (response.success) {
                    updateNotificationTypeChart(response.typeData);
                    updateDeliverySuccessChart(response.deliveryData);
                }
            }
        });
    }

    // Initialize notification type chart
    function updateNotificationTypeChart(data) {
        const ctx = document.getElementById('notificationTypeChart').getContext('2d');
        
        if (notificationTypeChart) {
            notificationTypeChart.destroy();
        }

        notificationTypeChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: [
                        '#3c8dbc',
                        '#00a65a',
                        '#f39c12',
                        '#dd4b39',
                        '#00c0ef',
                        '#3d9970'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Initialize delivery success chart
    function updateDeliverySuccessChart(data) {
        const ctx = document.getElementById('deliverySuccessChart').getContext('2d');
        
        if (deliverySuccessChart) {
            deliverySuccessChart.destroy();
        }

        deliverySuccessChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Success Rate (%)',
                    data: data.values,
                    backgroundColor: '#00a65a'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }

    // Initial load
    updateStatistics();
    updateCharts();
}); 