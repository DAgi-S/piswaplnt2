<?php 

require_once 'core.php';

// Validate and sanitize input
$startDate = isset($_POST['startDate']) ? mysqli_real_escape_string($connect, $_POST['startDate']) : '';
$endDate = isset($_POST['endDate']) ? mysqli_real_escape_string($connect, $_POST['endDate']) : '';

try {
	// Convert dates to MySQL format (YYYY-MM-DD)
	$startDate = date('Y-m-d', strtotime($startDate));
	$endDate = date('Y-m-d', strtotime($endDate));

	// Fetch orders for the date range using correct table structure
	$sql = "SELECT o.*, u.username as created_by 
			FROM orders o 
			LEFT JOIN users u ON o.user_id = u.user_id 
			WHERE DATE(o.order_date) BETWEEN ? AND ?
			ORDER BY o.order_date DESC";
			
	$stmt = $connect->prepare($sql);
	$stmt->bind_param("ss", $startDate, $endDate);
	$stmt->execute();
	$result = $stmt->get_result();

	// Add debug information
	error_log("Query executed: " . $sql);
	error_log("Start Date: " . $startDate);
	error_log("End Date: " . $endDate);
	error_log("Number of results: " . $result->num_rows);

	// Fetch company settings
	$settingsQuery = "SELECT setting_key, setting_value FROM company_settings";
	$settingsResult = $connect->query($settingsQuery);
	$settings = [];
	while($row = $settingsResult->fetch_assoc()) {
		$settings[$row['setting_key']] = $row['setting_value'];
	}

	if ($result->num_rows == 0) {
		// No sales found
		$html = generateReportHeader($settings);
		$html .= '<div class="no-sales-message">
					<i class="glyphicon glyphicon-info-sign"></i>
					No sales recorded between ' . date('d/m/Y', strtotime($startDate)) . 
					' and ' . date('d/m/Y', strtotime($endDate)) . '
				 </div>';
		$html .= generateReportFooter($settings);
	} else {
		// Generate report with sales data
		$html = generateReportHeader($settings);
		$html .= generateSalesTable($result);
		$html .= generateReportFooter($settings);
	}

	echo json_encode([
		'success' => true,
		'html' => $html
	]);

} catch (Exception $e) {
	echo json_encode([
		'success' => false,
		'messages' => 'Error generating report',
		'error_details' => $e->getMessage()
	]);
}

function generateReportHeader($settings) {
	return '
	<div class="report-container">
		<div class="report-header">
			<img src="../assets/images/logo.png" alt="Company Logo" class="report-logo">
			<div class="company-info">
				<h2>'.($settings['company_name'] ?? 'Lebawi Net Trading PLC').'</h2>
				<p>TIN: '.($settings['company_tin'] ?? '0073021029').'</p>
				<p>Phone: '.($settings['company_phone'] ?? '+251901000251').'</p>
			</div>
		</div>
		<h3 class="report-title">Sales Report</h3>';
}

function generateSalesTable($result) {
	$html = '
	<table class="table table-bordered">
		<thead>
			<tr>
				<th>Order Date</th>
				<th>FS Number</th>
				<th>Client Name</th>
				<th>Contact</th>
				<th>Sub Total</th>
				<th>VAT</th>
				<th>Grand Total</th>
				<th>Payment Status</th>
			</tr>
		</thead>
		<tbody>';

	$totalAmount = 0;
	$totalVat = 0;
	$grandTotal = 0;

	while ($row = $result->fetch_assoc()) {
		$html .= '<tr>
			<td>' . date('d/m/Y', strtotime($row['order_date'])) . '</td>
			<td>' . htmlspecialchars($row['fsnum']) . '</td>
			<td>' . htmlspecialchars($row['client_name']) . '</td>
			<td>' . htmlspecialchars($row['client_contact']) . '</td>
			<td class="text-right">' . number_format($row['sub_total'], 2) . '</td>
			<td class="text-right">' . number_format($row['vat'], 2) . '</td>
			<td class="text-right">' . number_format($row['grand_total'], 2) . '</td>
			<td>' . htmlspecialchars($row['payment_status']) . '</td>
		</tr>';
		
		$totalAmount += $row['sub_total'];
		$totalVat += $row['vat'];
		$grandTotal += $row['grand_total'];
	}

	$html .= '<tr class="total-row">
			<td colspan="4" class="text-right"><strong>Totals:</strong></td>
			<td class="text-right"><strong>' . number_format($totalAmount, 2) . '</strong></td>
			<td class="text-right"><strong>' . number_format($totalVat, 2) . '</strong></td>
			<td class="text-right"><strong>' . number_format($grandTotal, 2) . '</strong></td>
			<td></td>
		</tr>
	</tbody></table>';

	return $html;
}

function generateReportFooter($settings) {
	return '
		<div class="report-footer">
			<p>Generated on: ' . date('d/m/Y H:i:s') . '</p>
		</div>
	</div>';
}
?>