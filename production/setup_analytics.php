<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Production Analytics Setup</h3>
            </div>
            <div class="panel-body">
                <p>This will set up the required tables for production analytics. Please make sure you have a backup of your database before proceeding.</p>
                <div id="messages"></div>
                <button type="button" class="btn btn-primary" id="runSetup">Run Setup</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#runSetup').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true);
        btn.html('Running setup...');
        
        $.ajax({
            url: 'php_action/setup_production_analytics.php',
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#messages').html('<div class="alert alert-success">Setup completed successfully!</div>');
                } else {
                    var errorMsg = '<div class="alert alert-danger"><strong>Errors:</strong><ul>';
                    response.errors.forEach(function(error) {
                        errorMsg += '<li>' + error + '</li>';
                    });
                    errorMsg += '</ul></div>';
                    $('#messages').html(errorMsg);
                }
            },
            error: function() {
                $('#messages').html('<div class="alert alert-danger">An error occurred while running the setup.</div>');
            },
            complete: function() {
                btn.prop('disabled', false);
                btn.html('Run Setup');
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 