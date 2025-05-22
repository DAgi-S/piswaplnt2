<?php require_once 'includes/header.php'; ?>

<?php 
$user_id = $_SESSION['userId'];
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

// Fetch company settings
$settingsSql = "SELECT setting_key, setting_value FROM company_settings";
$settingsResult = $connect->query($settingsSql);
$companySettings = array();
while($row = $settingsResult->fetch_assoc()) {
    $companySettings[$row['setting_key']] = $row['setting_value'];
}
?>

<div class="row">
	<div class="col-md-12">
		<ol class="breadcrumb">
		  <li><a href="dashboard.php">Home</a></li>		  
		  <li class="active">Setting</li>
		</ol>

		<div class="panel panel-default">
			<div class="panel-heading">
				<div class="page-heading"> <i class="glyphicon glyphicon-wrench"></i> Setting</div>
			</div> <!-- /panel-heading -->

			<div class="panel-body">
				<!-- Alert Messages -->
				<div id="alertMessages"></div>

				<form action="php_action/changeUsername.php" method="post" class="form-horizontal" id="changeUsernameForm">
					<fieldset>
						<legend>Change Username</legend>

						<div class="changeUsenrameMessages"></div>			

						<div class="form-group">
					    <label for="username" class="col-sm-2 control-label">Username</label>
					    <div class="col-sm-10">
					      <input type="text" class="form-control" id="username" name="username" placeholder="Usename" value="<?php echo $result['username']; ?>"/>
					    </div>
					  </div>

					  <div class="form-group">
					    <div class="col-sm-offset-2 col-sm-10">
					    	<input type="hidden" name="user_id" id="user_id" value="<?php echo $result['user_id'] ?>" /> 
					      <button type="submit" class="btn btn-success" data-loading-text="Loading..." id="changeUsernameBtn"> <i class="glyphicon glyphicon-ok-sign"></i> Save Changes </button>
					    </div>
					  </div>
					</fieldset>
				</form>

				<form action="php_action/changePassword.php" method="post" class="form-horizontal" id="changePasswordForm">
					<fieldset>
						<legend>Change Password</legend>

						<div class="changePasswordMessages"></div>

						<div class="form-group">
					    <label for="password" class="col-sm-2 control-label">Current Password</label>
					    <div class="col-sm-10">
					      <input type="password" class="form-control" id="password" name="password" placeholder="Current Password">
					    </div>
					  </div>

					  <div class="form-group">
					    <label for="npassword" class="col-sm-2 control-label">New password</label>
					    <div class="col-sm-10">
					      <input type="password" class="form-control" id="npassword" name="npassword" placeholder="New Password">
					    </div>
					  </div>

					  <div class="form-group">
					    <label for="cpassword" class="col-sm-2 control-label">Confirm Password</label>
					    <div class="col-sm-10">
					      <input type="password" class="form-control" id="cpassword" name="cpassword" placeholder="Confirm Password">
					    </div>
					  </div>

					  <div class="form-group">
					    <div class="col-sm-offset-2 col-sm-10">
					    	<input type="hidden" name="user_id" id="user_id" value="<?php echo $result['user_id'] ?>" /> 
					      <button type="submit" class="btn btn-primary"> <i class="glyphicon glyphicon-ok-sign"></i> Save Changes </button>
					      
					    </div>
					  </div>


					</fieldset>
				</form>

				<form action="php_action/updateCompanySettings.php" method="post" class="form-horizontal" id="companySettingsForm" enctype="multipart/form-data">
					<fieldset>
						<legend>Company Information</legend>
						
						<div class="companySettingsMessages"></div>

						<div class="form-group">
							<label for="company_logo" class="col-sm-2 control-label">Company Logo</label>
							<div class="col-sm-10">
								<input type="file" class="form-control" id="company_logo" name="company_logo" accept="image/*">
								<?php if(isset($companySettings['company_logo']) && !empty($companySettings['company_logo'])): ?>
									<img src="<?php echo $companySettings['company_logo']; ?>" id="current_logo" alt="Current Logo" style="max-width: 200px; margin-top: 10px;">
								<?php endif; ?>
							</div>
						</div>

						<div class="form-group">
							<label for="company_name" class="col-sm-2 control-label">Company Name</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="company_name" name="company_name" 
									   value="<?php echo htmlspecialchars($companySettings['company_name'] ?? ''); ?>">
							</div>
						</div>

						<div class="form-group">
							<label for="company_tin" class="col-sm-2 control-label">Company TIN</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="company_tin" name="company_tin" 
									   value="<?php echo htmlspecialchars($companySettings['company_tin'] ?? ''); ?>">
							</div>
						</div>

						<div class="form-group">
							<label for="company_phone" class="col-sm-2 control-label">Phone Numbers</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="company_phone" name="company_phone" 
									   value="<?php echo htmlspecialchars($companySettings['company_phone'] ?? ''); ?>">
							</div>
						</div>

						<div class="form-group">
							<label for="company_email" class="col-sm-2 control-label">Email</label>
							<div class="col-sm-10">
								<input type="email" class="form-control" id="company_email" name="company_email" 
									   value="<?php echo htmlspecialchars($companySettings['company_email'] ?? ''); ?>">
							</div>
						</div>

						<div class="form-group">
							<label for="company_website" class="col-sm-2 control-label">Website</label>
							<div class="col-sm-10">
								<input type="text" class="form-control" id="company_website" name="company_website" 
									   value="<?php echo htmlspecialchars($companySettings['company_website'] ?? ''); ?>">
							</div>
						</div>

						<div class="form-group">
							<label for="company_address" class="col-sm-2 control-label">Address</label>
							<div class="col-sm-10">
								<textarea class="form-control" id="company_address" name="company_address" rows="3"><?php echo htmlspecialchars($companySettings['company_address'] ?? ''); ?></textarea>
							</div>
						</div>

						<div class="form-group">
							<label for="footer_image" class="col-sm-2 control-label">Footer Image</label>
							<div class="col-sm-10">
								<input type="file" class="form-control" id="footer_image" name="footer_image" accept="image/*">
								<?php if(isset($companySettings['footer_image']) && !empty($companySettings['footer_image'])): ?>
									<img src="<?php echo $companySettings['footer_image']; ?>" id="current_footer" alt="Current Footer" style="max-width: 200px; margin-top: 10px;">
								<?php endif; ?>
							</div>
						</div>

						<div class="form-group">
							<div class="col-sm-offset-2 col-sm-10">
								<button type="submit" class="btn btn-success" id="saveCompanySettingsBtn">
									<i class="glyphicon glyphicon-ok-sign"></i> Save Company Settings
								</button>
							</div>
						</div>
					</fieldset>
				</form>

			</div> <!-- /panel-body -->		

		</div> <!-- /panel -->		
	</div> <!-- /col-md-12 -->	
</div> <!-- /row-->


<script src="custom/js/setting.js"></script>
<?php require_once 'includes/footer.php'; ?>