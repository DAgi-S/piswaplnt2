<?php
require_once 'includes/core.php';

// Get flash message if any
$flashMessage = getFlashMessage();
?>
<!DOCTYPE html>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-danger">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-exclamation-triangle"></i> Access Denied
                </div>
            </div>
            <div class="panel-body">
                <div class="alert alert-danger" role="alert">
                    <h4><i class="fa fa-ban"></i> Permission Denied</h4>
                    <p>You do not have permission to access this page. Please contact your administrator if you believe this is an error.</p>
                </div>
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fa fa-dashboard"></i> Return to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 