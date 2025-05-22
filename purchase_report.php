<?php require_once 'includes/header.php'; ?>

<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default">
			<div class="panel-heading">
				<i class="glyphicon glyphicon-check"></i>	Purchase Report
			</div>
			<!-- /panel-heading -->
			<div class="panel-body">
				
				<form class="form-horizontal" action="php_action/getPurchaseReport.php" method="post" id="getPurchaseReportForm">
				  <div class="form-group">
				    <label for="startDate" class="col-sm-2 control-label">Start Date</label>
				    <div class="col-sm-10">
				      <input type="text" class="form-control" id="startDate" name="startDate" placeholder="Start Date" />
				    </div>
				  </div>
				  <div class="form-group">
				    <label for="endDate" class="col-sm-2 control-label">End Date</label>
				    <div class="col-sm-10">
				      <input type="text" class="form-control" id="endDate" name="endDate" placeholder="End Date" />
				    </div>
				  </div>
                  <div class="form-group">
                    <label for="supplier" class="col-sm-2 control-label">Supplier</label>
                    <div class="col-sm-10">
                        <select class="form-control" id="supplier" name="supplier">
                            <option value="">All Suppliers</option>
                            <?php 
                            $sql = "SELECT * FROM suppliers WHERE active = 1";
                            $result = $connect->query($sql);
                            while($row = $result->fetch_array()) {
                                echo "<option value='".$row['id']."'>".$row['company_name']."</option>";
                            }
                            ?>
                        </select>
                    </div>
                  </div>
				  <div class="form-group">
				    <label for="email" class="col-sm-2 control-label">Email To</label>
				    <div class="col-sm-10">
				      <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address" />
				    </div>
				  </div>
				  <div class="form-group">
				    <div class="col-sm-offset-2 col-sm-10">
				      <button type="submit" class="btn btn-success" id="generateReportBtn"> 
                        <i class="glyphicon glyphicon-ok-sign"></i> Generate Report
                      </button>
                      <button type="button" class="btn btn-primary" id="sendEmailBtn" style="margin-left: 10px;"> 
                        <i class="glyphicon glyphicon-envelope"></i> Send Email Report
                      </button>
				    </div>
				  </div>
				</form>

                <div id="reportPreview" class="mt-4" style="margin-top: 20px;">
                    <div id="messages"></div>
                    <div id="previewContent"></div>
                </div>

			</div>
			<!-- /panel-body -->
		</div>
	</div>
	<!-- /col-dm-12 -->
</div>
<!-- /row -->

<style>
.spinning {
    animation: spin 1s infinite linear;
}
@keyframes spin {
    from { transform: scale(1) rotate(0deg); }
    to { transform: scale(1) rotate(360deg); }
}

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

#previewContent {
    margin-top: 20px;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background-color: #fff;
}

.alert h4 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #a94442;
}

.no-purchases-message {
    text-align: center;
    padding: 30px;
    background-color: #f8f9fa;
    border: 1px dashed #dee2e6;
    border-radius: 4px;
    margin: 20px 0;
    color: #6c757d;
    font-size: 16px;
}

.no-purchases-message i {
    font-size: 24px;
    margin-right: 10px;
    vertical-align: middle;
}

.report-container {
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.report-header {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #eee;
}

.report-logo {
    width: 100px;
    height: auto;
    margin-right: 20px;
}

.company-info {
    flex: 1;
}

.company-info h2 {
    margin: 0 0 10px 0;
    color: #333;
}

.company-info p {
    margin: 5px 0;
    color: #666;
}

.report-title {
    text-align: center;
    margin: 20px 0;
    color: #333;
}

.report-period {
    text-align: center;
    color: #666;
    margin-bottom: 30px;
}

/* Error message styling */
.error-message {
    background-color: #fff3f3;
    border: 1px solid #ffa7a7;
    padding: 15px;
    border-radius: 4px;
    margin: 20px 0;
    color: #dc3545;
}

.error-message h4 {
    margin-top: 0;
    margin-bottom: 10px;
}

.error-details {
    background: #fff;
    padding: 10px;
    border-radius: 4px;
    margin-top: 10px;
}

/* Select2 customization */
.select2-container {
    width: 100% !important;
}
</style>

<script>
$(document).ready(function() {
    // Initialize date pickers
    $("#startDate").datepicker();
    $("#endDate").datepicker();

    // Initialize select2 for supplier dropdown
    $("#supplier").select2();

    $("#getPurchaseReportForm").submit(function(e) {
        e.preventDefault();
        generateReport();
    });

    $("#sendEmailBtn").click(function() {
        if (!$("#email").val()) {
            $('#messages').html('<div class="alert alert-danger">Please enter an email address</div>');
            return;
        }
        sendEmailReport();
    });

    function generateReport() {
        var btn = $('#generateReportBtn');
        btn.prop('disabled', true).html('<i class="glyphicon glyphicon-refresh spinning"></i> Generating...');

        $.ajax({
            url: 'php_action/getPurchaseReport.php',
            type: 'POST',
            data: $("#getPurchaseReportForm").serialize(),
            dataType: 'json',
            success: function(response) {
                handleReportSuccess(response);
            },
            error: handleAjaxError,
            complete: function() {
                btn.prop('disabled', false).html('<i class="glyphicon glyphicon-ok-sign"></i> Generate Report');
            }
        });
    }

    function sendEmailReport() {
        var btn = $('#sendEmailBtn');
        btn.prop('disabled', true).html('<i class="glyphicon glyphicon-refresh spinning"></i> Sending...');

        $.ajax({
            url: 'php_action/sendPurchaseReport.php',
            type: 'POST',
            data: $("#getPurchaseReportForm").serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#messages').html('<div class="alert alert-success">' + response.messages + '</div>');
                } else {
                    handleErrorResponse(response);
                }
            },
            error: handleAjaxError,
            complete: function() {
                btn.prop('disabled', false).html('<i class="glyphicon glyphicon-envelope"></i> Send Email Report');
            }
        });
    }

    function handleErrorResponse(response) {
        var errorHtml = '<div class="alert alert-danger">';
        errorHtml += '<h4>Error</h4>';
        
        if(response.error_details) {
            errorHtml += '<div class="error-details">';
            errorHtml += '<strong>Error Details:</strong><br>';
            errorHtml += '<pre>' + response.error_details + '</pre>';
            errorHtml += '</div>';
        }
        
        if(response.debug && response.debug.length > 0) {
            errorHtml += '<div class="debug-info">';
            errorHtml += '<strong>Debug Information:</strong><br>';
            errorHtml += '<pre>' + response.debug.join('\n') + '</pre>';
            errorHtml += '</div>';
        }
        
        errorHtml += '</div>';
        $('#messages').html(errorHtml);
    }

    function handleAjaxError(xhr, status, error) {
        var errorHtml = '<div class="error-message">';
        errorHtml += '<h4><i class="glyphicon glyphicon-warning-sign"></i> Error Generating Report</h4>';
        
        if(error === 'parsererror') {
            errorHtml += '<p>The report data could not be processed. Please try again.</p>';
        } else {
            errorHtml += '<p>An error occurred while generating the report.</p>';
        }
        
        if(xhr.responseText) {
            errorHtml += '<div class="error-details">';
            errorHtml += '<strong>Technical Details:</strong><br>';
            errorHtml += '<pre>' + xhr.responseText + '</pre>';
            errorHtml += '</div>';
        }
        
        errorHtml += '</div>';
        $('#messages').html(errorHtml);
        $('#previewContent').html(''); // Clear any previous content
    }

    function handleReportSuccess(response) {
        if(response.success) {
            if(response.html.includes('no-purchases-message')) {
                // No purchases case
                $('#previewContent').html(response.html);
                $('#messages').html('<div class="alert alert-info">Report generated successfully - No purchases found for the selected period</div>');
            } else {
                // Normal case with purchase data
                $('#previewContent').html(response.html);
                $('#messages').html('<div class="alert alert-success">Report generated successfully</div>');
            }
        } else {
            handleErrorResponse(response);
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 