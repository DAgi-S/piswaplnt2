<?php
require_once 'php_action/core.php';
require_once 'includes/header.php';
?>

<!-- Add Toastr CSS and JS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>

<!-- Add SweetAlert2 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading">
                    <i class="fas fa-warehouse"></i> Manage Warehouses
                </div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <table class="table table-hover table-striped" id="warehousesTable">
                    <!-- Existing warehouse table structure -->
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Warehouse Modal -->
<div class="modal fade" id="addWarehouseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addWarehouseForm" action="php_action/createWarehouse.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="fas fa-plus"></i> Add Warehouse</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="warehouseCode">Code</label>
                        <input type="text" class="form-control" id="warehouseCode" name="code" placeholder="Enter warehouse code" required>
                    </div>
                    <div class="form-group">
                        <label for="warehouseName">Name</label>
                        <input type="text" class="form-control" id="warehouseName" name="name" placeholder="Enter warehouse name" required>
                    </div>
                    <div class="form-group">
                        <label for="warehouseType">Type</label>
                        <select class="form-control" id="warehouseType" name="type" required>
                            <option value="">Select Type</option>
                            <option value="raw_material">Raw Material</option>
                            <option value="finished_good">Finished Good</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="warehouseLocation">Location</label>
                        <input type="text" class="form-control" id="warehouseLocation" name="location" placeholder="Enter warehouse location">
                    </div>
                    <div class="form-group">
                        <label for="warehouseDescription">Description</label>
                        <textarea class="form-control" id="warehouseDescription" name="description" rows="3" placeholder="Enter warehouse description"></textarea>
                    </div>
                    <input type="hidden" name="status" value="active">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Warehouse</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stock View Modal -->
<div class="modal fade" id="viewStockModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Warehouse Stock Items</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-hover table-striped" id="warehouseStockTable">
                            <thead>
                                <tr>
                                    <th>Item Code</th>
                                    <th>Item Name</th>
                                    <th>Type</th>
                                    <th>Unit</th>
                                    <th>Quantity</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Stock Modal -->
<div class="modal fade" id="editStockModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editStockForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">Edit Stock Quantity</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="editQuantity">Quantity</label>
                        <input type="number" class="form-control" id="editQuantity" name="quantity" step="0.01" required>
                        <input type="hidden" id="editStockId" name="stockId">
                    </div>
                    <div class="form-group">
                        <label for="editNotes">Notes</label>
                        <textarea class="form-control" id="editNotes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include custom JS -->
<script src="js/warehouses.js"></script>

<?php require_once 'includes/footer.php'; ?> 