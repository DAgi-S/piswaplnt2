// Notification handling
const NotificationManager = {
    init: function() {
        this.notificationCount = 0;
        this.notificationList = $('#notificationList');
        this.notificationBadge = $('#notificationBadge');
        this.setupEventListeners();
        this.fetchNotifications();
        
        // Poll for new notifications every 30 seconds
        setInterval(() => this.fetchNotifications(), 30000);
    },

    setupEventListeners: function() {
        // Mark notification as read when clicked
        $(document).on('click', '.notification-item', function(e) {
            const notificationId = $(this).data('notification-id');
            NotificationManager.markAsRead(notificationId);
        });

        // Prevent dropdown from closing when clicking inside
        $('.notification-dropdown').on('click', function(e) {
            e.stopPropagation();
        });
    },

    fetchNotifications: function() {
        $.ajax({
            url: 'php_action/fetchNotifications.php',
            type: 'GET',
            dataType: 'json',
            data: {
                limit: 10,
                is_read: false
            },
            success: function(response) {
                if (response.success) {
                    NotificationManager.updateNotificationUI(response.notifications);
                } else {
                    console.error('Failed to fetch notifications:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching notifications:', error || 'Unknown error');
                // Don't show error toast as this is a background operation
            },
            complete: function() {
                // Ensure next poll is scheduled even if this one failed
                setTimeout(() => NotificationManager.fetchNotifications(), 30000);
            }
        });
    },

    markAsRead: function(notificationId) {
        $.ajax({
            url: 'php_action/markNotificationRead.php',
            type: 'POST',
            dataType: 'json',
            data: {
                notification_id: notificationId
            },
            success: function(response) {
                if (response.success) {
                    // Update UI to reflect read status
                    $(`[data-notification-id="${notificationId}"]`).removeClass('unread');
                    NotificationManager.updateNotificationCount(-1);
                } else {
                    console.error('Failed to mark notification as read:', response.message);
                    toastr.error('Failed to mark notification as read');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error marking notification as read:', error || 'Unknown error');
                toastr.error('Failed to mark notification as read');
            }
        });
    },

    updateNotificationUI: function(notifications) {
        this.notificationList.empty();
        let unreadCount = 0;

        notifications.forEach(notification => {
            if (!notification.is_read) {
                unreadCount++;
            }

            const notificationHtml = `
                <div class="notification-item ${notification.is_read ? '' : 'unread'}" 
                     data-notification-id="${notification.notification_id}">
                    <div class="notification-icon">
                        <i class="fa ${this.getIconClass(notification.type)}"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-time">
                            ${this.formatTime(notification.created_at)}
                        </div>
                    </div>
                </div>
            `;

            this.notificationList.append(notificationHtml);
        });

        this.updateNotificationCount(unreadCount);
    },

    updateNotificationCount: function(count) {
        if (typeof count === 'number') {
            this.notificationCount = count;
        }
        
        if (this.notificationCount > 0) {
            this.notificationBadge.text(this.notificationCount).show();
        } else {
            this.notificationBadge.hide();
        }
    },

    getIconClass: function(type) {
        const iconMap = {
            'inventory': 'fa-box',
            'order': 'fa-shopping-cart',
            'system': 'fa-cog',
            'default': 'fa-bell'
        };
        return iconMap[type] || iconMap.default;
    },

    formatTime: function(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // difference in seconds

        if (diff < 60) {
            return 'Just now';
        } else if (diff < 3600) {
            const minutes = Math.floor(diff / 60);
            return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
        } else if (diff < 86400) {
            const hours = Math.floor(diff / 3600);
            return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        } else {
            return date.toLocaleDateString();
        }
    }
};

// Initialize when document is ready
$(document).ready(function() {
    // Initialize notification system
    const NotificationSystem = {
        init: function() {
            this.setupTemplates();
            this.setupEventListeners();
            this.loadNotifications();
            this.requestNotificationPermission();
            this.initializeRealTimeUpdates();
        },

        setupTemplates: function() {
            // Compile Handlebars template if it exists
            const templateElement = $('#notification-template');
            if (templateElement.length) {
                this.notificationTemplate = Handlebars.compile(templateElement.html());
            }
        },

        setupEventListeners: function() {
            // Handle notification click
            $('.notifications-container').on('click', '.notification-item', (e) => {
                const notificationId = $(e.currentTarget).data('notification-id');
                if ($(e.currentTarget).hasClass('unread')) {
                    this.markAsRead(notificationId);
                }
            });

            // Handle test browser notification
            $('#testBrowserBtn').on('click', () => {
                if (!$('input[name="channels[browser]"]').is(':checked')) {
                    toastr.warning('Please enable browser notifications first');
                    return;
                }
                this.sendTestNotification();
            });

            // Handle browser notification permission
            $('input[name="channels[browser]"]').on('change', (e) => {
                if (e.target.checked) {
                    this.requestNotificationPermission();
                }
            });
        },

        loadNotifications: function() {
            $('#loadingNotifications').show();
            $('.notifications-container').hide();
            $('#noNotifications').hide();

            $.ajax({
                url: 'php_action/fetchNotifications.php',
                type: 'GET',
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        this.updateNotificationsUI(response.notifications);
                        this.updateUnreadCount(response.notifications.filter(n => !n.is_read).length);
                    } else {
                        toastr.error(response.message || 'Failed to load notifications');
                    }
                },
                error: (xhr, status, error) => {
                    toastr.error('Error loading notifications: ' + error);
                    console.error('Error loading notifications:', error);
                },
                complete: () => {
                    $('#loadingNotifications').hide();
                }
            });
        },

        updateNotificationsUI: function(notifications) {
            const container = $('.notifications-container');
            if (!container.length) return;

            container.empty();

            if (notifications.length === 0) {
                $('#noNotifications').show();
                container.hide();
            } else {
                notifications.forEach(notification => {
                    notification.created_at = moment(notification.created_at).fromNow();
                    if (this.notificationTemplate) {
                        container.append(this.notificationTemplate(notification));
                    }
                });
                container.show();
                $('#noNotifications').hide();
            }
        },

        updateUnreadCount: function(count) {
            const badge = $('#unreadCount, #notificationBadge');
            badge.text(count);
            if (count > 0) {
                badge.show();
            } else {
                badge.hide();
            }
        },

        markAsRead: function(notificationId) {
            $.ajax({
                url: 'php_action/markNotificationRead.php',
                type: 'POST',
                data: { notification_id: notificationId },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        $(`.notification-item[data-notification-id="${notificationId}"]`).removeClass('unread');
                        this.loadNotifications();
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error marking notification as read:', error);
                }
            });
        },

        sendTestNotification: function() {
            const $btn = $('#testBrowserBtn');
            const originalText = $btn.html();
            
            $btn.prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> Sending...');

            $.ajax({
                url: 'php_action/testBrowserNotification.php',
                type: 'POST',
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        this.showBrowserNotification(response.notification);
                        toastr.success(response.messages || 'Test notification sent successfully');
                    } else {
                        toastr.error(response.messages || 'Failed to send test notification');
                    }
                },
                error: (xhr, status, error) => {
                    toastr.error('Failed to send test notification: ' + error);
                    console.error('Ajax Error:', error);
                },
                complete: () => {
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        },

        showBrowserNotification: function(notification) {
            if (Notification.permission === 'granted') {
                const browserNotification = new Notification(notification.title, {
                    body: notification.message,
                    icon: 'assets/images/favicon.ico'
                });

                browserNotification.onclick = () => {
                    window.focus();
                    if (notification.notification_id) {
                        this.markAsRead(notification.notification_id);
                    }
                };

                // Auto close after 5 seconds
                setTimeout(() => browserNotification.close(), 5000);
            }
        },

        requestNotificationPermission: function() {
            if ('Notification' in window) {
                if (Notification.permission === 'default') {
                    Notification.requestPermission().then(permission => {
                        if (permission === 'denied') {
                            toastr.warning('Browser notifications permission denied');
                            $('input[name="channels[browser]"]').prop('checked', false);
                        }
                    });
                } else if (Notification.permission === 'denied') {
                    toastr.warning('Browser notifications are blocked. Please enable them in your browser settings.');
                    $('input[name="channels[browser]"]').prop('checked', false);
                }
            } else {
                toastr.warning('Browser notifications are not supported in your browser');
                $('input[name="channels[browser]"]').prop('disabled', true)
                    .closest('.form-group')
                    .append('<small class="text-muted">Browser notifications not supported</small>');
            }
        },

        initializeRealTimeUpdates: function() {
            if ('EventSource' in window) {
                // Get base URL from current location
                const baseUrl = window.location.pathname.includes('/production/') ? 
                    window.location.pathname.split('/production/')[0] + '/production/' : '/';
                
                const source = new EventSource(baseUrl + 'php_action/notification_stream.php');

                source.addEventListener('notification', (e) => {
                    const notification = JSON.parse(e.data);
                    this.handleNewNotification(notification);
                });

                source.addEventListener('count', (e) => {
                    const data = JSON.parse(e.data);
                    this.updateUnreadCount(data.unread);
                });

                source.addEventListener('error', (e) => {
                    if (e.readyState === EventSource.CLOSED) {
                        console.log('SSE connection closed');
                        // Try to reconnect after a delay
                        setTimeout(() => this.initializeRealTimeUpdates(), 5000);
                    }
                });
            } else {
                console.log('SSE not supported, falling back to polling');
                setInterval(() => this.loadNotifications(), 30000);
            }
        },

        handleNewNotification: function(notification) {
            // Show browser notification
            this.showBrowserNotification(notification);

            // Update UI
            notification.created_at = moment(notification.created_at).fromNow();
            if (this.notificationTemplate) {
                $('.notifications-container').prepend(this.notificationTemplate(notification));
            }

            // Update count
            const currentCount = parseInt($('#unreadCount').text()) || 0;
            this.updateUnreadCount(currentCount + 1);

            // Show toast notification
            toastr.info(notification.message, notification.title);
        }
    };

    // Initialize the notification system
    NotificationSystem.init();
}); 