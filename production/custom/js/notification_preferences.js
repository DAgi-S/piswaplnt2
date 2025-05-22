$(document).ready(function() {
    // Load user preferences
    loadUserPreferences();

    // Handle form submission
    $('#notificationPreferencesForm').on('submit', function(e) {
        e.preventDefault();
        savePreferences();
    });

    // Load user's current preferences
    function loadUserPreferences() {
        $.ajax({
            url: 'php_action/getUserPreferences.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const prefs = response.preferences;
                    
                    // Set channels
                    if (prefs.channels) {
                        Object.keys(prefs.channels).forEach(channel => {
                            $(`input[name="channels[${channel}]"]`).prop('checked', prefs.channels[channel]);
                        });
                    }

                    // Set notification types
                    if (prefs.types) {
                        Object.keys(prefs.types).forEach(type => {
                            $(`input[name="types[${type}]"]`).prop('checked', prefs.types[type]);
                        });
                    }

                    // Set quiet hours
                    if (prefs.quiet_hours) {
                        $('input[name="quiet_hours_start"]').val(prefs.quiet_hours.start);
                        $('input[name="quiet_hours_end"]').val(prefs.quiet_hours.end);
                    }
                } else {
                    toastr.error('Failed to load preferences');
                }
            },
            error: function() {
                toastr.error('Failed to load preferences');
            }
        });
    }

    // Save preferences
    function savePreferences() {
        const formData = $('#notificationPreferencesForm').serializeArray();
        const preferences = {
            channels: {
                email: false,
                browser: false,
                sms: false
            },
            types: {
                inventory_low: false,
                restock: false,
                order_new: false,
                order_status: false,
                system_maintenance: false,
                system_updates: false,
                security_login: false,
                security_changes: false
            },
            quiet_hours: {
                start: '',
                end: ''
            }
        };

        // Process form data
        formData.forEach(item => {
            if (item.name.startsWith('channels[')) {
                const channel = item.name.match(/channels\[(.*?)\]/)[1];
                preferences.channels[channel] = true;
            } else if (item.name.startsWith('types[')) {
                const type = item.name.match(/types\[(.*?)\]/)[1];
                preferences.types[type] = true;
            } else if (item.name === 'quiet_hours_start') {
                preferences.quiet_hours.start = item.value;
            } else if (item.name === 'quiet_hours_end') {
                preferences.quiet_hours.end = item.value;
            }
        });

        // Save preferences
        $.ajax({
            url: 'php_action/savePreferences.php',
            type: 'POST',
            data: JSON.stringify(preferences),
            contentType: 'application/json',
            success: function(response) {
                if (response.success) {
                    toastr.success('Preferences saved successfully');
                    // Reload preferences to ensure UI is in sync with server
                    loadUserPreferences();
                } else {
                    toastr.error(response.message || 'Failed to save preferences');
                    console.error('Save preferences error:', response);
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Failed to save preferences');
                console.error('Save preferences error:', error);
                console.error('Server response:', xhr.responseText);
            }
        });
    }

    // Preview button click handler
    $('#previewBtn').on('click', function() {
        updatePreview();
        $('#previewModal').modal('show');
    });

    // Preview type change handler
    $('#previewType, #previewPriority').on('change', function() {
        updatePreview();
    });

    // Update preview content
    function updatePreview() {
        const type = $('#previewType').val();
        const priority = $('#previewPriority').val();
        const previewContent = getPreviewContent(type, priority);

        // Update in-app preview
        $('#inAppPreview .notification-title').text(previewContent.title);
        $('#inAppPreview .notification-message').text(previewContent.message);
        $('#inAppPreview .notification-icon i').attr('class', `fa ${previewContent.icon}`);

        // Update email preview
        $('.email-subject').text(previewContent.title);
        $('.email-content').html(`
            <p>Hello,</p>
            <p>${previewContent.message}</p>
            <p>Click here to view more details.</p>
        `);

        // Update SMS preview
        $('.sms-content').text(`${previewContent.title}: ${previewContent.shortMessage}`);

        // Update browser preview
        $('.browser-title').text(previewContent.title);
        $('.browser-message').text(previewContent.shortMessage);
        $('.browser-icon i').attr('class', `fa ${previewContent.icon}`);
    }

    // Get preview content based on type and priority
    function getPreviewContent(type, priority) {
        const content = {
            inventory_low: {
                title: 'Low Stock Alert',
                message: 'Printer Paper (SKU: PP-001) has fallen below the minimum threshold. Current stock: 45 units (Minimum: 50 units)',
                shortMessage: 'Printer Paper stock low: 45 units',
                icon: 'fa-exclamation-triangle'
            },
            order_new: {
                title: 'New Order Received',
                message: 'Order #ORD-2024-001 has been received from Customer ABC. Total amount: $1,500.00',
                shortMessage: 'New order #ORD-2024-001 received',
                icon: 'fa-shopping-cart'
            },
            system_maintenance: {
                title: 'Scheduled Maintenance',
                message: 'System maintenance is scheduled for tomorrow at 02:00 AM. Expected duration: 2 hours',
                shortMessage: 'System maintenance tomorrow 02:00 AM',
                icon: 'fa-wrench'
            },
            security_login: {
                title: 'Security Alert',
                message: 'Multiple failed login attempts detected from IP: 192.168.1.100. Account has been temporarily locked.',
                shortMessage: 'Security alert: Failed login attempts',
                icon: 'fa-shield-alt'
            }
        };

        // Add priority indicator to title
        if (priority === 'high' || priority === 'critical') {
            content[type].title = `[${priority.toUpperCase()}] ${content[type].title}`;
        }

        return content[type];
    }

    // Request browser notification permission if needed
    if ('Notification' in window) {
        $('input[name="channels[browser]"]').on('change', function() {
            if (this.checked && Notification.permission !== 'granted') {
                Notification.requestPermission().then(function(permission) {
                    if (permission !== 'granted') {
                        toastr.warning('Browser notifications permission denied');
                        $('input[name="channels[browser]"]').prop('checked', false);
                    }
                });
            }
        });
    } else {
        $('input[name="channels[browser]"]').prop('disabled', true);
        $('input[name="channels[browser]"]').closest('.form-group').append(
            '<small class="text-muted">Browser notifications not supported</small>'
        );
    }

    // Test browser notification
    $('#browserPreview').on('click', '.browser-notification', function() {
        if (Notification.permission === 'granted') {
            const type = $('#previewType').val();
            const content = getPreviewContent(type, $('#previewPriority').val());
            
            const notification = new Notification(content.title, {
                body: content.shortMessage,
                icon: '/favicon.ico'
            });

            // Close notification after 5 seconds
            setTimeout(() => notification.close(), 5000);
        }
    });

    // Test email notification
    $('#testEmailBtn').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.text();
        
        // Check if email notifications are enabled
        if (!$('input[name="channels[email]"]').is(':checked')) {
            toastr.warning('Please enable email notifications first');
            return;
        }

        $btn.prop('disabled', true).text('Sending...');

        $.ajax({
            url: 'php_action/testEmail.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.messages || 'Test email sent successfully');
                } else {
                    toastr.error(response.messages || 'Failed to send test email');
                    console.error('Email Error:', response.debug);
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Failed to send test email: ' + error);
                console.error('Ajax Error:', error);
            },
            complete: function() {
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Initialize SSE connection
    let eventSource = null;
    let reconnectAttempts = 0;
    const maxReconnectAttempts = 5;
    const baseReconnectDelay = 3000;

    function connectSSE() {
        if (eventSource) {
            eventSource.close();
        }

        eventSource = new EventSource('php_action/notification_stream.php');

        eventSource.onopen = function() {
            console.log('SSE connection established');
            reconnectAttempts = 0;
        };

        eventSource.onerror = function(e) {
            console.error('SSE connection error:', e);
            eventSource.close();
            
            if (reconnectAttempts < maxReconnectAttempts) {
                const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
                reconnectAttempts++;
                
                console.log(`Reconnecting in ${delay}ms (attempt ${reconnectAttempts}/${maxReconnectAttempts})`);
                setTimeout(connectSSE, delay);
            } else {
                console.error('Max reconnection attempts reached');
                toastr.error('Failed to maintain notification connection. Please refresh the page.');
            }
        };

        eventSource.addEventListener('count', function(e) {
            try {
                const data = JSON.parse(e.data);
                updateUnreadCount(data.unread);
            } catch (error) {
                console.error('Error parsing count event:', error);
            }
        });

        eventSource.addEventListener('notification', function(e) {
            try {
                const notification = JSON.parse(e.data);
                handleNewNotification(notification);
            } catch (error) {
                console.error('Error parsing notification event:', error);
            }
        });

        eventSource.addEventListener('close', function(e) {
            console.log('Server requested connection close');
            eventSource.close();
            setTimeout(connectSSE, baseReconnectDelay);
        });
    }

    function updateUnreadCount(count) {
        // Update UI with new unread count
        $('.notification-badge').text(count);
        if (count > 0) {
            $('.notification-badge').show();
        } else {
            $('.notification-badge').hide();
        }
    }

    function handleNewNotification(notification) {
        // Handle new notification based on user preferences
        const channels = {
            browser: $('input[name="channels[browser]"]').is(':checked'),
            email: $('input[name="channels[email]"]').is(':checked'),
            sms: $('input[name="channels[sms]"]').is(':checked')
        };

        // Check quiet hours
        const now = new Date();
        const start = $('input[name="quiet_hours_start"]').val();
        const end = $('input[name="quiet_hours_end"]').val();
        
        if (start && end) {
            const [startHour, startMin] = start.split(':');
            const [endHour, endMin] = end.split(':');
            const startTime = new Date(now).setHours(startHour, startMin, 0);
            const endTime = new Date(now).setHours(endHour, endMin, 0);
            
            if (now >= startTime && now <= endTime) {
                console.log('Notification received during quiet hours');
                return;
            }
        }

        // Show browser notification if enabled
        if (channels.browser && Notification.permission === 'granted') {
            const browserNotification = new Notification(notification.title, {
                body: notification.message,
                icon: '/favicon.ico'
            });
            setTimeout(() => browserNotification.close(), 5000);
        }

        // Show in-app notification
        toastr.info(notification.message, notification.title, {
            timeOut: 5000,
            closeButton: true,
            progressBar: true
        });
    }

    // Start SSE connection
    connectSSE();

    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        if (eventSource) {
            eventSource.close();
        }
    });
}); 