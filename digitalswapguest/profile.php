<?php
require_once 'includes/core.php';
requireLogin();

// Get current user data
$user = getCurrentUser();
if (!$user) {
    setFlashMessage('User data not found', MESSAGE_ERROR);
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Get active account data
$sql = "SELECT gu.*, a.account_owner, a.account_platform, a.Currency, a.number_of_transactions, a.status 
        FROM guest_users gu 
        JOIN guest_account_links gal ON gu.id = gal.guest_id
        JOIN accounts a ON gal.account_id = a.id 
        WHERE gu.id = ? AND gal.account_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("ii", $_SESSION['guest_id'], $_SESSION['active_guest_account']);
$stmt->execute();
$result = $stmt->get_result();
$guestData = $result->fetch_assoc();

if (!$guestData) {
    setFlashMessage('Account data not found', MESSAGE_ERROR);
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Get flash message if any
$flashMessage = getFlashMessage();

require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Account Information</h3>
            </div>
            <div class="panel-body">
                <table class="table">
                    <tr>
                        <th>Guest ID</th>
                        <td><?php echo htmlspecialchars($guestData['guest_id'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <th>Full Name</th>
                        <td><?php echo htmlspecialchars($guestData['full_name'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <th>Account Owner</th>
                        <td><?php echo htmlspecialchars($guestData['account_owner'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <th>Account Platform</th>
                        <td><?php echo htmlspecialchars($guestData['account_platform'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <th>Currency</th>
                        <td><?php echo htmlspecialchars($guestData['Currency'] ?? 'ETB'); ?></td>
                    </tr>
                    <tr>
                        <th>Number of Transactions</th>
                        <td><?php echo htmlspecialchars($guestData['number_of_transactions'] ?? '0'); ?></td>
                    </tr>
                    <tr>
                        <th>Account Status</th>
                        <td>
                            <?php if(isset($guestData['status']) && $guestData['status'] == 1): ?>
                                <span class="label label-success">Active</span>
                            <?php else: ?>
                                <span class="label label-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">Change Password</h3>
            </div>
            <div class="panel-body">
                <div id="changePasswordMessages"></div>
                
                <form action="php_action/changePassword.php" method="post" class="form-horizontal" id="changePasswordForm">
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Current Password</label>
                        <div class="col-sm-8">
                            <input type="password" class="form-control" id="currentPassword" name="currentPassword" placeholder="Current Password" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">New Password</label>
                        <div class="col-sm-8">
                            <input type="password" class="form-control" id="newPassword" name="newPassword" placeholder="New Password" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Confirm Password</label>
                        <div class="col-sm-8">
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-8">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $("#changePasswordForm").on('submit', function(e) {
        e.preventDefault();
        
        // Validate passwords match
        if($("#newPassword").val() !== $("#confirmPassword").val()) {
            $("#changePasswordMessages").html(
                '<div class="alert alert-warning">'+
                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                '<strong><i class="fa fa-warning"></i></strong> New passwords do not match'+
                '</div>'
            );
            return false;
        }
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success == true) {
                    $("#changePasswordMessages").html(
                        '<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fa fa-check"></i></strong> '+ response.messages +
                        '</div>'
                    );
                    $("#changePasswordForm")[0].reset();
                } else {
                    $("#changePasswordMessages").html(
                        '<div class="alert alert-warning">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="fa fa-warning"></i></strong> '+ response.messages +
                        '</div>'
                    );
                }
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 