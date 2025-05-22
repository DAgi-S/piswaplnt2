class AuditLogger {
    constructor() {
        this.baseUrl = '/php_action/audit_log.php';
    }

    async logAction(action, module, details = '') {
        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action,
                    module,
                    details: typeof details === 'object' ? JSON.stringify(details) : details
                })
            });

            const result = await response.json();
            if (!result.success) {
                console.error('Audit logging failed:', result.errors);
            }
            return result;
        } catch (error) {
            console.error('Error logging audit:', error);
            return { success: false, error: error.message };
        }
    }

    async getLogs(filters = {}) {
        try {
            const queryParams = new URLSearchParams();
            Object.entries(filters).forEach(([key, value]) => {
                if (value !== null && value !== undefined) {
                    queryParams.append(key, value);
                }
            });

            const response = await fetch(`${this.baseUrl}?${queryParams.toString()}`);
            return await response.json();
        } catch (error) {
            console.error('Error fetching audit logs:', error);
            return { success: false, error: error.message };
        }
    }

    // Helper method to log configuration changes
    async logConfigChange(module, oldValue, newValue) {
        return this.logAction('config_update', module, {
            old_value: oldValue,
            new_value: newValue,
            timestamp: new Date().toISOString()
        });
    }

    // Helper method to log security events
    async logSecurityEvent(action, details) {
        return this.logAction(action, 'security', details);
    }

    // Helper method to log user actions
    async logUserAction(action, details) {
        return this.logAction(action, 'user', details);
    }
}

// Create global instance
window.auditLogger = new AuditLogger(); 