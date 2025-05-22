$(document).ready(function() {
    // Initialize DataTable
    const queueTable = $('#queueTable').DataTable({
        ajax: {
            url: 'php_action/fetchQueueItems.php',
            dataSrc: 'data'
        },
        columns: [
            { data: 'queue_id' },
            { data: 'type' },
            { data: 'user_name' },
            { data: 'channel' },
            { 
                data: 'status',
                render: function(data) {
                    const statusClasses = {
                        'pending': 'label-primary',
                        'processing': 'label-warning',
                        'completed': 'label-success',
                        'failed': 'label-danger'
                    };
                    return `<span class="label ${statusClasses[data]}">${data}</span>`;
                }
            },
            { data: 'attempts' },
            { 
                data: 'created_at',
                render: function(data) {
                    return moment(data).format('YYYY-MM-DD HH:mm:ss');
                }
            },
            { 
                data: 'last_attempt',
                render: function(data) {
                    return data ? moment(data).format('YYYY-MM-DD HH:mm:ss') : '-';
                }
            },
            {
                data: null,
                render: function(data) {
                    let buttons = `
                        <button class="btn btn-xs btn-info viewBtn" data-id="${data.queue_id}">
                            <i class="fa fa-eye"></i>
                        </button>`;
                    
                    if (data.status === 'failed') {
                        buttons += `
                            <button class="btn btn-xs btn-warning retryBtn" data-id="${data.queue_id}">
                                <i class="fa fa-refresh"></i>
                            </button>`;
                    }
                    
                    buttons += `
                        <button class="btn btn-xs btn-danger deleteBtn" data-id="${data.queue_id}">
                            <i class="fa fa-trash"></i>
                        </button>`;
                    
                    return buttons;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true
    });

    // Update statistics
    function updateStats() {
        $.ajax({
            url: 'php_action/getQueueStats.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    $('#pendingCount').text(response.stats.pending);
                    $('#processingCount').text(response.stats.processing);
                    $('#completedCount').text(response.stats.completed);
                    $('#failedCount').text(response.stats.failed);
                }
            }
        });
    }

    // Refresh data periodically
    setInterval(function() {
        queueTable.ajax.reload(null, false);
        updateStats();
    }, 30000); // Every 30 seconds

    // Initial stats update
    updateStats();

    // Process Queue Button
    $('#processQueueBtn').on('click', function() {
        $.ajax({
            url: 'php_action/triggerQueueProcess.php',
            type: 'POST',
            success: function(response) {
                if (response.success) {
                    toastr.success('Queue processing triggered');
                    setTimeout(function() {
                        queueTable.ajax.reload();
                        updateStats();
                    }, 2000);
                } else {
                    toastr.error(response.message || 'Failed to trigger queue processing');
                }
            }
        });
    });

    // Retry Failed Button
    $('#retryFailedBtn').on('click', function() {
        $.ajax({
            url: 'php_action/retryFailedNotifications.php',
            type: 'POST',
            success: function(response) {
                if (response.success) {
                    toastr.success('Failed notifications queued for retry');
                    queueTable.ajax.reload();
                    updateStats();
                } else {
                    toastr.error(response.message || 'Failed to retry notifications');
                }
            }
        });
    });

    // View Button Click
    $('#queueTable').on('click', '.viewBtn', function() {
        const queueId = $(this).data('id');
        $.ajax({
            url: 'php_action/getQueueItemDetails.php',
            type: 'GET',
            data: { queue_id: queueId },
            success: function(response) {
                if (response.success) {
                    $('#errorDetails').text(JSON.stringify(response.data, null, 2));
                    $('#errorModal').modal('show');
                }
            }
        });
    });

    // Retry Single Item
    $('#queueTable').on('click', '.retryBtn', function() {
        const queueId = $(this).data('id');
        $.ajax({
            url: 'php_action/retryNotification.php',
            type: 'POST',
            data: { queue_id: queueId },
            success: function(response) {
                if (response.success) {
                    toastr.success('Notification queued for retry');
                    queueTable.ajax.reload();
                    updateStats();
                } else {
                    toastr.error(response.message || 'Failed to retry notification');
                }
            }
        });
    });

    // Delete Item
    $('#queueTable').on('click', '.deleteBtn', function() {
        const queueId = $(this).data('id');
        if (confirm('Are you sure you want to delete this queue item?')) {
            $.ajax({
                url: 'php_action/deleteQueueItem.php',
                type: 'POST',
                data: { queue_id: queueId },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Queue item deleted');
                        queueTable.ajax.reload();
                        updateStats();
                    } else {
                        toastr.error(response.message || 'Failed to delete queue item');
                    }
                }
            });
        }
    });
}); 