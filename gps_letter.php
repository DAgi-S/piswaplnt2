<?php 
require_once 'includes/header.php'; 
require_once 'php_action/core.php';
require_once 'php_action/middleware.php';
require_once 'php_action/checkPagePermission.php';

// Check page permission
if (!isset($_SESSION['roleId']) || ($_SESSION['roleId'] !== 2 && !hasPermission('view_letter'))) {
    header('location: access_denied.php');
    exit();
}
$title = "GPS Letter Management";

?>

<link rel="stylesheet" href="custom/css/gps_letter.css">

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">GPS Letter Generator</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"><i class="glyphicon glyphicon-file"></i> GPS Letter Generator</div>
            </div>
            <div class="panel-body">
                <form id="gpsLetterForm" action="php_action/generateLetter.php" method="POST">
                    <!-- Template Selection -->
                    <div class="form-section">
                        <h4><i class="glyphicon glyphicon-list-alt"></i> Letter Template</h4>
                        <div class="form-group">
                            <select class="form-control select2" id="templateId" name="templateId" required>
                                <option value="">Select Template</option>
                            </select>
                        </div>
                    </div>

                    <!-- Client Information -->
                    <div class="form-section">
                        <h4><i class="glyphicon glyphicon-user"></i> Client Information</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Client Name *</label>
                                    <input type="text" class="form-control" id="clientName" name="clientName" required>
                                </div>
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-addon">+251</span>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               pattern="[0-9]{9}" title="Please enter 9 digits">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>TIN Number</label>
                                    <input type="text" class="form-control" id="tinNumber" name="tinNumber" 
                                           pattern="[0-9]{10}" title="TIN should be 10 digits">
                                </div>
                                <div class="form-group">
                                    <label>FS Number</label>
                                    <input type="text" class="form-control" id="fsNumber" name="fsNumber">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                        </div>
                    </div>

                    <!-- Vehicle Details -->
                    <div class="form-section">
                        <h4><i class="glyphicon glyphicon-road"></i> Vehicle Details</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="vehicleTable">
                                <thead>
                                    <tr>
                                        <th>Plate Number *</th>
                                        <th>Trailer Plate</th>
                                        <th>Chassis Number *</th>
                                        <th>Motor Number</th>
                                        <th>IMEI Number *</th>
                                        <th width="100">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="vehicle-row">
                                        <td><input type="text" class="form-control" name="plate[]" required></td>
                                        <td><input type="text" class="form-control" name="trailer[]"></td>
                                        <td><input type="text" class="form-control" name="chassis[]" required></td>
                                        <td><input type="text" class="form-control" name="motor[]"></td>
                                        <td><input type="text" class="form-control" name="imei[]" required></td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm removeRow">
                                                <i class="glyphicon glyphicon-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-success" id="addVehicleRow">
                            <i class="glyphicon glyphicon-plus"></i> Add Vehicle
                        </button>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-info" id="previewBtn">
                            <i class="glyphicon glyphicon-eye-open"></i> Preview Letter
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="glyphicon glyphicon-file"></i> Generate Letter
                        </button>
                    </div>
                </form>

                <!-- Preview Modal -->
                <div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title">Letter Preview</h4>
                                <div class="modal-actions">
                                    <button type="button" class="btn btn-info" onclick="window.printPreview()">
                                        <i class="glyphicon glyphicon-print"></i> Print Preview
                                    </button>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                            </div>
                            <div class="modal-body" id="previewContent"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-section {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
    border: 1px solid #eee;
}

.form-section h4 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    color: #333;
}

.form-actions {
    padding: 15px;
    margin-top: 20px;
    border-top: 1px solid #eee;
}

.vehicle-row td {
    vertical-align: middle !important;
}

.modal-actions {
    float: right;
}

.modal-actions .btn {
    margin-right: 10px;
}

.required-field:after {
    content: " *";
    color: red;
}
</style>

<script src="custom/js/gps_letter.js"></script>
<?php require_once 'includes/footer.php'; ?>