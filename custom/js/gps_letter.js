$(document).ready(function() {
    // Load templates into select dropdown
    $.ajax({
        url: 'php_action/fetchTemplatesForSelect.php',
        type: 'get',
        dataType: 'json',
        success: function(response) {
            var options = '<option value="">Select Template</option>';
            response.forEach(function(template) {
                options += `<option value="${template.id}">${template.letter_code}</option>`;
            });
            $("#templateId").html(options);
        }
    });

    // Add new vehicle row
    $("#addVehicleRow").click(function() {
        var newRow = `<tr>
            <td><input type="text" class="form-control" name="plate[]"></td>
            <td><input type="text" class="form-control" name="trailer[]"></td>
            <td><input type="text" class="form-control" name="chassis[]"></td>
            <td><input type="text" class="form-control" name="motor[]"></td>
            <td><input type="text" class="form-control" name="imei[]"></td>
            <td><button type="button" class="btn btn-danger btn-sm removeRow">Remove</button></td>
        </tr>`;
        $("#vehicleTable tbody").append(newRow);
    });

    // Remove vehicle row
    $(document).on('click', '.removeRow', function() {
        $(this).closest('tr').remove();
    });

    // Load letter template details
    $("#templateId").change(function() {
        var templateId = $(this).val();
        if(templateId) {
            $.ajax({
                url: 'php_action/fetchTemplate.php',
                type: 'post',
                data: {templateId: templateId},
                dataType: 'json',
                success: function(response) {
                    $("#letterFor").val(response.letter_for);
                    $("#location").val(response.location);
                    $("#subject").val(response.letter_subject);
                    $("#letterContent").val(response.letter_content);
                }
            });
        }
    });

    // Add form validation
    $("#gpsLetterForm").submit(function(e) {
        e.preventDefault();
        
        var hasError = false;
        $('.text-danger').remove();
        
        // Validate required fields
        if(!$("#templateId").val()) {
            $("#templateId").after('<p class="text-danger">Please select a template</p>');
            hasError = true;
        }
        
        if(!$("#clientName").val()) {
            $("#clientName").after('<p class="text-danger">Please enter client name</p>');
            hasError = true;
        }
        
        // Validate at least one vehicle
        var hasVehicle = false;
        $("input[name='plate[]']").each(function() {
            if($(this).val() !== '') {
                hasVehicle = true;
                return false;
            }
        });
        
        if(!hasVehicle) {
            $("#vehicleTable").after('<p class="text-danger">Please add at least one vehicle</p>');
            hasError = true;
        }
        
        if(!hasError) {
            this.submit();
        }
    });

    // Preview button click handler
    $('#previewBtn').click(function() {
        var formData = new FormData($('#gpsLetterForm')[0]);
        formData.append('preview', 'true');
        
        $.ajax({
            url: 'php_action/previewLetter.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#previewContent').html(response);
                $('#previewModal').modal('show');
            },
            error: function() {
                alert('Error generating preview');
            }
        });
    });

    // Update print preview function
    window.printPreview = function() {
        const printWindow = window.open('', '', 'width=800,height=600');
        const content = document.getElementById('previewContent').innerHTML;
        const styles = `
            <style>
                .preview-letter { padding: 20px; max-width: 800px; margin: 0 auto; }
                .letter-header { text-align: center; margin-bottom: 20px; }
                .company-logo img { height: 80px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                @media print {
                    body { margin: 0; padding: 15px; }
                    button { display: none; }
                }
            </style>
        `;

        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Letter Preview</title>
                ${styles}
            </head>
            <body>
                <div class="preview-letter">
                    ${content}
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                        window.onfocus = function() { window.close(); }
                    }
                </script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
});