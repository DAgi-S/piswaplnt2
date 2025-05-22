/**
 * Configuration Validator
 * Handles client-side validation and testing of configuration settings
 */
class ConfigurationValidator {
    constructor() {
        this.baseUrl = 'php_action/';
        this.initializeEventListeners();
    }

    /**
     * Initialize event listeners
     */
    initializeEventListeners() {
        // Email configuration testing
        document.getElementById('test-email-config')?.addEventListener('click', () => {
            this.validateEmailConfig();
        });

        // Database configuration testing
        document.getElementById('test-db-config')?.addEventListener('click', () => {
            this.validateDatabaseConfig();
        });

        // Backup configuration testing
        document.getElementById('test-backup-config')?.addEventListener('click', () => {
            this.validateBackupConfig();
        });

        // Test email sending
        document.getElementById('send-test-email')?.addEventListener('click', () => {
            this.sendTestEmail();
        });

        // Real-time validation listeners
        this.addInputValidationListeners();
    }

    /**
     * Add real-time validation listeners to inputs
     */
    addInputValidationListeners() {
        // URL validation
        document.querySelectorAll('input[data-validate="url"]').forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField('url', input.value, input);
            });
        });

        // IP validation
        document.querySelectorAll('input[data-validate="ip"]').forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField('ip', input.value, input);
            });
        });

        // Path validation
        document.querySelectorAll('input[data-validate="path"]').forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField('path', input.value, input);
            });
        });

        // JSON validation
        document.querySelectorAll('textarea[data-validate="json"]').forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField('json', input.value, input);
            });
        });

        // DateTime validation
        document.querySelectorAll('input[data-validate="datetime"]').forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField('datetime', input.value, input);
            });
        });
    }

    /**
     * Validate email configuration
     */
    async validateEmailConfig() {
        const config = {
            mail_server: document.getElementById('mail_server')?.value,
            mail_port: document.getElementById('mail_port')?.value,
            mail_username: document.getElementById('mail_username')?.value
        };

        try {
            const response = await this.sendRequest('test_configuration.php', {
                test_type: 'email',
                config: config
            });

            if (response.success) {
                this.displayResults('email-test-results', response.data);
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.showError('Error testing email configuration: ' + error.message);
        }
    }

    /**
     * Validate database configuration
     */
    async validateDatabaseConfig() {
        const config = {
            db_host: document.getElementById('db_host')?.value,
            db_name: document.getElementById('db_name')?.value,
            db_user: document.getElementById('db_user')?.value,
            db_password: document.getElementById('db_password')?.value
        };

        try {
            const response = await this.sendRequest('test_configuration.php', {
                test_type: 'database',
                config: config
            });

            if (response.success) {
                this.displayResults('database-test-results', response.data);
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.showError('Error testing database configuration: ' + error.message);
        }
    }

    /**
     * Validate backup configuration
     */
    async validateBackupConfig() {
        const config = {
            backup_path: document.getElementById('backup_path')?.value,
            backup_frequency: document.getElementById('backup_frequency')?.value,
            backup_retention: document.getElementById('backup_retention')?.value
        };

        try {
            const response = await this.sendRequest('test_configuration.php', {
                test_type: 'backup',
                config: config
            });

            if (response.success) {
                this.displayResults('backup-test-results', response.data);
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.showError('Error testing backup configuration: ' + error.message);
        }
    }

    /**
     * Send test email
     */
    async sendTestEmail() {
        const testEmail = document.getElementById('test_email')?.value;
        if (!testEmail) {
            this.showError('Test email address is required');
            return;
        }

        try {
            const response = await this.sendRequest('test_configuration.php', {
                test_type: 'test_email',
                config: { test_email: testEmail }
            });

            if (response.success) {
                this.showSuccess('Test email sent successfully');
            } else {
                this.showError(response.message);
            }
        } catch (error) {
            this.showError('Error sending test email: ' + error.message);
        }
    }

    /**
     * Validate a single field
     */
    async validateField(type, value, inputElement) {
        const config = {};
        config[type] = value;

        try {
            const response = await this.sendRequest('test_configuration.php', {
                test_type: 'validate_' + type,
                config: config
            });

            if (response.success) {
                this.updateFieldValidation(inputElement, response.data.valid);
            } else {
                this.updateFieldValidation(inputElement, false);
            }
        } catch (error) {
            this.updateFieldValidation(inputElement, false);
        }
    }

    /**
     * Send AJAX request
     */
    async sendRequest(endpoint, data) {
        try {
            const response = await fetch(this.baseUrl + endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            return await response.json();
        } catch (error) {
            throw new Error('Request failed: ' + error.message);
        }
    }

    /**
     * Display test results
     */
    displayResults(containerId, results) {
        const container = document.getElementById(containerId);
        if (!container) return;

        let html = '<div class="test-results">';
        
        if (results.messages && results.messages.length > 0) {
            html += '<h4>Validation Messages:</h4>';
            html += '<ul>';
            results.messages.forEach(message => {
                html += `<li>${message}</li>`;
            });
            html += '</ul>';
        }

        if (results.tests && results.tests.length > 0) {
            html += '<h4>Test Results:</h4>';
            html += '<ul>';
            results.tests.forEach(test => {
                const icon = test.success ? '✓' : '✗';
                const className = test.success ? 'success' : 'error';
                html += `<li class="${className}">${icon} ${test.test}: ${test.message}</li>`;
            });
            html += '</ul>';
        }

        html += '</div>';
        container.innerHTML = html;
    }

    /**
     * Update field validation visual feedback
     */
    updateFieldValidation(inputElement, isValid) {
        if (!inputElement) return;

        inputElement.classList.remove('is-valid', 'is-invalid');
        inputElement.classList.add(isValid ? 'is-valid' : 'is-invalid');

        // Update feedback element if it exists
        const feedback = inputElement.nextElementSibling;
        if (feedback && feedback.classList.contains('invalid-feedback')) {
            feedback.style.display = isValid ? 'none' : 'block';
        }
    }

    /**
     * Show success message
     */
    showSuccess(message) {
        // Implement your preferred success message display method
        alert(message); // Replace with your UI framework's method
    }

    /**
     * Show error message
     */
    showError(message) {
        // Implement your preferred error message display method
        alert(message); // Replace with your UI framework's method
    }
}

// Initialize the validator when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.configValidator = new ConfigurationValidator();
}); 