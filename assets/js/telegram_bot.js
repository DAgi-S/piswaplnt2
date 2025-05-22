$(document).ready(function() {
    // Variables to store data for delete operations
    var deleteType = '';
    var deleteId = 0;
    
    // Variables for pagination
    var logOffset = 0;
    var logLimit = 100;
    var logHasMore = true;
    
    // Load initial data
    loadBotSettings();
    loadNotificationTemplates();
    loadNotificationLogs(logOffset, logLimit);
    
    // Tab change event - reload data on tab click
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href");
        
        if (target === '#botSettings') {
            loadBotSettings();
        } else if (target === '#templates') {
            loadNotificationTemplates();
        } else if (target === '#logs') {
            logOffset = 0;
            logHasMore = true;
            loadNotificationLogs(logOffset, logLimit);
        }
    });
    
    // Form submission handlers with AJAX
    $('#addBotForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addBotModal').modal('hide');
                    $('#addBotForm')[0].reset();
                    showMessage('success', response.message);
                    loadBotSettings();
                } else {
                    showMessage('danger', response.message);
                }
            },
            error: function() {
                showMessage('danger', 'Failed to communicate with the server');
            }
        });
    });
    
    $('#editBotForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editBotModal').modal('hide');
                    showMessage('success', response.message);
                    loadBotSettings();
                } else {
                    showMessage('danger', response.message);
                }
            },
            error: function() {
                showMessage('danger', 'Failed to communicate with the server');
            }
        });
    });
    
    $('#addTemplateForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#addTemplateModal').modal('hide');
                    $('#addTemplateForm')[0].reset();
                    showMessage('success', response.message);
                    loadNotificationTemplates();
                } else {
                    showMessage('danger', response.message);
                }
            },
            error: function() {
                showMessage('danger', 'Failed to communicate with the server');
            }
        });
    });
    
    $('#editTemplateForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editTemplateModal').modal('hide');
                    showMessage('success', response.message);
                    loadNotificationTemplates();
                } else {
                    showMessage('danger', response.message);
                }
            },
            error: function() {
                showMessage('danger', 'Failed to communicate with the server');
            }
        });
    });
    
    // Test bot connection
    $('#testBotConnection').click(function() {
        var botToken = $('#botToken').val();
        var chatId = $('#chatId').val();
        
        if (!botToken || !chatId) {
            $('#testStatus').html('<span class="text-danger">Please enter bot token and chat ID</span>');
            return;
        }
        
        $('#testStatus').html('<span class="text-info">Testing...</span>');
        
        $.ajax({
            url: 'php_action/telegram_bot_management.php',
            method: 'POST',
            data: {
                action: 'testBot',
                botToken: botToken,
                chatId: chatId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#testStatus').html('<span class="text-success">Success! Test message sent.</span>');
                } else {
                    $('#testStatus').html('<span class="text-danger">Failed: ' + response.message + '</span>');
                }
            },
            error: function() {
                $('#testStatus').html('<span class="text-danger">Connection error</span>');
            }
        });
    });
    
    $('#editTestBotConnection').click(function() {
        var botToken = $('#editBotToken').val();
        var chatId = $('#editChatId').val();
        
        if (!botToken || !chatId) {
            $('#editTestStatus').html('<span class="text-danger">Please enter bot token and chat ID</span>');
            return;
        }
        
        $('#editTestStatus').html('<span class="text-info">Testing...</span>');
        
        $.ajax({
            url: 'php_action/telegram_bot_management.php',
            method: 'POST',
            data: {
                action: 'testBot',
                botToken: botToken,
                chatId: chatId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#editTestStatus').html('<span class="text-success">Success! Test message sent.</span>');
                } else {
                    $('#editTestStatus').html('<span class="text-danger">Failed: ' + response.message + '</span>');
                }
            },
            error: function() {
                $('#editTestStatus').html('<span class="text-danger">Connection error</span>');
            }
        });
    });
    
    // Handle bot edit button clicks
    $(document).on('click', '.editBotBtn', function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: 'php_action/telegram_bot_management.php',
            method: 'GET',
            data: {
                action: 'getBot',
                id: id
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var bot = response.data;
                    $('#editBotId').val(bot.id);
                    $('#editBotName').val(bot.bot_name);
                    $('#editBotToken').val(bot.bot_token);
                    $('#editChatId').val(bot.chat_id);
                    $('#editWebhookUrl').val(bot.webhook_url);
                    $('#editIsActive').val(bot.is_active);
                    $('#editTestStatus').html('');
                    $('#editBotModal').modal('show');
                } else {
                    showMessage('danger', response.message);
                }
            },
            error: function() {
                showMessage('danger', 'Failed to fetch bot details');
            }
        });
    });
    
    // Handle template edit button clicks
    $(document).on('click', '.editTemplateBtn', function() {
        var id = $(this).data('id');
        
        $.ajax({
            url: 'php_action/telegram_bot_management.php',
            method: 'GET',
            data: {
                action: 'getTemplate',
                id: id
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var template = response.data;
                    $('#editTemplateId').val(template.id);
                    $('#editTemplateName').val(template.template_name);
                    $('#editTemplateKey').val(template.template_key);
                    $('#editMessageTemplate').val(template.message_template);
                    $('#editTemplateIsActive').val(template.is_active);
                    $('#editTemplateModal').modal('show');
                } else {
                    showMessage('danger', response.message);
                }
            },
            error: function() {
                showMessage('danger', 'Failed to fetch template details');
            }
        });
    });
    
    // Handle delete button clicks
    $(document).on('click', '.deleteBotBtn', function() {
        deleteType = 'bot';
        deleteId = $(this).data('id');
        $('#deleteConfirmModal').modal('show');
    });
    
    $(document).on('click', '.deleteTemplateBtn', function() {
        deleteType = 'template';
        deleteId = $(this).data('id');
        $('#deleteConfirmModal').modal('show');
    });
    
    // Handle delete confirmation
    $('#confirmDelete').click(function() {
        var action = '';
        
        if (deleteType === 'bot') {
            action = 'deleteBot';
        } else if (deleteType === 'template') {
            action = 'deleteTemplate';
        }
        
        if (action && deleteId) {
            $.ajax({
                url: 'php_action/telegram_bot_management.php',
                method: 'POST',
                data: {
                    action: action,
                    id: deleteId
                },
                dataType: 'json',
                success: function(response) {
                    $('#deleteConfirmModal').modal('hide');
                    
                    if (response.success) {
                        showMessage('success', response.message);
                        
                        if (deleteType === 'bot') {
                            loadBotSettings();
                        } else if (deleteType === 'template') {
                            loadNotificationTemplates();
                        }
                    } else {
                        showMessage('danger', response.message);
                    }
                },
                error: function() {
                    $('#deleteConfirmModal').modal('hide');
                    showMessage('danger', 'Failed to delete item');
                }
            });
        }
    });
    
    // Handle log view button clicks
    $(document).on('click', '.viewLogBtn', function() {
        var logItem = $(this).closest('tr').data('log');
        $('#logTemplate').text(logItem.template_name ? logItem.template_name + ' (' + logItem.template_key + ')' : 'No template');
        $('#logMessage').text(logItem.message);
        $('#logStatus').text(logItem.status);
        $('#logError').text(logItem.error_message || 'None');
        $('#logSentAt').text(logItem.sent_at || 'Not sent');
        $('#logCreatedAt').text(logItem.created_at);
        $('#viewLogModal').modal('show');
    });
    
    // Load more logs
    $('#loadMoreLogs button').click(function() {
        if (logHasMore) {
            logOffset += logLimit;
            loadNotificationLogs(logOffset, logLimit);
        }
    });
    
    // Reset forms when modals are closed
    $('#addBotModal').on('hidden.bs.modal', function() {
        $('#addBotForm')[0].reset();
        $('#testStatus').html('');
    });
    
    $('#editBotModal').on('hidden.bs.modal', function() {
        $('#editTestStatus').html('');
    });
    
    $('#addTemplateModal').on('hidden.bs.modal', function() {
        $('#addTemplateForm')[0].reset();
    });
    
    // Functions to load data
    function loadBotSettings() {
        $.ajax({
            url: 'php_action/telegram_bot_management.php',
            method: 'GET',
            data: {
                action: 'getBots'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderBotSettings(response.data);
                } else {
                    showMessage('danger', response.message);
                }
            },
            error: function() {
                showMessage('danger', 'Failed to fetch bot settings');
            }
        });
    }
    
    function loadNotificationTemplates() {
        $.ajax({
            url: 'php_action/telegram_bot_management.php',
            method: 'GET',
            data: {
                action: 'getTemplates'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderNotificationTemplates(response.data);
                } else {
                    showMessage('danger', response.message);
                }
            },
            error: function() {
                showMessage('danger', 'Failed to fetch notification templates');
            }
        });
    }
    
    function loadNotificationLogs(offset, limit) {
        $.ajax({
            url: 'php_action/telegram_bot_management.php',
            method: 'GET',
            data: {
                action: 'getLogs',
                offset: offset,
                limit: limit
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (offset === 0) {
                        $('#logsTableBody').empty();
                    }
                    
                    renderNotificationLogs(response.data);
                    
                    var pagination = response.pagination;
                    logHasMore = (pagination.offset + pagination.limit) < pagination.total;
                    
                    $('#loadMoreLogs').toggle(logHasMore);
                } else {
                    showMessage('danger', response.message);
                }
            },
            error: function() {
                showMessage('danger', 'Failed to fetch notification logs');
            }
        });
    }
    
    // Render data to tables
    function renderBotSettings(data) {
        var html = '';
        
        if (data.length === 0) {
            html = '<tr><td colspan="8" class="text-center">No bot settings found</td></tr>';
        } else {
            $.each(data, function(index, bot) {
                var statusBadge = bot.is_active == 1 ? 
                    '<span class="label label-success">Active</span>' : 
                    '<span class="label label-default">Inactive</span>';
                
                html += '<tr>';
                html += '<td>' + bot.id + '</td>';
                html += '<td>' + bot.bot_name + '</td>';
                html += '<td>' + bot.bot_token + '</td>';
                html += '<td>' + bot.chat_id + '</td>';
                html += '<td>' + statusBadge + '</td>';
                html += '<td>' + bot.created_at + '</td>';
                html += '<td>' + (bot.created_by_username || 'N/A') + '</td>';
                html += '<td>';
                html += '<button class="btn btn-sm btn-default editBotBtn" data-id="' + bot.id + '"><i class="glyphicon glyphicon-edit"></i></button> ';
                html += '<button class="btn btn-sm btn-danger deleteBotBtn" data-id="' + bot.id + '"><i class="glyphicon glyphicon-trash"></i></button>';
                html += '</td>';
                html += '</tr>';
            });
        }
        
        $('#botTableBody').html(html);
    }
    
    function renderNotificationTemplates(data) {
        var html = '';
        
        if (data.length === 0) {
            html = '<tr><td colspan="7" class="text-center">No notification templates found</td></tr>';
        } else {
            $.each(data, function(index, template) {
                var statusBadge = template.is_active == 1 ? 
                    '<span class="label label-success">Active</span>' : 
                    '<span class="label label-default">Inactive</span>';
                
                html += '<tr>';
                html += '<td>' + template.id + '</td>';
                html += '<td>' + template.template_name + '</td>';
                html += '<td>' + template.template_key + '</td>';
                html += '<td>' + statusBadge + '</td>';
                html += '<td>' + template.created_at + '</td>';
                html += '<td>' + (template.created_by_username || 'N/A') + '</td>';
                html += '<td>';
                html += '<button class="btn btn-sm btn-default editTemplateBtn" data-id="' + template.id + '"><i class="glyphicon glyphicon-edit"></i></button> ';
                html += '<button class="btn btn-sm btn-danger deleteTemplateBtn" data-id="' + template.id + '"><i class="glyphicon glyphicon-trash"></i></button>';
                html += '</td>';
                html += '</tr>';
            });
        }
        
        $('#templateTableBody').html(html);
    }
    
    function renderNotificationLogs(data) {
        var html = '';
        
        if (data.length === 0 && $('#logsTableBody').children().length === 0) {
            html = '<tr><td colspan="6" class="text-center">No notification logs found</td></tr>';
            $('#logsTableBody').html(html);
        } else {
            $.each(data, function(index, log) {
                var statusBadge = '';
                
                if (log.status === 'sent') {
                    statusBadge = '<span class="label label-success">Sent</span>';
                } else if (log.status === 'failed') {
                    statusBadge = '<span class="label label-danger">Failed</span>';
                } else {
                    statusBadge = '<span class="label label-warning">Pending</span>';
                }
                
                var templateName = log.template_name ? log.template_name : 'No template';
                var messageSummary = log.message.length > 50 ? log.message.substring(0, 50) + '...' : log.message;
                
                var tr = $('<tr>');
                tr.data('log', log);
                
                tr.append('<td>' + log.id + '</td>');
                tr.append('<td>' + templateName + '</td>');
                tr.append('<td>' + messageSummary + '</td>');
                tr.append('<td>' + statusBadge + '</td>');
                tr.append('<td>' + (log.sent_at || 'Not sent') + '</td>');
                tr.append('<td>' + log.created_at + '</td>');
                tr.append('<td><button class="btn btn-sm btn-info viewLogBtn"><i class="glyphicon glyphicon-eye-open"></i></button></td>');
                
                $('#logsTableBody').append(tr);
            });
        }
    }
    
    // Show messages
    function showMessage(type, message) {
        var html = '<div class="alert alert-' + type + ' alert-dismissible" role="alert">';
        html += '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
        html += message;
        html += '</div>';
        
        $('.remove-messages').html(html);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
}); 