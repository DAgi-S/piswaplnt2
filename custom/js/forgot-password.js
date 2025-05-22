$(document).ready(function() {
    $('#resetPasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        $('.messages').hide();
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="glyphicon glyphicon-refresh glyphicon-spin"></i> Sending...').prop('disabled', true);
        
        $.ajax({
            url: 'php_action/sendResetLink.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('.messages').html('<div class="alert alert-success">' + response.messages + '</div>').show();
                    $('#resetPasswordForm')[0].reset();
                } else {
                    $('.messages').html('<div class="alert alert-danger">' + response.messages + '</div>').show();
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
                console.error('Response:', xhr.responseText);
                $('.messages').html('<div class="alert alert-danger">An error occurred. Please try again.</div>').show();
            },
            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
}); 