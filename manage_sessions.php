<?php
// First include core which handles session start
require_once 'php_action/core.php';

// Check admin access before any output
if (!isset($_SESSION['roleId']) || $_SESSION['roleId'] !== 2) {
    header('location: dashboard.php');
    exit();
}

// Now include other files after session/header operations
require_once 'includes/session.php';
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Manage Sessions</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"><i class="glyphicon glyphicon-lock"></i> Active Sessions</div>
            </div>
            <div class="panel-body">
                <div id="sessions-container">
                    <!-- Sessions will be loaded here -->
                    <div class="loading-placeholder">
                        <div class="alert alert-info">Loading sessions...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Session Template -->
<template id="session-template">
    <div class="session-item well">
        <div class="row">
            <div class="col-md-8">
                <div class="device-info">
                    <i class="device-icon fas fa-desktop"></i>
                    <span class="user-agent"></span>
                </div>
                <div class="ip-address text-muted"></div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <small class="text-muted">Last Activity</small>
                        <div class="last-activity"></div>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Expires</small>
                        <div class="expires-at"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-right">
                <span class="current-session label label-success" style="display: none;">Current</span>
                <button class="terminate-button btn btn-danger btn-sm" style="display: none;">
                    <i class="glyphicon glyphicon-trash"></i> Terminate
                </button>
            </div>
        </div>
    </div>
</template>

<!-- Toast Notification -->
<div id="toast" class="alert alert-dismissible" style="display: none; position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <span id="toast-message"></span>
</div>

<style>
.session-item {
    margin-bottom: 15px;
    border-radius: 4px;
}

.device-info {
    font-size: 16px;
    margin-bottom: 5px;
}

.device-icon {
    margin-right: 10px;
    color: #666;
}

.ip-address {
    font-size: 13px;
    color: #666;
}

.mt-2 {
    margin-top: 15px;
}

.current-session {
    margin-right: 10px;
}

#toast {
    max-width: 300px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sessionsContainer = document.getElementById('sessions-container');
    const sessionTemplate = document.getElementById('session-template');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    let currentSessionId = '<?php echo session_id(); ?>';

    function showToast(message, type = 'success') {
        toastMessage.textContent = message;
        toast.className = `alert alert-${type} alert-dismissible`;
        toast.style.display = 'block';
        
        setTimeout(() => {
            $(toast).fadeOut('slow');
        }, 3000);
    }

    function formatDate(timestamp) {
        return new Date(timestamp * 1000).toLocaleString();
    }

    function getDeviceIcon(userAgent) {
        if (/mobile/i.test(userAgent)) return 'glyphicon glyphicon-phone';
        if (/tablet/i.test(userAgent)) return 'glyphicon glyphicon-tablet';
        return 'glyphicon glyphicon-desktop';
    }

    function createSessionElement(session) {
        const template = sessionTemplate.content.cloneNode(true);
        const sessionElement = template.querySelector('.session-item');
        
        // Set device icon
        const iconElement = sessionElement.querySelector('.device-icon');
        iconElement.className = getDeviceIcon(session.user_agent);
        
        // Set session info
        sessionElement.querySelector('.user-agent').textContent = session.user_agent;
        sessionElement.querySelector('.ip-address').textContent = session.ip_address;
        sessionElement.querySelector('.last-activity').textContent = formatDate(session.last_activity);
        sessionElement.querySelector('.expires-at').textContent = formatDate(session.expires_at);
        
        // Handle current session badge and terminate button
        const currentBadge = sessionElement.querySelector('.current-session');
        const terminateButton = sessionElement.querySelector('.terminate-button');
        
        if (session.session_id === currentSessionId) {
            currentBadge.style.display = 'inline';
        } else {
            terminateButton.style.display = 'inline-block';
            terminateButton.addEventListener('click', () => terminateSession(session.session_id));
        }
        
        return sessionElement;
    }

    async function loadSessions() {
        try {
            const response = await fetch('php_action/fetchSessions.php');
            const data = await response.json();
            
            if (data.success) {
                sessionsContainer.innerHTML = '';
                data.sessions.forEach(session => {
                    sessionsContainer.appendChild(createSessionElement(session));
                });
            } else {
                throw new Error(data.messages);
            }
        } catch (error) {
            showToast('Failed to load sessions: ' + error.message, 'danger');
            console.error('Error loading sessions:', error);
        }
    }

    async function terminateSession(sessionId) {
        if (!confirm('Are you sure you want to terminate this session?')) return;

        try {
            const response = await fetch('php_action/terminateSession.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ session_id: sessionId })
            });

            const data = await response.json();
            
            if (data.success) {
                showToast('Session terminated successfully');
                await loadSessions();
            } else {
                throw new Error(data.messages);
            }
        } catch (error) {
            showToast('Failed to terminate session: ' + error.message, 'danger');
            console.error('Error terminating session:', error);
        }
    }

    // Initial load
    loadSessions();

    // Refresh sessions every minute
    setInterval(loadSessions, 60000);
});
</script>

<?php require_once 'includes/footer.php'; ?> 