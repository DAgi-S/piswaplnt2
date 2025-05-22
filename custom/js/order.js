var manageOrderTable;

$(document).ready(function() {
	$("#paymentPlace").change(function(){
		if($("#paymentPlace").val() == 2)
		{
			$(".gst").text("IGST 18%");
		}
		else
		{
			$(".gst").text("GST 18%");	
		}
	});

	var divRequest = $(".div-request").text();

	// top nav bar 
	$("#navOrder").addClass('active');

	if(divRequest == 'add')  {
		// add order	
		// top nav child bar 
		$('#topNavAddOrder').addClass('active');	

		// order date picker
		$("#orderDate").datepicker({
			dateFormat: 'yy-mm-dd',
			autoclose: true,
			todayHighlight: true
		});

		// create order form function
		$("#createOrderForm").unbind('submit').bind('submit', function(e) {
			e.preventDefault();
			var form = $(this);

			$('.form-group').removeClass('has-error').removeClass('has-success');
			$('.text-danger').remove();

			var orderDate = $("#orderDate").val();
			var clientName = $("#clientName").val();
			var clientContact = $("#clientContact").val();
			var clientTin = $("#clientTin").val();
			var paymentType = $("#paymentType").val();
			
			// Basic validation
			if(!orderDate || !clientName || !clientContact || !clientTin || !paymentType) {
				alert('Please fill in all required fields');
				return false;
			}

			// Form validation
			var productName = document.getElementsByName('productName[]');
			var quantity = document.getElementsByName('quantity[]');
			var hasValidProduct = false;

			// Check if we have at least one valid product with quantity
			for (var x = 0; x < productName.length; x++) {
				if(productName[x].value !== '' && quantity[x].value !== '' && quantity[x].value > 0) {
					hasValidProduct = true;
					break;
				}
			}

			if(!hasValidProduct) {
				alert('Please add at least one product with valid quantity');
				$("#createOrderBtn").button('reset');
				return false;
			}

			// Show loading state
			$("#createOrderBtn").button('loading');

			// Continue with the AJAX call if validation passes
			if(orderDate && clientName && clientContact && clientTin && paymentType) {
				$.ajax({
					url: form.attr('action'),
					type: form.attr('method'),
					data: form.serialize(),
					dataType: 'json',
					success: function(response) {
						console.log('Server Response:', response);
						$("#createOrderBtn").button('reset');

						if(response.success === true) {
							$(".success-messages").html(
								'<div class="alert alert-success">' +
								'<button type="button" class="close" data-dismiss="alert">&times;</button>' +
								'<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> ' + 
								response.messages + 
								' <br><br><a type="button" onclick="printOrder(' + response.order_id + 
								')" class="btn btn-primary"><i class="glyphicon glyphicon-print"></i> Print</a>' +
								'</div>'
							);

							// Reset form
							$("#createOrderForm")[0].reset();
							$(".item-row").remove();
						} else {
							// Show error message in a proper alert
							$(".success-messages").html(
								'<div class="alert alert-danger">'+
								'<button type="button" class="close" data-dismiss="alert">&times;</button>'+
								'<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ 
								response.messages +
								'</div>'
							);
						}
					},
					error: function(xhr, status, error) {
						console.error('Ajax Error:', error);
						$("#createOrderBtn").button('reset');
						
						// Show error message in a proper alert
						$(".success-messages").html(
							'<div class="alert alert-danger">'+
							'<button type="button" class="close" data-dismiss="alert">&times;</button>'+
							'<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ 
							'Failed to save order. Please check your permissions or try again later.'+
							'</div>'
						);
					}
				});
			} else {
				alert('Please fill in all required fields');
			}

			return false;
		}); // /create order form function	
	
	} else if(divRequest == 'manord') {
		// top nav child bar 
		$('#topNavManageOrder').addClass('active');

		manageOrderTable = $("#manageOrderTable").DataTable({
			'ajax': {
				'url': 'php_action/fetchOrder.php',
				'error': function(xhr, error, thrown) {
					console.error('DataTable Error:', error);
					if (xhr.responseJSON && xhr.responseJSON.messages) {
						$(".success-messages").html(
							'<div class="alert alert-danger">'+
							'<button type="button" class="close" data-dismiss="alert">&times;</button>'+
							'<strong><i class="glyphicon glyphicon-exclamation-sign"></i></strong> '+ 
							xhr.responseJSON.messages +
							'</div>'
						);
					}
				}
			},
			'order': []
		});		
					
	} else if(divRequest == 'editOrd') {
		$("#orderDate").datepicker();

		// edit order form function
		$("#editOrderForm").unbind('submit').bind('submit', function() {
			// alert('ok');
			var form = $(this);

			$('.form-group').removeClass('has-error').removeClass('has-success');
			$('.text-danger').remove();
				
			var orderDate = $("#orderDate").val();
			var clientName = $("#clientName").val();
			var clientContact = $("#clientContact").val();
			
			var clientTin = $("#clientTin").val();
			var paymentType = $("#paymentType").val();

			// form validation 
			if(orderDate == "") {
				$("#orderDate").after('<p class="text-danger"> The Order Date field is required </p>');
				$('#orderDate').closest('.form-group').addClass('has-error');
			} else {
				$('#orderDate').closest('.form-group').addClass('has-success');
			} // /else
			if(clientTin == "") {
				$("#clientTin").after('<p class="text-danger"> The Client Tin field is required </p>');
				$('#clientTin').closest('.form-group').addClass('has-error');
			} else {
				$('#orderDate').closest('.form-group').addClass('has-success');
			} // /else
			if(clientName == "") {
				$("#clientName").after('<p class="text-danger"> The Client Name field is required </p>');
				$('#clientName').closest('.form-group').addClass('has-error');
			} else {
				$('#clientName').closest('.form-group').addClass('has-success');
			} // /else

			if(clientContact == "") {
				$("#clientContact").after('<p class="text-danger"> The Contact field is required </p>');
				$('#clientContact').closest('.form-group').addClass('has-error');
			} else {
				$('#clientContact').closest('.form-group').addClass('has-success');
			} // /else

			if(paymentType == "") {
				$("#paymentType").after('<p class="text-danger"> The Payment Type field is required </p>');
				$('#paymentType').closest('.form-group').addClass('has-error');
			} else {
				$('#paymentType').closest('.form-group').addClass('has-success');
			} // /else

			// array validation
			var productName = document.getElementsByName('productName[]');				
			var validateProduct;
			for (var x = 0; x < productName.length; x++) {       			
				var productNameId = productName[x].id;	    	
		    if(productName[x].value == ''){	    		    	
		    	$("#"+productNameId+"").after('<p class="text-danger"> Product Name Field is required!! </p>');
		    	$("#"+productNameId+"").closest('.form-group').addClass('has-error');	    		    	    	
	      } else {      	
		    	$("#"+productNameId+"").closest('.form-group').addClass('has-success');	    		    		    	
	      }          
	   	} // for

	   	for (var x = 0; x < productName.length; x++) {       						
		    if(productName[x].value){	    		    		    	
		    	validateProduct = true;
	      } else {      	
		    	validateProduct = false;
	      }          
	   	} // for       		   	
	   	
	   	var quantity = document.getElementsByName('quantity[]');		   	
	   	var validateQuantity;
	   	for (var x = 0; x < quantity.length; x++) {       
	 			var quantityId = quantity[x].id;
		    if(quantity[x].value == ''){	    	
		    	$("#"+quantityId+"").after('<p class="text-danger"> Product Name Field is required!! </p>');
		    	$("#"+quantityId+"").closest('.form-group').addClass('has-error');	    		    		    	
	      } else {      	
		    	$("#"+quantityId+"").closest('.form-group').addClass('has-success');	    		    		    		    	
	      } 
	   	}  // for

	   	for (var x = 0; x < quantity.length; x++) {       						
		    if(quantity[x].value){	    		    		    	
		    	validateQuantity = true;
	      } else {      	
		    	validateQuantity = false;
	      }          
	   	} // for       	
	   	

			if(orderDate && clientName && clientContact && clientTin && paymentType) {
				if(validateProduct == true && validateQuantity == true) {
					// Show loading state
					$("#createOrderBtn").button('loading');

					$.ajax({
						url: form.attr('action'),
						type: form.attr('method'),
						data: form.serialize(),
						dataType: 'json',
						success: function(response) {
							console.log('Response:', response); // Debug log
							
							// Reset button state
							$("#createOrderBtn").button('reset');
							
							$(".text-danger").remove();
							$('.form-group').removeClass('has-error').removeClass('has-success');

							if(response.success == true) {
								// Show success message
								$(".success-messages").html('<div class="alert alert-success">'+
									'<button type="button" class="close" data-dismiss="alert">&times;</button>'+
									'<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
									' <br /> <br /> <a type="button" onclick="printOrder('+response.order_id+')" class="btn btn-primary"> <i class="glyphicon glyphicon-print"></i> Print </a>'+
									'<a href="orders.php?o=add" class="btn btn-default" style="margin-left:10px;"> <i class="glyphicon glyphicon-plus-sign"></i> Add New Order </a>'+
								'</div>');
								
								// Scroll to top
								$("html, body, div.panel, div.pane-body").animate({scrollTop: '0px'}, 100);

								// Disable buttons
								$(".submitButtonFooter").addClass('div-hide');
								$(".removeProductRowBtn").addClass('div-hide');
							} else {
								alert(response.messages);
							}
						},
						error: function(xhr, status, error) {
							console.error('Error:', error); // Debug log
							$("#createOrderBtn").button('reset');
							alert('Failed to save order. Please try again.');
						}
					});
				} else {
					alert('Please add at least one product and quantity');
				}
			} else {
				alert('Please fill in all required fields');
			}
			

			return false;
		}); // /create order form function	
	} 	

	// Initialize date picker
	$("#orderDate").datepicker({
		dateFormat: 'yy-mm-dd'
	});

	// Set today's date
	var today = new Date();
	var dd = String(today.getDate()).padStart(2, '0');
	var mm = String(today.getMonth() + 1).padStart(2, '0');
	var yyyy = today.getFullYear();
	today = yyyy + '-' + mm + '-' + dd;
	$('#orderDate').val(today);

	// Add withholding tax checkbox handler
	$("#withholding_enabled").change(function() {
		if($(this).is(":checked")) {
			$(".withholding-amount").show();
		} else {
			$(".withholding-amount").hide();
		}
		calculateTotal();
	});

}); // /documernt


// print order function
function printOrder(orderId = null) {
	if(orderId) {
		$.ajax({
			url: 'php_action/printOrder.php',
			type: 'post',
			data: {orderId: orderId},
			dataType: 'text',
			success:function(response) {
				var mywindow = window.open('', 'Stock Management System', 'height=800,width=900');
				mywindow.document.write('<html><head><title>Order Invoice</title>');
				mywindow.document.write('</head><body>');
				mywindow.document.write(response);
				mywindow.document.write('</body></html>');
				
				mywindow.document.close();
				mywindow.focus();
				
				// Wait for images to load
				var images = mywindow.document.getElementsByTagName('img');
				var loaded = 0;
				
				function tryPrint() {
					if (loaded === images.length) {
						mywindow.print();
						setTimeout(function() {
							mywindow.close();
						}, 500);
					}
				}

				if (images.length > 0) {
					for (var i = 0; i < images.length; i++) {
						images[i].onload = function() {
							loaded++;
							tryPrint();
						};
						images[i].onerror = function() {
							loaded++;
							tryPrint();
						};
					}
					// Fallback if images fail to trigger events
					setTimeout(function() {
						mywindow.print();
						mywindow.close();
					}, 3000);
				} else {
					mywindow.print();
					mywindow.close();
				}
			}
		});
	}
}

// Add row function
function addRow() {
	var tableLength = $("#productTable tbody tr").length;
	var tableRow;
	var arrayNumber;
	var count;

	if(tableLength > 0) {
		tableRow = $("#productTable tbody tr:last").attr('id');
		arrayNumber = $("#productTable tbody tr:last").attr('class');
		count = tableRow.substring(3);
		count = Number(count) + 1;
		arrayNumber = Number(arrayNumber) + 1;
	} else {
		count = 1;
		arrayNumber = 0;
	}

	var tr = '<tr id="row'+count+'" class="'+arrayNumber+'">'+
		'<td>'+
			'<div class="form-group">'+
			'<select class="form-control" name="productName[]" id="productName'+count+'" onchange="getProductData('+count+')" >'+
				'<option value="">~~SELECT~~</option>'+
				'<?php echo $productList; ?>'+
			'</select>'+
			'</div>'+
		'</td>'+
		'<td style="padding-left:20px;">'+
			'<input type="text" name="rate[]" id="rate'+count+'" autocomplete="off" class="form-control" disabled="true" />'+
			'<input type="hidden" name="rateValue[]" id="rateValue'+count+'" autocomplete="off" class="form-control" />'+
		'</td>'+
		'<td style="padding-left:20px;">'+
			'<div class="form-group">'+
				'<p id="available_quantity'+count+'"></p>'+
			'</div>'+
		'</td>'+
		'<td style="padding-left:20px;">'+
			'<div class="form-group">'+
				'<input type="number" name="quantity[]" id="quantity'+count+'" onkeyup="getTotal('+count+')" autocomplete="off" class="form-control" min="1" />'+
			'</div>'+
		'</td>'+
		'<td style="padding-left:20px;">'+
			'<input type="text" name="total[]" id="total'+count+'" autocomplete="off" class="form-control" disabled="true" />'+
			'<input type="hidden" name="totalValue[]" id="totalValue'+count+'" autocomplete="off" class="form-control" />'+
		'</td>'+
		'<td>'+
			'<button class="btn btn-default removeProductRowBtn" type="button" onclick="removeProductRow('+count+')"><i class="glyphicon glyphicon-trash"></i></button>'+
		'</td>'+
	'</tr>';

	if(tableLength > 0) {
		$("#productTable tbody tr:last").after(tr);
	} else {
		$("#productTable tbody").append(tr);
	}
}

// Remove row function
function removeProductRow(row = null) {
	if(row) {
		$("#row"+row).remove();
		calculateSubTotal();
	} else {
		alert('error! Refresh the page again');
	}
}

// select on product data
function getProductData(row = null) {
	if(row) {
		var productId = $("#productName" + row).val();      
		
		if(productId == "") {
			$("#rate" + row).val("");
			$("#quantity" + row).val("");
			$("#total" + row).val("");
			$("#available_quantity" + row).text("");
		} else {
			$.ajax({
				url: 'php_action/fetchSelectedProduct.php',
				type: 'post',
				data: {productId: productId},
				dataType: 'json',
				success: function(response) {
					console.log(response); // For debugging
					
					// Update price
					$("#rate" + row).val(response.price);
					$("#rateValue" + row).val(response.price);
					
					// Update available quantity
					$("#available_quantity" + row).text(response.quantity);
					
					// Set default quantity to 1
					$("#quantity" + row).val(1);
					
					// Calculate total
					var total = Number(response.price) * 1;
					total = total.toFixed(2);
					$("#total" + row).val(total);
					$("#totalValue" + row).val(total);
					
					calculateSubTotal();
				},
				error: function(xhr, status, error) {
					console.error("Error:", error);
					alert("Error fetching product data");
				}
			});
		}
	}
}

function calculateSubTotal() {
	var tableProductLength = $("#productTable tbody tr").length;
	var totalSubAmount = 0;
	
	for(var x = 0; x < tableProductLength; x++) {
		var tr = $("#productTable tbody tr")[x];
		var count = $(tr).attr('id');
		count = count.substring(3);
		
		totalSubAmount = Number(totalSubAmount) + Number($("#total"+count).val());
	}

	// Sub total
	$("#subTotal").val(totalSubAmount.toFixed(2));
	$("#subTotalValue").val(totalSubAmount.toFixed(2));

	// VAT 15%
	var vat = (totalSubAmount * 0.15).toFixed(2);
	$("#vat").val(vat);
	$("#vatValue").val(vat);

	// Grand Total
	var grandTotal = Number(totalSubAmount) + Number(vat);
	$("#grandTotal").val(grandTotal.toFixed(2));
	$("#grandTotalValue").val(grandTotal.toFixed(2));
}

// table total
function getTotal(row = null) {
	if(row) {
		var rate = Number($("#rate"+row).val());
		var quantity = Number($("#quantity"+row).val());
		
		var total = rate * quantity;
		total = total.toFixed(2);
		$("#total"+row).val(total);
		$("#totalValue"+row).val(total);
		
		subAmount();
	} else {
		alert('no row !! please refresh the page');
	}
}

function subAmount() {
	var tableProductLength = $("#productTable tbody tr").length;
	var totalSubAmount = 0;
	for(x = 0; x < tableProductLength; x++) {
		var tr = $("#productTable tbody tr")[x];
		var count = $(tr).attr('id');
		count = count.substring(3);

		totalSubAmount = Number(totalSubAmount) + Number($("#total"+count).val());
	} // /for

	totalSubAmount = totalSubAmount.toFixed(2);

	// sub total
	$("#subTotal").val(totalSubAmount);
	$("#subTotalValue").val(totalSubAmount);

	// vat
	var vat = (Number($("#subTotal").val())/100) * 15;
	vat = vat.toFixed(2);
	$("#vat").val(vat);
	$("#vatValue").val(vat);

	// total amount
	var totalAmount = (Number($("#subTotal").val()) + Number($("#vat").val()));
	totalAmount = totalAmount.toFixed(2);
	$("#totalAmount").val(totalAmount);
	$("#totalAmountValue").val(totalAmount);

	var discount = $("#discount").val();
	if(discount) {
		var grandTotal = Number($("#totalAmount").val()) - Number(discount);
		grandTotal = grandTotal.toFixed(2);
		$("#grandTotal").val(grandTotal);
		$("#grandTotalValue").val(grandTotal);
	} else {
		$("#grandTotal").val(totalAmount);
		$("#grandTotalValue").val(totalAmount);
	} // /else discount	

	var paidAmount = $("#paid").val();
	if(paidAmount) {
		paidAmount =  Number($("#grandTotal").val()) - Number(paidAmount);
		paidAmount = paidAmount.toFixed(2);
		$("#due").val(paidAmount);
		$("#dueValue").val(paidAmount);
	} else {	
		$("#due").val($("#grandTotal").val());
		$("#dueValue").val($("#grandTotal").val());
	} // else

} // /sub total amount

function discountFunc() {
	var discount = $("#discount").val();
 	var totalAmount = Number($("#totalAmount").val());
 	totalAmount = totalAmount.toFixed(2);

 	var grandTotal;
 	if(totalAmount) { 	
 		grandTotal = Number($("#totalAmount").val()) - Number($("#discount").val());
 		grandTotal = grandTotal.toFixed(2);

 		$("#grandTotal").val(grandTotal);
 		$("#grandTotalValue").val(grandTotal);
 	} else {
 	}

 	var paid = $("#paid").val();

 	var dueAmount; 	
 	if(paid) {
 		dueAmount = Number($("#grandTotal").val()) - Number($("#paid").val());
 		dueAmount = dueAmount.toFixed(2);

 		$("#due").val(dueAmount);
 		$("#dueValue").val(dueAmount);
 	} else {
 		$("#due").val($("#grandTotal").val());
 		$("#dueValue").val($("#grandTotal").val());
 	}

} // /discount function

function paidAmount() {
	var grandTotal = $("#grandTotal").val();

	if(grandTotal) {
		var dueAmount = Number($("#grandTotal").val()) - Number($("#paid").val());
		dueAmount = dueAmount.toFixed(2);
		$("#due").val(dueAmount);
		$("#dueValue").val(dueAmount);
	} // /if
} // /paid amoutn function


function resetOrderForm() {
	// reset the input field
	$("#createOrderForm")[0].reset();
	// remove remove text danger
	$(".text-danger").remove();
	// remove form group error 
	$(".form-group").removeClass('has-success').removeClass('has-error');
} // /reset order form


// remove order from server
function removeOrder(orderId = null) {
	if(orderId) {
		$("#removeOrderBtn").unbind('click').bind('click', function() {
			$("#removeOrderBtn").button('loading');

			$.ajax({
				url: 'php_action/removeOrder.php',
				type: 'post',
				data: {orderId : orderId},
				dataType: 'json',
				success:function(response) {
					$("#removeOrderBtn").button('reset');

					if(response.success == true) {

						manageOrderTable.ajax.reload(null, false);
						// hide modal
						$("#removeOrderModal").modal('hide');
						// success messages
						$("#success-messages").html('<div class="alert alert-success">'+
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
						// error messages
						$(".removeOrderMessages").html('<div class="alert alert-warning">'+
	            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
	            '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
	          '</div>');

						// remove the mesages
	          $(".alert-success").delay(500).show(10, function() {
							$(this).delay(3000).hide(10, function() {
								$(this).remove();
							});
						}); // /.alert	          
					} // /else

				} // /success
			});  // /ajax function to remove the order

		}); // /remove order button clicked
		

	} else {
		alert('error! refresh the page again');
	}
}
// /remove order from server

// Payment ORDER
function paymentOrder(orderId = null) {
	if(orderId) {

		$("#orderDate").datepicker();

		$.ajax({
			url: 'php_action/fetchOrderData.php',
			type: 'post',
			data: {orderId: orderId},
			dataType: 'json',
			success:function(response) {				

				// due 
				$("#due").val(response.order[10]);				

				// pay amount 
				$("#payAmount").val(response.order[10]);

				var paidAmount = response.order[9] 
				var dueAmount = response.order[10];							
				var grandTotal = response.order[8];

				// update payment
				$("#updatePaymentOrderBtn").unbind('click').bind('click', function() {
					var payAmount = $("#payAmount").val();
					var paymentType = $("#paymentType").val();
					var paymentStatus = $("#paymentStatus").val();

					if(payAmount == "") {
						$("#payAmount").after('<p class="text-danger">The Pay Amount field is required</p>');
						$("#payAmount").closest('.form-group').addClass('has-error');
					} else {
						$("#payAmount").closest('.form-group').addClass('has-success');
					}

					if(paymentType == "") {
						$("#paymentType").after('<p class="text-danger">The Pay Amount field is required</p>');
						$("#paymentType").closest('.form-group').addClass('has-error');
					} else {
						$("#paymentType").closest('.form-group').addClass('has-success');
					}

					if(paymentStatus == "") {
						$("#paymentStatus").after('<p class="text-danger">The Pay Amount field is required</p>');
						$("#paymentStatus").closest('.form-group').addClass('has-error');
					} else {
						$("#paymentStatus").closest('.form-group').addClass('has-success');
					}

					if(payAmount && paymentType && paymentStatus) {
						$("#updatePaymentOrderBtn").button('loading');
						$.ajax({
							url: 'php_action/editPayment.php',
							type: 'post',
							data: {
								orderId: orderId,
								payAmount: payAmount,
								paymentType: paymentType,
								paymentStatus: paymentStatus,
								paidAmount: paidAmount,
								grandTotal: grandTotal
							},
							dataType: 'json',
							success:function(response) {
								$("#updatePaymentOrderBtn").button('loading');

								// remove error
								$('.text-danger').remove();
								$('.form-group').removeClass('has-error').removeClass('has-success');

								$("#paymentOrderModal").modal('hide');

								$("#success-messages").html('<div class="alert alert-success">'+
			            '<button type="button" class="close" data-dismiss="alert">&times;</button>'+
			            '<strong><i class="glyphicon glyphicon-ok-sign"></i></strong> '+ response.messages +
			          '</div>');

								// remove the mesages
			          $(".alert-success").delay(500).show(10, function() {
									$(this).delay(3000).hide(10, function() {
										$(this).remove();
									});
								}); // /.alert	

			          // refresh the manage order table
								manageOrderTable.ajax.reload(null, false);

							} //

						});
					} // /if
						
					return false;
				}); // /update payment			

			} // /success
		}); // fetch order data
	} else {
		alert('Error ! Refresh the page again');
	}
}

// Add this after the product selection handler
function onRateChange(row) {
	var rate = Number($("#rate"+row).val());
	var quantity = Number($("#quantity"+row).val());
	
	// Update both rate fields
	$("#rate"+row).val(rate.toFixed(2));
	$("#rateValue"+row).val(rate.toFixed(2));
	
	var total = rate * quantity;
	total = total.toFixed(2);
	
	$("#total"+row).val(total);
	$("#totalValue"+row).val(total);
	
	subAmount();
}

function calculateTotal() {
	var subTotal = 0;
	var total = 0;
	var vat = 0;
	var withholding = 0;
	var grandTotal = 0;

	// Calculate subtotal
	$('input[name="total[]"]').each(function() {
		if($(this).val() != '') {
			subTotal += parseFloat($(this).val());
		}
	});

	// Calculate VAT (15%)
	vat = subTotal * 0.15;

	// Calculate withholding tax if enabled (2% of subtotal)
	if($("#withholding_enabled").is(":checked")) {
		withholding = subTotal * 0.02;
		$("#withholding_amount").val(withholding.toFixed(2));
		$("#withholdingValue").val(withholding.toFixed(2));
		$(".withholding-amount").show();
	} else {
		withholding = 0;
		$("#withholding_amount").val('0.00');
		$("#withholdingValue").val('0.00');
		$(".withholding-amount").hide();
	}

	// Calculate grand total (subtotal + VAT - withholding)
	grandTotal = subTotal + vat - withholding;

	// Update form fields
	$("#subTotal").val(subTotal.toFixed(2));
	$("#subTotalValue").val(subTotal.toFixed(2));
	
	$("#vat").val(vat.toFixed(2));
	$("#vatValue").val(vat.toFixed(2));
	
	$("#totalAmount").val((subTotal + vat).toFixed(2));
	$("#totalAmountValue").val((subTotal + vat).toFixed(2));
	
	$("#grandTotal").val(grandTotal.toFixed(2));
	$("#grandTotalValue").val(grandTotal.toFixed(2));
}

// Update other calculation functions to use the new calculateTotal
function getTotal(row = null) {
	if(row) {
		var total = Number($("#rate"+row).val()) * Number($("#quantity"+row).val());
		total = total.toFixed(2);
		$("#total"+row).val(total);
		$("#totalValue"+row).val(total);
	}
	calculateTotal();
}
