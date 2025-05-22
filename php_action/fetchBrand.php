<?php
require_once 'core.php';

$sql = "SELECT brand_id, name, status FROM brands WHERE status != 2";
$result = $connect->query($sql);

$output = array('data' => array());

if($result->num_rows > 0) {
    while($row = $result->fetch_array()) {
        $brandId = $row[0];
        
        // Status badge
        $status = ($row[2] == 1) ? 
            '<span class="label label-success">Available</span>' : 
            '<span class="label label-danger">Not Available</span>';

        // Create action buttons
        $button = '
        <div class="btn-group">
            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Action <span class="caret"></span>
            </button>
            <ul class="dropdown-menu">
                <li><a type="button" data-toggle="modal" data-target="#editBrandModel" onclick="editBrands('.$brandId.')"> <i class="glyphicon glyphicon-edit"></i> Edit</a></li>
                <li><a type="button" data-toggle="modal" data-target="#removeMemberModal" onclick="removeBrands('.$brandId.')"> <i class="glyphicon glyphicon-trash"></i> Remove</a></li>
            </ul>
        </div>';

        $output['data'][] = array(
            $row[1], // brand name
            $status, // status
            $button  // action buttons
        );
    }
}

$connect->close();
echo json_encode($output);