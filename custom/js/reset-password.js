$(document).ready(function() {
    $('#resetPasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        $('.messages').hide();
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Check if passwords match
        if (password !== confirmPassword) {
            $('.messages').html('<div class="alert alert-danger">Error! Passwords do not match.</div>').show();
            return;
        }
        
        submitBtn.html('<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Resetting...').prop('disabled', true);
        
        $.ajax({
            url: 'php_action/updatePassword.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('.messages').html('<div class="alert alert-success">' + response.messages + '</div>').show();
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    $('.messages').html('<div class="alert alert-danger">' + response.messages + '</div>').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
                console.error('Status:', status);
                console.error('Response:', xhr.responseText);
                console.error('Status code:', xhr.status);
                
                let errorMessage = 'An error occurred. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.messages || errorMessage;
                } catch(e) {
                    console.error('Error parsing response:', e);
                }
                
                $('.messages').html('<div class="alert alert-danger">Error: ' + errorMessage + '</div>').show();
            },
            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
            }
        })
    });
});     