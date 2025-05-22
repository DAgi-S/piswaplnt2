// Move this function outside document.ready
function editTemplate(templateId) {
    if(templateId) {
        // Remove previous error messages
        $('.text-danger').remove();
        $('.form-group').removeClass('has-error').removeClass('has-success');

        $.ajax({
            url: 'php_action/fetchSelectedTemplate.php',
            type: 'post',
            data: {templateId: templateId},
            dataType: 'json',
            success:function(response) {
                console.log('Response:', response); // For debugging
                
                // Fill form with template data
                $('#editTemplateForm #templateId').val(response.id);
                $('#editTemplateForm #letterCode').val(response.letter_code);
                $('#editTemplateForm #letterFor').val(response.letter_for);
                $('#editTemplateForm #location').val(response.location);
                $('#editTemplateForm #subject').val(response.letter_subject);
                $('#editTemplateForm #letterContent').val(response.letter_content);
                
                // Show modal
                $('#editTemplateModal').modal('show');
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alert('Failed to fetch template data');
            }
        });
    }
}

// Keep document.ready and move form submission inside it
$(document).ready(function() {
    // Handle form submission
    $("#submitTemplateForm").unbind('submit').bind('submit', function() {
        var form = $(this);
        
        // Remove previous error messages
        $('.form-group').removeClass('has-error');
        $('.text-danger').remove();
        
        // Get form values
        var letterCode = $("#letterCode").val();
        var letterFor = $("#letterFor").val();
        var location = $("#location").val();
        var subject = $("#subject").val();
        var letterContent = $("#letterContent").val();
        
        // Validation
        var hasError = false;
        
        if(letterCode == "") {
            $("#letterCode").closest('.form-group').addClass('has-error');
            $("#letterCode").after('<p class="text-danger">Letter Code is required</p>');
            hasError = true;
        } else if(letterCode.length > 50) {
            $("#letterCode").closest('.form-group').addClass('has-error');
            $("#letterCode").after('<p class="text-danger">Letter Code cannot exceed 50 characters</p>');
            hasError = true;
        }
        
        if(letterFor == "") {
            $("#letterFor").closest('.form-group').addClass('has-error');
            $("#letterFor").after('<p class="text-danger">Letter For is required</p>');
            hasError = true;
        } else if(letterFor.length > 100) {
            $("#letterFor").closest('.form-group').addClass('has-error');
            $("#letterFor").after('<p class="text-danger">Letter For cannot exceed 100 characters</p>');
            hasError = true;
        }
        
        if(location == "") {
            $("#location").closest('.form-group').addClass('has-error');
            $("#location").after('<p class="text-danger">Location is required</p>');
            hasError = true;
        } else if(location.length > 100) {
            $("#location").closest('.form-group').addClass('has-error');
            $("#location").after('<p class="text-danger">Location cannot exceed 100 characters</p>');
            hasError = true;
        }
        
        if(subject == "") {
            $("#subject").closest('.form-group').addClass('has-error');
            $("#subject").after('<p class="text-danger">Subject is required</p>');
            hasError = true;
        } else if(subject.length > 255) {
            $("#subject").closest('.form-group').addClass('has-error');
            $("#subject").after('<p class="text-danger">Subject cannot exceed 255 characters</p>');
            hasError = true;
        }
        
        if(letterContent == "") {
            $("#letterContent").closest('.form-group').addClass('has-error');
            $("#letterContent").after('<p class="text-danger">Letter Content is required</p>');
            hasError = true;
        }
        
        // If validation passes, submit form
        if(!hasError) {
            $.ajax({
                url: form.attr('action'),
                type: form.attr('method'),
                data: form.serialize(),
                dataType: 'json',
                success:function(response) {
                    if(response.success == true) {
                        // Reset form
                        $("#submitTemplateForm")[0].reset();
                        
                        // Close modal
                        $("#addTemplateModal").modal('hide');
                        
                        // Show success message
                        $('.remove-messages').html('<div class="alert alert-success">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                        '</div>');

                        // Reload table
                        manageLatterTemplatesTable.ajax.reload(null, false);
                    } else {
                        $('.remove-messages').html('<div class="alert alert-danger">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="glyphicon glyphicon-remove-sign"></i></strong> '+ response.messages +
                        '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    $('.remove-messages').html('<div class="alert alert-danger">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-remove-sign"></i></strong> An error occurred. Please try again.'+
                    '</div>');
                }
            });
        }
        
        return false;
    });

    // Initialize DataTable if needed
    var manageLatterTemplatesTable = $('#manageLatterTemplatesTable').DataTable({
        'ajax': 'php_action/fetchTemplates.php',
        'order': []
    });

    // Handle edit form submission
    $('#editTemplateForm').on('submit', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: 'php_action/editTemplate.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#editTemplateModal').modal('hide');
                    $('.remove-messages').html('<div class="alert alert-success">'+
                        '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                        '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
                    '</div>');
                    manageLatterTemplatesTable.ajax.reload(null, false);
                }
            }
        });
    });
}); 