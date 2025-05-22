$(document).ready(function() {
	// main menu
	$("#navSetting").addClass('active');
	// sub manin
	$("#topNavSetting").addClass('active');

	// change username
	$("#changeUsernameForm").unbind('submit').bind('submit', function() {
		var form = $(this);

		var username = $("#username").val();

		if(username == "") {
			$("#username").after('<p class="text-danger">Username field is required</p>');
			$("#username").closest('.form-group').addClass('has-error');
		} else {

			$(".text-danger").remove();
			$('.form-group').removeClass('has-error');

			$("#changeUsernameBtn").button('loading');

			$.ajax({
				url: form.attr('action'),
				type: form.attr('method'),
				data: form.serialize(),
				dataType: 'json',
				success:function(response) {

					$("#changeUsernameBtn").button('reset');
					// remove text-error 
					$(".text-danger").remove();
					// remove from-group error
					$(".form-group").removeClass('has-error').removeClass('has-success');

					if(response.success == true)  {												
																
						// shows a successful message after operation
						$('.changeUsenrameMessages').html('<div class="alert alert-success">'+
	            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
	            '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
	          '</div>');

						// remove the mesages
	          $(".alert-success").delay(500).show(10, function() {
							$(this).delay(3000).hide(10, function() {
								$(this).remove();
							});
						}); // /.alert	          					
						
					} else {
						// shows a successful message after operation
						$('.changeUsenrameMessages').html('<div class="alert alert-warning">'+
	            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
	            '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.messages +
	          '</div>');

						// remove the mesages
	          $(".alert-warning").delay(500).show(10, function() {
							$(this).delay(3000).hide(10, function() {
								$(this).remove();
							});
						}); // /.alert	          					
					}
				} // /success 
			}); // /ajax
		}
			
		return false;
	});

	$("#changePasswordForm").unbind('submit').bind('submit', function() {

		var form = $(this);

		$(".text-danger").remove();

		var currentPassword = $("#password").val();
		var newPassword = $("#npassword").val();
		var conformPassword = $("#cpassword").val();

		if(currentPassword == "" || newPassword == "" || conformPassword == "") {
			if(currentPassword == "") {
				$("#password").after('<p class="text-danger">The Current Password field is required</p>');
				$("#password").closest('.form-group').addClass('has-error');
			} else {
				$("#password").closest('.form-group').removeClass('has-error');
				$(".text-danger").remove();
			}

			if(newPassword == "") {
				$("#npassword").after('<p class="text-danger">The New Password field is required</p>');
				$("#npassword").closest('.form-group').addClass('has-error');
			} else {
				$("#npassword").closest('.form-group').removeClass('has-error');
				$(".text-danger").remove();
			}

			if(conformPassword == "") {
				$("#cpassword").after('<p class="text-danger">The Conform Password field is required</p>');
				$("#cpassword").closest('.form-group').addClass('has-error');
			} else {
				$("#cpassword").closest('.form-group').removeClass('has-error');
				$(".text-danger").remove();
			}
		} else {
			$(".form-group").removeClass('has-error');
			$(".text-danger").remove();

			$.ajax({
				url: form.attr('action'),
				type: form.attr('method'),
				data: form.serialize(),
				dataType: 'json',
				success:function(response) {
					console.log(response);
					if(response.success == true) {
						$('.changePasswordMessages').html('<div class="alert alert-success">'+
	            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
	            '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
	          '</div>');

						// remove the mesages
	          $(".alert-success").delay(500).show(10, function() {
							$(this).delay(3000).hide(10, function() {
								$(this).remove();
							});
						}); // /.alert	    
					} else {

						$('.changePasswordMessages').html('<div class="alert alert-warning">'+
	            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
	            '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.messages +
	          '</div>');

						// remove the mesages
	          $(".alert-warning").delay(500).show(10, function() {
							$(this).delay(3000).hide(10, function() {
								$(this).remove();
							});
						}); // /.alert	          	
					}
				} // /success function
			}); // /ajax function

		} // /else


		return false;
	});

	// Function to show alerts
	function showAlert(type, message, timeout = 3000) {
		// Remove any existing alerts
		$("#alertMessages").empty();
		
		// Create alert HTML
		var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
		var iconClass = type === 'success' ? 'glyphicon-ok-sign' : 'glyphicon-exclamation-sign';
		
		var alertHtml = `
			<div class="alert ${alertClass} alert-dismissible fade in" role="alert">
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<i class="glyphicon ${iconClass}"></i> ${message}
			</div>
		`;
		
		// Add alert to container
		$("#alertMessages").html(alertHtml);
		
		// Scroll to alert
		$('html, body').animate({
			scrollTop: $("#alertMessages").offset().top - 100
		}, 200);
		
		// Auto dismiss after timeout
		if (timeout) {
			setTimeout(function() {
				$("#alertMessages .alert").fadeOut('slow', function() {
					$(this).remove();
				});
			}, timeout);
		}
	}

	// Company Settings Form Submit
	$("#companySettingsForm").submit(function(e) {
		e.preventDefault();
		
		// Create FormData object
		var formData = new FormData(this);
		
		// Show loading state
		$("#saveCompanySettingsBtn")
			.attr('disabled', true)
			.html('<i class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></i> Saving Changes...');
		
		$.ajax({
			url: 'php_action/updateCompanySettings.php',
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			dataType: 'json',
			success: function(response) {
				if(response.success) {
					showAlert('success', 'Company settings updated successfully! 👍');
					
					// If files were uploaded, reload page after delay
					if($("#company_logo").val() || $("#footer_image").val()) {
						showAlert('success', 'Settings saved! Page will refresh in 2 seconds...', 2000);
						setTimeout(function() {
							location.reload();
						}, 2000);
					}
				} else {
					showAlert('error', 'Error: ' + (response.messages || 'Unknown error occurred'));
				}
			},
			error: function(xhr, status, error) {
				let errorMessage = 'Error updating settings';
				try {
					const response = JSON.parse(xhr.responseText);
					errorMessage = response.messages || error;
				} catch(e) {
					errorMessage += ': ' + error;
				}
				showAlert('error', errorMessage);
				console.error('Ajax Error:', xhr.responseText);
			},
			complete: function() {
				// Reset button state
				$("#saveCompanySettingsBtn")
					.attr('disabled', false)
					.html('<i class="glyphicon glyphicon-ok-sign"></i> Save Company Settings');
			}
		});
	});

	// Preview images before upload
	function readURL(input, previewId) {
		if (input.files && input.files[0]) {
			var reader = new FileReader();
			
			reader.onload = function(e) {
				const preview = $(previewId);
				preview.attr('src', e.target.result);
				preview.show(); // Make sure preview is visible
				
				// Show preview success message
				const fieldName = input.id === 'company_logo' ? 'Company Logo' : 'Footer Image';
				showAlert('success', `${fieldName} ready for upload! Click Save to apply changes.`, 2000);
			}
			
			reader.onerror = function() {
				showAlert('error', 'Error reading file. Please try another image.');
			}
			
			reader.readAsDataURL(input.files[0]);
		}
	}

	// File input change handlers
	$("#company_logo").change(function() {
		const file = this.files[0];
		if(file) {
			if(file.size > 5000000) { // 5MB limit
				showAlert('error', 'Company logo file is too large. Maximum size is 5MB.');
				this.value = '';
				return;
			}
			readURL(this, "#current_logo");
		}
	});

	$("#footer_image").change(function() {
		const file = this.files[0];
		if(file) {
			if(file.size > 5000000) { // 5MB limit
				showAlert('error', 'Footer image file is too large. Maximum size is 5MB.');
				this.value = '';
				return;
			}
			readURL(this, "#current_footer");
		}
	});

	// Form validation before submit
	$("#companySettingsForm").on('submit', function(e) {
		const requiredFields = ['company_name', 'company_phone', 'company_email'];
		let hasError = false;
		
		requiredFields.forEach(field => {
			const value = $(`#${field}`).val().trim();
			if(!value) {
				showAlert('error', `${field.replace('_', ' ').toUpperCase()} is required`);
				hasError = true;
			}
		});
		
		if(hasError) {
			e.preventDefault();
			return false;
		}
	});
});