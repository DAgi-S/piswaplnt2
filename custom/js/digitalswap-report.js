$(document).ready(function() {
    // Date picker initialization
    $("#startDate").datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $("#endDate").datepicker({
        dateFormat: 'yy-mm-dd'
    });

    // Generate Report Button
    $("#getDigitalSwapReportForm").unbind('submit').bind('submit', function(e) {
        e.preventDefault();
        if (validateDates()) {
            generateReport('print');
        }
    });

    // Email Report Button
    $("#emailReportBtn").click(function() {
        if (!validateForm()) {
            return;
        }
        generateReport('email');
    });

    // Validation functions
    function validateDates() {
        var startDate = $("#startDate").val();
        var endDate = $("#endDate").val();

        if (!startDate || !endDate) {
            $("#report-messages").html('<div class="alert alert-warning">'+
                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Both Start Date and End Date are required'+
                '</div>');
            return false;
        }

        // Convert to Date objects for comparison
        var start = new Date(startDate);
        var end = new Date(endDate);

        if (start > end) {
            $("#report-messages").html('<div class="alert alert-warning">'+
                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Start Date cannot be later than End Date'+
                '</div>');
            return false;
        }

        return true;
    }

    function validateEmail(email) {
        var emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
        return emailRegex.test(email);
    }

    function validateForm() {
        if (!validateDates()) {
            return false;
        }

        var email = $("#email").val();
        if (!email) {
            $("#report-messages").html('<div class="alert alert-warning">'+
                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Email address is required for sending report'+
                '</div>');
            return false;
        }

        if (!validateEmail(email)) {
            $("#report-messages").html('<div class="alert alert-warning">'+
                '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Please enter a valid email address'+
                '</div>');
            return false;
        }

        return true;
    }

    function generateReport(action) {
        var form = $("#getDigitalSwapReportForm");
        var formData = form.serialize() + '&action=' + action;

        $.ajax({
            url: 'php_action/getDigitalSwapReport.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (action === 'email') {
                    if (response.success) {
                        $("#report-messages").html('<div class="alert alert-success">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> Report sent successfully'+
                            '</div>');
                    } else {
                        $("#report-messages").html('<div class="alert alert-danger">'+
                            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                            '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ response.message +
                            '</div>');
                    }
                } else {
                    // Print functionality
                    var mywindow = window.open('', 'Digital Swap Report', 'height=800,width=1200');
                    mywindow.document.write('<html><head><title>Digital Swap Report</title>');
                    mywindow.document.write('<style>' + response.styles + '</style></head><body>');
                    mywindow.document.write(response.html);
                    mywindow.document.write('</body></html>');
                    mywindow.document.close();
                    mywindow.focus();
                    setTimeout(function() {
                        mywindow.print();
                        mywindow.close();
                    }, 1000);
                }
            },
            error: function(xhr, status, error) {
                $("#report-messages").html('<div class="alert alert-danger">'+
                    '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
                    '<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> Error generating report: '+ error +
                    '</div>');
            }
        });
    }
});