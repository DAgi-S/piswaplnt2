<?php 	

require_once 'core.php';
require_once 'db_connect.php';

$output = array('data' => array());

$sql = "SELECT * FROM digital_categories ORDER BY category_name ASC";
$result = $connect->query($sql);

if($result->num_rows > 0) {
	while($row = $result->fetch_array()) {
		$categoryId = $row['category_id'];
		
		$button = '
			<div class="btn-group">
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Action <span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
					<li><a href="#" class="edit-category" data-id="'.$categoryId.'"><i class="glyphicon glyphicon-edit"></i> Edit</a></li>
					<li><a href="#" class="remove-category" data-id="'.$categoryId.'"><i class="glyphicon glyphicon-trash"></i> Remove</a></li>
				</ul>
			</div>';

		$output['data'][] = array(
			$row['category_name'],
			$row['description'],
			$row['created_at'],
			$button
		);
	}
}

$connect->close();

echo json_encode($output);