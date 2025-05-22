$(document).ready(function() {
    // Date picker initialization
    $("#startDate").datepicker({
        dateFormat: 'yy-mm-dd'
    });
    $("#endDate").datepicker({
        dateFormat: 'yy-mm-dd'
    });

    // Form submission
    $("#getDigitalSwapReportForm").unbind('submit').bind('submit', function() {
        var form = $(this);

        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: form.serialize(),
            dataType: 'text',
            success:function(response) {
                var mywindow = window.open('', 'Digital Swap Report', 'height=600,width=800');
                mywindow.document.write('<html><head><title>Digital Swap Report</title>');
                mywindow.document.write('<style>');
                mywindow.document.write('table { width: 100%; border-collapse: collapse; }');
                mywindow.document.write('th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }');
                mywindow.document.write('th { background-color: #f5f5f5; }');
                mywindow.document.write('tfoot th { background-color: #e9ecef; }');
                mywindow.document.write('</style>');
                mywindow.document.write('</head><body>');
                mywindow.document.write('<h2 style="text-align:center;">Digital Swap Report</h2>');
                mywindow.document.write(response);
                mywindow.document.write('</body></html>');
                mywindow.document.close();
                mywindow.focus();
                mywindow.print();
            }
        });
        return false;
    });
});