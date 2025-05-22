<?php
require_once 'core.php';
require_once 'classes/ProductionAnalytics.php';

// Check if export type is specified
$exportType = isset($_GET['type']) ? $_GET['type'] : 'csv';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 month'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$analytics = new Production\ProductionAnalytics($connect);
$report = $analytics->generateAnalyticsReport($startDate, $endDate);

// Set headers for file download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="production_analytics_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Write UTF-8 BOM
fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Export Efficiency Metrics
fputcsv($output, ['Efficiency Metrics']);
fputcsv($output, ['Metric', 'Value']);
fputcsv($output, ['Total Orders', $report['efficiency_metrics']['efficiency_metrics']['total_orders']]);
fputcsv($output, ['On-Time Completion Rate', number_format($report['efficiency_metrics']['efficiency_metrics']['on_time_completion_rate'], 1) . '%']);
fputcsv($output, ['Completion Rate', number_format($report['efficiency_metrics']['efficiency_metrics']['completion_rate'], 1) . '%']);
fputcsv($output, ['Average Delay', number_format($report['efficiency_metrics']['efficiency_metrics']['avg_delay_minutes'] / 60, 1) . ' hours']);
fputcsv($output, ['Average Production Time', number_format($report['efficiency_metrics']['efficiency_metrics']['avg_production_time'] / 60, 1) . ' hours']);
fputcsv($output, []);

// Export Resource Performance
fputcsv($output, ['Resource Performance']);
fputcsv($output, ['Workstation', 'Total Assignments', 'Efficiency Rating', 'Runtime Hours', 'Operator Count', 'On-Time Rate', 'Utilization Rate']);
foreach ($report['resource_performance'] as $resource) {
    fputcsv($output, [
        $resource['workstation_name'],
        $resource['metrics']['total_assignments'],
        number_format($resource['metrics']['efficiency_rating'], 1) . '%',
        number_format($resource['metrics']['total_runtime_hours'], 1),
        $resource['metrics']['operator_count'],
        number_format($resource['metrics']['on_time_completion_rate'], 1) . '%',
        number_format($resource['metrics']['utilization_rate'], 1) . '%'
    ]);
}
fputcsv($output, []);

// Export Quality Metrics
fputcsv($output, ['Quality Metrics']);
fputcsv($output, ['Product', 'Total Orders', 'Defect Rate', 'Quality Score', 'High Defect Orders']);
foreach ($report['quality_metrics'] as $quality) {
    fputcsv($output, [
        $quality['product_name'],
        $quality['metrics']['total_orders'],
        number_format($quality['metrics']['avg_defect_rate'], 2) . '%',
        number_format($quality['metrics']['avg_quality_score'], 1),
        $quality['metrics']['high_defect_orders']
    ]);
}
fputcsv($output, []);

// Export Production Trends
fputcsv($output, ['Production Trends']);
fputcsv($output, ['Date', 'Completed Orders', 'Total Units', 'Completion Rate', 'On-Time Orders']);
foreach ($report['production_trends'] as $trend) {
    fputcsv($output, [
        $trend['date'],
        $trend['metrics']['completed_orders'],
        $trend['metrics']['total_units'],
        number_format($trend['metrics']['completion_rate'], 1) . '%',
        $trend['metrics']['on_time_orders']
    ]);
}
fputcsv($output, []);

// Export Bottleneck Analysis
fputcsv($output, ['Bottleneck Analysis']);
fputcsv($output, ['Workstation', 'Total Orders', 'Delayed Orders', 'Avg Processing Time', 'Avg Delay', 'Utilization Rate']);
foreach ($report['bottleneck_analysis'] as $bottleneck) {
    fputcsv($output, [
        $bottleneck['workstation_name'],
        $bottleneck['metrics']['total_orders'],
        $bottleneck['metrics']['delayed_orders'],
        number_format($bottleneck['metrics']['avg_processing_time'], 1) . ' hours',
        number_format($bottleneck['metrics']['avg_delay_hours'], 1) . ' hours',
        number_format($bottleneck['metrics']['utilization_rate'], 1) . '%'
    ]);
}

fclose($output); 