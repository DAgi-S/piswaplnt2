<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';

// Get user data
$userId = $_SESSION['userId'];
$sql = "SELECT u.*, ur.role_name 
        FROM users u 
        LEFT JOIN user_roles ur ON u.role_id = ur.role_id 
        WHERE u.user_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Profile</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-user"></i> My Profile
                </div>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <img src="<?php echo isset($userData['profile_image']) ? $userData['profile_image'] : 'assets/images/default-user.png'; ?>" 
                                 class="img-circle profile-image" 
                                 alt="Profile Image" 
                                 style="width: 150px; height: 150px;">
                            <h4><?php echo htmlspecialchars($userData['username']); ?></h4>
                            <span class="label label-info"><?php echo htmlspecialchars($userData['role_name']); ?></span>
                        </div>
                        <hr>
                        <div class="profile-stats">
                            <p><strong>Member Since:</strong> <?php echo date('M d, Y', strtotime($userData['created_at'])); ?></p>
                            <p><strong>Last Login:</strong> <?php echo $userData['last_login'] ? date('M d, Y H:i', strtotime($userData['last_login'])) : 'Never'; ?></p>
                            <p><strong>Status:</strong> 
                                <span class="label <?php echo $userData['status'] ? 'label-success' : 'label-danger'; ?>">
                                    <?php echo $userData['status'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <ul class="nav nav-tabs">
                            <li class="active"><a href="#profile" data-toggle="tab">Profile Information</a></li>
                            <li><a href="#password" data-toggle="tab">Change Password</a></li>
                            <li><a href="#preferences" data-toggle="tab">Preferences</a></li>
                        </ul>

                        <div class="tab-content">
                            <!-- Profile Information Tab -->
                            <div class="tab-pane active" id="profile">
                                <form id="updateProfileForm" action="php_action/updateProfile.php" method="post" enctype="multipart/form-data" class="form-horizontal" style="margin-top: 20px;">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Username</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($userData['username']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Email</label>
                                        <div class="col-sm-9">
                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Full Name</label>
                                        <div class="col-sm-9">
                                            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($userData['full_name'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Phone</label>
                                        <div class="col-sm-9">
                                            <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Profile Image</label>
                                        <div class="col-sm-9">
                                            <input type="file" class="form-control" name="profile_image" accept="image/*">
                                            <small class="text-muted">Max file size: 2MB. Supported formats: JPG, PNG</small>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-offset-3 col-sm-9">
                                            <button type="submit" class="btn btn-primary">Update Profile</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Change Password Tab -->
                            <div class="tab-pane" id="password">
                                <form id="changePasswordForm" action="php_action/changePassword.php" method="post" class="form-horizontal" style="margin-top: 20px;">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Current Password</label>
                                        <div class="col-sm-9">
                                            <input type="password" class="form-control" name="currentPassword" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">New Password</label>
                                        <div class="col-sm-9">
                                            <input type="password" class="form-control" name="newPassword" required>
                                            <small class="text-muted">Minimum 6 characters, at least one number and one letter</small>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Confirm Password</label>
                                        <div class="col-sm-9">
                                            <input type="password" class="form-control" name="confirmPassword" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-offset-3 col-sm-9">
                                            <button type="submit" class="btn btn-primary">Change Password</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Preferences Tab -->
                            <div class="tab-pane" id="preferences">
                                <form id="updatePreferencesForm" action="php_action/updatePreferences.php" method="post" class="form-horizontal" style="margin-top: 20px;">
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Language</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" name="language">
                                                <option value="en" <?php echo ($userData['language'] ?? 'en') == 'en' ? 'selected' : ''; ?>>English</option>
                                                <option value="es" <?php echo ($userData['language'] ?? '') == 'es' ? 'selected' : ''; ?>>Spanish</option>
                                                <option value="fr" <?php echo ($userData['language'] ?? '') == 'fr' ? 'selected' : ''; ?>>French</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Time Zone</label>
                                        <div class="col-sm-9">
                                            <select class="form-control" name="timezone">
                                                <?php
                                                $timezones = DateTimeZone::listIdentifiers();
                                                $currentTimezone = $userData['timezone'] ?? 'UTC';
                                                foreach ($timezones as $timezone) {
                                                    echo '<option value="' . $timezone . '"' . 
                                                         ($timezone == $currentTimezone ? ' selected' : '') . '>' . 
                                                         $timezone . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="col-sm-3 control-label">Email Notifications</label>
                                        <div class="col-sm-9">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="notify_updates" value="1" 
                                                           <?php echo ($userData['notify_updates'] ?? 0) ? 'checked' : ''; ?>>
                                                    System Updates
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="notify_alerts" value="1"
                                                           <?php echo ($userData['notify_alerts'] ?? 0) ? 'checked' : ''; ?>>
                                                    Critical Alerts
                                                </label>
                                            </div>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="notify_reports" value="1"
                                                           <?php echo ($userData['notify_reports'] ?? 0) ? 'checked' : ''; ?>>
                                                    Report Generation
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-sm-offset-3 col-sm-9">
                                            <button type="submit" class="btn btn-primary">Save Preferences</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script src="custom/js/profile.js"></script>

<style>
.profile-image {
    border: 5px solid #f8f9fa;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.profile-stats {
    margin-top: 20px;
}
.tab-content {
    padding: 20px 0;
}
.nav-tabs {
    margin-top: 20px;
}
</style> 