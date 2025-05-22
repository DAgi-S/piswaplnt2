<?php 
require_once 'includes/header.php'; 
require_once 'php_action/core.php';
require_once 'php_action/middleware.php';
// Fetch all generated letters with proper joins
$sql = "SELECT gl.*, lt.letter_subject, lt.letter_for, 
        GROUP_CONCAT(lv.plate_number) as vehicles 
        FROM generated_letters gl
        LEFT JOIN letter_templates lt ON gl.template_id = lt.id
        LEFT JOIN letter_vehicles lv ON gl.id = lv.letter_id
        GROUP BY gl.id
        ORDER BY gl.created_at DESC";

$result = $connect->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generated Letters</title>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.dataTables.min.css">
</head>
<body>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Manage Generated Letters</h3>
                </div>
                <div class="panel-body">
                    <table id="manageGeneratedLettersTable" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reference No</th>
                                <th>Client Name</th>
                                <th>Template</th>
                                <th>Vehicles</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($row['created_at'])); ?></td>
                                <td>GPS/<?php echo $row['fs_number']; ?>/<?php echo date('Y', strtotime($row['created_at'])); ?></td>
                                <td><?php echo $row['client_name']; ?></td>
                                <td><?php echo $row['letter_for']; ?></td>
                                <td><?php echo $row['vehicles']; ?></td>
                                <td>
                                    <a href="viewLetter.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">View</a>
                                    <a href="php_action/printLetter.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Print</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Required Scripts -->
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#manageGeneratedLettersTable').DataTable({
                "order": [[ 0, "desc" ]],
                "pageLength": 10,
                "language": {
                    "emptyTable": "No letters generated yet"
                }
            });
        });
    </script>
</body>
</html> 