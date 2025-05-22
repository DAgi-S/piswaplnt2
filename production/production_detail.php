<?php 
require_once 'includes/header.php';

// Get production order ID from URL
$productionOrderId = isset($_GET['id']) ? $_GET['id'] : 0;
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="../dashboard.php">Home</a></li>
            <li><a href="production_orders.php">Production Orders</a></li>
            <li class="active">Production Detail</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fa fa-industry"></i> Production Order Detail
                    <div class="pull-right">
                        <button class="btn btn-warning" id="btnUpdateProgress">
                            <i class="fa fa-refresh"></i> Update Progress
                        </button>
                        <button class="btn btn-success" id="btnAddMaterial">
                            <i class="fa fa-plus"></i> Add Material
                        </button>
                    </div>
                </div>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Order Number:</label>
                            <p class="form-control-static" id="orderNumber"></p>
                        </div>
                        <div class="form-group">
                            <label>Status:</label>
                            <p class="form-control-static" id="orderStatus"></p>
                        </div>
                        <div class="form-group">
                            <label>Start Date:</label>
                            <p class="form-control-static" id="startDate"></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Target Quantity:</label>
                            <p class="form-control-static" id="targetQuantity"></p>
                        </div>
                        <div class="form-group">
                            <label>Completed Quantity:</label>
                            <p class="form-control-static" id="completedQuantity"></p>
                        </div>
                        <div class="form-group">
                            <label>Completion Date:</label>
                            <p class="form-control-static" id="completionDate"></p>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12">
                        <h4>Required Materials</h4>
                        <table class="table table-striped table-bordered" id="materialsTable">
                            <thead>
                                <tr>
                                    <th>Material Code</th>
                                    <th>Name</th>
                                    <th>Required Qty</th>
                                    <th>Consumed Qty</th>
                                    <th>Available Stock</th>
                                    <th>Unit</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12">
                        <h4>Production Progress</h4>
                        <table class="table table-striped table-bordered" id="progressTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Quantity Produced</th>
                                    <th>Notes</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Material Modal -->
<div class="modal fade" id="addMaterialModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="addMaterialForm" action="php_action/addProductionMaterial.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Material</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="productionOrderId" value="<?php echo $productionOrderId; ?>">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Material</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="materialId" name="materialId" required>
                                <option value="">Select Material</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Required Quantity</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="requiredQuantity" name="requiredQuantity" required>
                            <span class="help-block">Available Stock: <span id="availableStock">0</span></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Material</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Progress Modal -->
<div class="modal fade" id="updateProgressModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="updateProgressForm" action="php_action/updateProductionProgress.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-refresh"></i> Update Production Progress</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="productionOrderId" value="<?php echo $productionOrderId; ?>">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Quantity Produced</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="quantityProduced" name="quantityProduced" required>
                            <span class="help-block">Remaining: <span id="remainingQuantity">0</span></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Notes</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="progressNotes" name="progressNotes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Progress</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Consume Material Modal -->
<div class="modal fade" id="consumeMaterialModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="consumeMaterialForm" action="php_action/consumeProductionMaterial.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-cube"></i> Consume Material</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="productionOrderId" value="<?php echo $productionOrderId; ?>">
                    <input type="hidden" name="materialId" id="consumeMaterialId">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Material</label>
                        <div class="col-sm-9">
                            <p class="form-control-static" id="consumeMaterialName"></p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Quantity to Consume</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" class="form-control" id="consumeQuantity" name="consumeQuantity" required>
                            <span class="help-block">Available: <span id="consumeAvailableStock">0</span></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Notes</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="consumeNotes" name="consumeNotes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Consume Material</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="js/production_detail.js"></script>

<?php require_once 'includes/footer.php'; ?> 