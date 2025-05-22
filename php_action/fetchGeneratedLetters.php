<?php
require_once 'core.php';

$sql = "SELECT gl.*, lt.letter_code as template_name,
        (SELECT COUNT(*) FROM letter_vehicles WHERE letter_id = gl.id) as vehicle_count
        FROM generated_letters gl
        LEFT JOIN letter_templates lt ON gl.template_id = lt.id
        ORDER BY gl.generated_date DESC";

$result = $connect->query($sql);
$output = array('data' => array());

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $output['data'][] = array(
            'id' => $row['id'],
            'generated_date' => date('d M Y', strtotime($row['generated_date'])),
            'reference_no' => 'GPS/'.$row['fs_number'].'/'.date('Y', strtotime($row['generated_date'])),
            'client_name' => $row['client_name'],
            'template_name' => $row['template_name'],
            'vehicle_count' => $row['vehicle_count']
        );
    }
}

echo json_encode($output);
$connect->close(); 