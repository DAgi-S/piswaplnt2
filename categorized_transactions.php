<?php
require_once 'php_action/db_connect.php';
require_once 'includes/header.php';
require_once 'php_action/core.php';

$title = "Categorized Transactions";

// Fetch categories for dropdown
$sql = "SELECT * FROM digital_categories ORDER BY category_name ASC";
$result = $connect->query($sql);
$categories = array();
while($row = $result->fetch_assoc()) {
    $categories[] = $row;
}
?>

<!-- Add DataTables CSS -->
<link rel="stylesheet" href="/pistocklnt/assests/plugins/datatables/jquery.dataTables.min.css">
<link rel="stylesheet" href="/pistocklnt/assests/plugins/datatables/buttons.dataTables.min.css">
<link rel="stylesheet" href="/pistocklnt/assests/plugins/select2/select2.min.css">

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="digitalswap.php">Digital Swap</a></li>
            <li class="active">Categorized Transactions</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"><i class="glyphicon glyphicon-th-list"></i> Categorized Transactions</div>
            </div>
            <div class="panel-body">
                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="categoryFilter">Filter by Category:</label>
                            <select class="form-control" id="categoryFilter">
                                <option value="">All Categories</option>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo $category['category_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="searchInput">Search:</label>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search transactions...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="pull-right" style="margin-top: 24px;">
                            <button class="btn btn-primary" id="bulkAssignCategory">
                                <i class="glyphicon glyphicon-tags"></i> Auto-Assign Categories
                            </button>
                            <button class="btn btn-success" id="assignSelectedBtn" disabled>
                                <i class="glyphicon glyphicon-tags"></i> Assign Selected
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <table class="table" id="categorizedTransactionsTable">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Name</th>
                                    <th>Platform</th>
                                    <th>Amount</th>
                                    <th>Category</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assign Category Modal -->
<div class="modal fade" id="assignCategoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="assignCategoryForm" action="php_action/assignCategory.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-tags"></i> Assign Category</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Category</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="categorySelect" name="categoryId" required>
                                <option value="">Select Category</option>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>">
                                        <?php echo $category['category_name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="transactionId" id="transactionId" />
                    <input type="hidden" name="isMultiple" id="isMultiple" value="0" />
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add DataTables JS -->
<script src="/pistocklnt/assests/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="/pistocklnt/assests/plugins/datatables/dataTables.buttons.min.js"></script>
<script src="/pistocklnt/assests/plugins/datatables/buttons.flash.min.js"></script>
<script src="/pistocklnt/assests/plugins/datatables/jszip.min.js"></script>
<script src="/pistocklnt/assests/plugins/datatables/pdfmake.min.js"></script>
<script src="/pistocklnt/assests/plugins/datatables/vfs_fonts.js"></script>
<script src="/pistocklnt/assests/plugins/datatables/buttons.html5.min.js"></script>
<script src="/pistocklnt/assests/plugins/datatables/buttons.print.min.js"></script>
<script src="/pistocklnt/assests/plugins/select2/select2.min.js"></script>

<script src="custom/js/categorized-transactions.js"></script> 