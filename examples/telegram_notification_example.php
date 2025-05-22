<?php
/**
 * Example script to demonstrate how to use the Telegram notification system
 */
require_once '../php_action/db_connect.php';
require_once '../php_action/core.php';
require_once '../php_action/telegram_notification.php';

// Display a simple form to test notifications
$title = "Telegram Notification Example";
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <style>
        body { padding-top: 20px; }
        .response-box { 
            margin-top: 20px; 
            padding: 10px; 
            border: 1px solid #ddd; 
            border-radius: 5px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><?php echo $title; ?></h3>
                    </div>
                    <div class="panel-body">
                        
                        <h4>About this example</h4>
                        <p>This example demonstrates how to use the Telegram notification system to send notifications based on templates or direct messages.</p>
                        
                        <h4>1. Send notification using a template</h4>
                        <form id="templateForm">
                            <div class="form-group">
                                <label for="templateKey">Template Key</label>
                                <select class="form-control" id="templateKey" name="templateKey">
                                    <option value="">-- Select a template --</option>
                                    <?php
                                    // Get all active templates
                                    $sql = "SELECT template_key, template_name FROM telegram_notification_templates WHERE is_active = 1";
                                    $result = $connect->query($sql);
                                    if ($result && $result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<option value="' . $row['template_key'] . '">' . $row['template_name'] . ' (' . $row['template_key'] . ')</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="templateData">Template Data (JSON format)</label>
                                <textarea class="form-control" id="templateData" name="templateData" rows="5" placeholder='{"order_id": "12345", "client_name": "Company ABC", "amount": "$1,500.00", "order_date": "2023-07-15 14:30"}'></textarea>
                                <p class="help-block">Enter the data as JSON to replace template variables.</p>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Send Template Notification</button>
                        </form>
                        
                        <div id="templateResponse" class="response-box"></div>
                        
                        <hr>
                        
                        <h4>2. Send direct message</h4>
                        <form id="directForm">
                            <div class="form-group">
                                <label for="directMessage">Message</label>
                                <textarea class="form-control" id="directMessage" name="directMessage" rows="5" placeholder="Enter your message here"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Send Direct Message</button>
                        </form>
                        
                        <div id="directResponse" class="response-box"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="../assets/jquery/jquery.min.js"></script>
    <script src="../assets/bootstrap/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            // Template notification form
            $('#templateForm').submit(function(e) {
                e.preventDefault();
                
                var templateKey = $('#templateKey').val();
                var templateData = $('#templateData').val();
                
                if (!templateKey) {
                    alert('Please select a template');
                    return;
                }
                
                try {
                    // Check if data is valid JSON
                    var dataObj = JSON.parse(templateData);
                    
                    $.ajax({
                        url: 'telegram_notification_handler.php',
                        method: 'POST',
                        data: {
                            type: 'template',
                            templateKey: templateKey,
                            templateData: templateData
                        },
                        dataType: 'json',
                        success: function(response) {
                            var html = '<div class="alert alert-' + (response.success ? 'success' : 'danger') + '">';
                            html += response.message;
                            html += '</div>';
                            
                            $('#templateResponse').html(html).show();
                        },
                        error: function() {
                            $('#templateResponse').html('<div class="alert alert-danger">Failed to communicate with the server</div>').show();
                        }
                    });
                } catch (e) {
                    alert('Invalid JSON data format');
                }
            });
            
            // Direct message form
            $('#directForm').submit(function(e) {
                e.preventDefault();
                
                var message = $('#directMessage').val();
                
                if (!message) {
                    alert('Please enter a message');
                    return;
                }
                
                $.ajax({
                    url: 'telegram_notification_handler.php',
                    method: 'POST',
                    data: {
                        type: 'direct',
                        message: message
                    },
                    dataType: 'json',
                    success: function(response) {
                        var html = '<div class="alert alert-' + (response.success ? 'success' : 'danger') + '">';
                        html += response.message;
                        html += '</div>';
                        
                        $('#directResponse').html(html).show();
                    },
                    error: function() {
                        $('#directResponse').html('<div class="alert alert-danger">Failed to communicate with the server</div>').show();
                    }
                });
            });
        });
    </script>
</body>
</html> 