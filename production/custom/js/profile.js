$(document).ready(function() {
    // Initialize form submission
    $('#updateProfileForm').on('submit', function(e) {
        e.preventDefault();
        
        // Create FormData object to handle file uploads
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,  // Important for FormData
            contentType: false,  // Important for FormData
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showAlert('success', response.message);
                    
                    // Update profile image if provided
                    if (response.profileImage) {
                        $('.profile-image').attr('src', '../' + response.profileImage);
                        $('.profile-img-small').attr('src', '../' + response.profileImage);
                    }
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while updating profile');
            }
        });
    });

    // Initialize password change form submission
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    $('#changePasswordForm')[0].reset();
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while changing password');
            }
        });
    });

    // Initialize preferences form submission
    $('#updatePreferencesForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Error occurred while updating preferences');
            }
        });
    });

    // Profile image preview
    $('input[name="profile_image"]').on('change', function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('.profile-image').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
});

// Function to show alerts
function showAlert(type, message) {
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    var alert = '<div class="alert ' + alertClass + ' alert-dismissible" role="alert">' +
                '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                '<span aria-hidden="true">&times;</span></button>' + message + '</div>';
    
    $('.page-heading').after(alert);
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 3000);
} 