<?php
require_once '../../php_action/core.php';

$sql = "SELECT * FROM gps_investors ORDER BY name ASC";
$result = $connect->query($sql);

$output = array('data' => array());

if($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        $investorId = $row['id'];

        // Button
        $button = '
            <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Action <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="#" class="editInvestor" data-id="'.$investorId.'"><i class="fas fa-edit"></i> Edit</a></li>
                    <li><a href="#" class="removeInvestor" data-id="'.$investorId.'"><i class="fas fa-trash"></i> Remove</a></li>
                </ul>
            </div>';

        $output['data'][] = array(
            $row['name'],
            number_format($row['share_percentage'], 2) . '%',
            number_format($row['investment'], 2),
            $row['account_type'],
            number_format($row['balance'], 2),
            number_format($row['credit_amount'], 2),
            $button
        );
    }
}

$connect->close();
echo json_encode($output); 