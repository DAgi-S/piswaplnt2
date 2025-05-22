$(document).ready(function() {
	// order date picker
	$("#startDate").datepicker();
	// order date picker
	$("#endDate").datepicker();

	$("#getOrderReportForm").unbind('submit').bind('submit', function() {
		
		var startDate = $("#startDate").val();
		var endDate = $("#endDate").val();

		if(startDate == "" || endDate == "") {
			if(startDate == "") {
				$("#startDate").closest('.form-group').addClass('has-error');
				$("#startDate").after('<p class="text-danger">The Start Date is required</p>');
			} else {
				$(".form-group").removeClass('has-error');
				$(".text-danger").remove();
			}

			if(endDate == "") {
				$("#endDate").closest('.form-group').addClass('has-error');
				$("#endDate").after('<p class="text-danger">The End Date is required</p>');
			} else {
				$(".form-group").removeClass('has-error');
				$(".text-danger").remove();
			}
		} else {
			$(".form-group").removeClass('has-error');
			$(".text-danger").remove();

			var form = $(this);

			$.ajax({
				url: form.attr('action'),
				type: form.attr('method'),
				data: form.serialize(),
				dataType: 'text',
				success:function(response) {
					var mywindow = window.open('', 'Stock Management System', 'height=800,width=1200');
	        mywindow.document.write('<html><head><title>Sales Report</title>');        
	        mywindow.document.write('<style>');
	        mywindow.document.write(`
	            body { 
	                font-family: Arial, sans-serif; 
	                margin: 20px;
	                padding: 20px;
	                min-height: 100vh;
	                position: relative;
	                padding-bottom: 200px;
	            }
	            .header {
	                display: flex;
	                align-items: flex-start;
	                gap: 20px;
	                margin-bottom: 30px;
	            }
	            .logo {
	                max-width: 300px;
	            }
	            .company-details {
	                flex-grow: 1;
	            }
	            .report-info {
	                margin: 20px 0;
	            }
	            table {
	                width: 100%;
	                border-collapse: collapse;
	                margin: 20px 0;
	                font-size: 14px;
	            }
	            th, td {
	                border: 1px solid #000;
	                padding: 6px;
	                text-align: left;
	            }
	            .totals-table {
	                width: auto;
	                margin-left: auto;
	                border: none;
	            }
	            .totals-table td {
	                border: none;
	                text-align: right;
	            }
	            .website {
	                text-align: left;
	                margin-top: 30px;
	            }
	            .footer {
	                position: absolute;
	                bottom: 0;
	                left: 0;
	                width: 100%;
	                text-align: center;
	                padding: 20px 0;
	            }
	            .footer img {
	                max-width: 100%;
	                height: auto;
	            }
	        `);
	        mywindow.document.write('</style></head><body>');
	        mywindow.document.write(response);
	        mywindow.document.write('</body></html>');

	        mywindow.document.close();
	        mywindow.focus();

	        setTimeout(function() {
	            mywindow.print();
	            mywindow.close();
	        }, 2000);

				} // /success
			});	// /ajax

		} // /else

		return false;
	});

});