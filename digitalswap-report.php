<?php require_once 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <i class="glyphicon glyphicon-check"></i> Digital Swap Report
            </div>
            <div class="panel-body">
                <form class="form-horizontal" action="php_action/getDigitalSwapReport.php" method="post" id="getDigitalSwapReportForm">
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
                            <div class="input-group">
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Enter email address" />
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-primary" id="emailReportBtn">
                                        <i class="glyphicon glyphicon-envelope"></i> Send Email
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-success" id="generateReportBtn">
                                <i class="glyphicon glyphicon-ok-sign"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </form>
                <div id="report-messages"></div>
                <div id="reportResults" style="margin-top: 20px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize select2 for better dropdown experience
    $('#accountId').select2({
        placeholder: "Select an account",
        allowClear: true
    });

    // Set default dates
    var today = new Date();
    var firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    
    $('#startDate').val(firstDayOfMonth.toISOString().split('T')[0]);
    $('#endDate').val(today.toISOString().split('T')[0]);

    // Email report handling
    $('#emailReportBtn').click(function() {
        var form = $('#getDigitalSwapReportForm');
        var emailInput = $('#email');
        var btn = $(this);
        
        // Clear previous messages
        $('#report-messages').empty();
        
        // Validate email
        if (!emailInput.val()) {
            $('#report-messages').html(
                '<div class="alert alert-warning">' +
                '<i class="glyphicon glyphicon-warning-sign"></i> ' +
                'Please enter an email address</div>'
            );
            emailInput.focus();
            return;
        }

        // Validate dates
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();
        if (!startDate || !endDate) {
            $('#report-messages').html(
                '<div class="alert alert-warning">' +
                '<i class="glyphicon glyphicon-warning-sign"></i> ' +
                'Please select both start and end dates</div>'
            );
            return;
        }

        // Show loading state
        btn.prop('disabled', true)
           .html('<i class="glyphicon glyphicon-refresh spinning"></i> Sending...');

        // Add action parameter for email
        var formData = form.serialize() + '&action=email';

        $.ajax({
            url: 'php_action/sendDigitalSwapReport.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#report-messages').html(
                        '<div class="alert alert-success">' +
                        '<i class="glyphicon glyphicon-ok"></i> ' +
                        'Report sent successfully to ' + emailInput.val() + '</div>'
                    );
                } else {
                    $('#report-messages').html(
                        '<div class="alert alert-danger">' +
                        '<i class="glyphicon glyphicon-remove"></i> ' +
                        (response.messages || 'Error sending email') + '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                $('#report-messages').html(
                    '<div class="alert alert-danger">' +
                    '<i class="glyphicon glyphicon-remove"></i> ' +
                    'Error sending email: ' + error + '</div>'
                );
            },
            complete: function() {
                btn.prop('disabled', false)
                   .html('<i class="glyphicon glyphicon-envelope"></i> Send Email');
            }
        });
    });

    // Keep existing form submission code for report generation
    $('#getDigitalSwapReportForm').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#generateReportBtn');
        btn.addClass('btn-loading').prop('disabled', true);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Apply the styles
                    var styleTag = '<style>' + response.styles + '</style>';
                    
                    // Add the styles and HTML content
                    $('#reportResults').html(styleTag + response.html);
                    
                    // Clear any previous error messages
                    $('#report-messages').empty();
                } else {
                    $('#report-messages').html(
                        '<div class="alert alert-danger">' + response.messages + '</div>'
                    );
                    $('#reportResults').empty();
                }
            },
            error: function(xhr, status, error) {
                $('#report-messages').html(
                    '<div class="alert alert-danger">Error generating report: ' + error + '</div>'
                );
                $('#reportResults').empty();
            },
            complete: function() {
                btn.removeClass('btn-loading').prop('disabled', false);
            }
        });
    });
});
</script>

<style>
/* Panel Styling */
.panel-default {
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.panel-heading {
    background: #f8f9fa !important;
    border-bottom: 2px solid #e9ecef;
    padding: 15px 20px;
}

.panel-heading i {
    margin-right: 10px;
    color: #28a745;
}

.panel-body {
    padding: 25px;
    background: #ffffff;
}

/* Form Styling */
.form-group {
    margin-bottom: 20px;
}

.form-control {
    height: 38px;
    border-radius: 4px;
    border: 1px solid #dce4ec;
    box-shadow: none;
    transition: border-color 0.15s ease-in-out;
}

.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

/* Select2 Custom Styling */
.select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #dce4ec;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}

/* Button Styling */
.btn {
    padding: 8px 16px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-success {
    background-color: #28a745;
    border-color: #28a745;
}

.btn-success:hover {
    background-color: #218838;
    border-color: #1e7e34;
}

.btn-primary {
    background-color: #007bff;
    border-color: #007bff;
}

/* Report Table Styling */
#reportResults table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: #fff;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-top: 20px;
}

#reportResults th {
    background: #f8f9fa;
    padding: 12px 15px;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    text-align: left;
}

#reportResults td {
    padding: 12px 15px;
    border-bottom: 1px solid #dee2e6;
    color: #495057;
}

#reportResults tr:hover {
    background-color: #f8f9fa;
}

/* Amount Column */
#reportResults td:nth-child(6),
#reportResults th:nth-child(6) {
    text-align: right;
}

/* Totals Styling */
#reportResults tr:nth-last-child(-n+3) {
    background-color: #f8f9fa;
    font-weight: 600;
}

#reportResults tr:nth-last-child(-n+3) td {
    border-top: 2px solid #dee2e6;
}

/* Net Balance Highlight */
#reportResults tr:last-child {
    background-color: #e8f4ff;
}

/* Alert Styling */
.alert {
    border-radius: 4px;
    padding: 15px 20px;
    margin: 15px 0;
    border: none;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.alert i {
    margin-right: 8px;
}

/* Email Input Group */
.input-group {
    margin-bottom: 0;
}

.input-group .form-control {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.input-group-btn .btn {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    height: 38px;
}

/* Loading Animation */
.spinning {
    animation: spin 1s infinite linear;
    display: inline-block;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .panel-body {
        padding: 15px;
    }
    
    .col-sm-10 {
        padding-left: 15px;
    }
    
    #reportResults {
        overflow-x: auto;
    }
    
    #reportResults table {
        min-width: 800px;
    }
}

/* Print Styles */
@media print {
    .btn, .input-group-btn, .no-print {
        display: none !important;
    }
    
    .panel {
        border: none !important;
        box-shadow: none !important;
    }
    
    #reportResults table {
        box-shadow: none !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>