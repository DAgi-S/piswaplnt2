<?php 
require_once 'includes/header.php'; 
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-envelope"></i> Digital Swap Email Report
            </div>
            <div class="panel-body">
                <form class="form-horizontal" id="emailReportForm">
                    <div class="form-group">
                        <label for="startDate" class="col-sm-2 control-label">Start Date</label>
                        <div class="col-sm-10">
                            <input type="date" class="form-control" id="startDate" name="startDate" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="endDate" class="col-sm-2 control-label">End Date</label>
                        <div class="col-sm-10">
                            <input type="date" class="form-control" id="endDate" name="endDate" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="accountId" class="col-sm-2 control-label">Account</label>
                        <div class="col-sm-10">
                            <select class="form-control" id="accountId" name="accountId">
                                <option value="">All Accounts</option>
                                <?php
                                $sql = "SELECT id, account_owner, account_platform 
                                       FROM accounts 
                                       ORDER BY account_platform, account_owner";
                                $result = $connect->query($sql);
                                
                                $currentPlatform = '';
                                while($account = $result->fetch_assoc()) {
                                    if($currentPlatform != $account['account_platform']) {
                                        if($currentPlatform != '') echo '</optgroup>';
                                        echo '<optgroup label="'.htmlspecialchars($account['account_platform']).'">';
                                        $currentPlatform = $account['account_platform'];
                                    }
                                    echo "<option value='".htmlspecialchars($account['id'])."'>".
                                         htmlspecialchars($account['account_owner'])."</option>";
                                }
                                if($currentPlatform != '') echo '</optgroup>';
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="type" class="col-sm-2 control-label">Type</label>
                        <div class="col-sm-10">
                            <select class="form-control" id="type" name="type">
                                <option value="">All Types</option>
                                <option value="deposit">Deposit</option>
                                <option value="withdraw">Withdraw</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email" class="col-sm-2 control-label">Email To</label>
                        <div class="col-sm-10">
                            <input type="email" class="form-control" id="email" name="email" required />
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-primary" id="sendEmailBtn">
                                <i class="glyphicon glyphicon-envelope"></i> Send Email Report
                            </button>
                        </div>
                    </div>
                </form>
                <div id="messages"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#emailReportForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var btn = $('#sendEmailBtn');
        btn.prop('disabled', true).html('<i class="glyphicon glyphicon-refresh spinning"></i> Sending...');
        
        $.ajax({
            url: 'php_action/sendDigitalSwapReport.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#messages').html(
                        '<div class="alert alert-success">' + response.messages + '</div>'
                    );
                } else {
                    var errorHtml = '<div class="alert alert-danger">';
                    errorHtml += '<h4>Error sending email</h4>';
                    
                    // Add error details if available
                    if(response.error_details) {
                        errorHtml += '<div class="error-details">';
                        errorHtml += '<strong>Error Details:</strong><br>';
                        errorHtml += '<pre>' + response.error_details + '</pre>';
                        errorHtml += '</div>';
                    }
                    
                    // Add debug information if available
                    if(response.debug && response.debug.length > 0) {
                        errorHtml += '<div class="debug-info" style="margin-top: 10px;">';
                        errorHtml += '<strong>Debug Information:</strong><br>';
                        errorHtml += '<pre style="max-height: 200px; overflow-y: auto;">';
                        errorHtml += response.debug.join('\n');
                        errorHtml += '</pre>';
                        errorHtml += '</div>';
                    }
                    
                    errorHtml += '</div>';
                    $('#messages').html(errorHtml);
                }
            },
            error: function(xhr, status, error) {
                var errorHtml = '<div class="alert alert-danger">';
                errorHtml += '<h4>Error sending email</h4>';
                
                // Add AJAX error details
                errorHtml += '<div class="error-details">';
                errorHtml += '<strong>Error Details:</strong><br>';
                errorHtml += '<pre>Status: ' + status + '\nError: ' + error + '</pre>';
                
                // Try to parse response JSON if available
                if(xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if(response.messages) {
                            errorHtml += '<strong>Server Message:</strong><br>';
                            errorHtml += '<pre>' + response.messages + '</pre>';
                        }
                    } catch(e) {
                        errorHtml += '<strong>Server Response:</strong><br>';
                        errorHtml += '<pre>' + xhr.responseText + '</pre>';
                    }
                }
                
                errorHtml += '</div></div>';
                $('#messages').html(errorHtml);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="glyphicon glyphicon-envelope"></i> Send Email Report');
            }
        });
    });
});
</script>

<style>
.spinning {
    animation: spin 1s infinite linear;
}
@keyframes spin {
    from { transform: scale(1) rotate(0deg); }
    to { transform: scale(1) rotate(360deg); }
}

/* New styles for error display */
.error-details, .debug-info {
    margin-top: 10px;
    padding: 10px;
    background-color: #f8f8f8;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.error-details pre, .debug-info pre {
    margin: 5px 0;
    padding: 10px;
    background-color: #fff;
    border: 1px solid #eee;
    border-radius: 3px;
    white-space: pre-wrap;
    word-wrap: break-word;
    font-size: 12px;
    color: #666;
}

.alert h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #a94442;
}
</style>

<?php require_once 'includes/footer.php'; ?> 