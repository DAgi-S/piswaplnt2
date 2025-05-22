<?php
require_once 'core.php';

$sql = "SELECT * FROM letter_templates";
$result = $connect->query($sql);

$output = array('data' => array());

if($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        $templateId = $row[0];

        // Action buttons
        $actionButton = '
            <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                    Action <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    <li><a href="javascript:void(0)" onclick="editTemplate('.$row['id'].')"> 
                        <i class="glyphicon glyphicon-edit"></i> Edit</a></li>
                    <li><a href="javascript:void(0)" onclick="removeTemplate('.$row['id'].')"> 
                        <i class="glyphicon glyphicon-trash"></i> Remove</a></li>
                </ul>
            </div>';

        $output['data'][] = array(
            $row['letter_code'],
            $row['letter_for'],
            $row['location'],
            $row['letter_subject'],
            $actionButton
        );
    }
}

$connect->close();
echo json_encode($output); 