<?php
require_once 'core.php';

$sql = "SELECT * FROM gps_profit ORDER BY date DESC";
$result = $connect->query($sql);

$output = array('data' => array());

if($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        $profitId = $row['id'];

        // Button
        $button = '
            <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Action <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="#" class="editProfit" data-id="'.$profitId.'"><i class="fas fa-edit"></i> Edit</a></li>
                    <li><a href="#" class="removeProfit" data-id="'.$profitId.'"><i class="fas fa-trash"></i> Remove</a></li>
                </ul>
            </div>';

        $output['data'][] = array(
            date('d-m-Y', strtotime($row['date'])),
            number_format($row['total_purchase'], 2),
            number_format($row['total_sales'], 2),
            number_format($row['total_credit'], 2),
            number_format($row['total_profit'], 2),
            number_format($row['total_expenses'], 2),
            $row['comment'],
            $button
        );
    }
}

$connect->close();
echo json_encode($output); 