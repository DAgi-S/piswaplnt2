<?php 
require_once 'php_action/db_connect.php';
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <div class="page-heading"> <i class="glyphicon glyphicon-edit"></i> Manage Quotations</div>
                </div>
                <div class="panel-body">
                    <div class="remove-messages"></div>

                    <div class="div-action pull pull-right" style="padding-bottom:20px;">
                        <a href="quotation.php" class="btn btn-default button1">
                            <i class="glyphicon glyphicon-plus-sign"></i> Create New Quotation
                        </a>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="row">
                                        <div class="col-sm-3">
                                            <button class="btn btn-default" id="copyBtn">Copy</button>
                                            <button class="btn btn-default" id="csvBtn">CSV</button>
                                            <button class="btn btn-default" id="printBtn">Print</button>
                                        </div>
                                        <div class="col-sm-6"></div>
                                        <div class="col-sm-3">
                                            <input type="search" class="form-control input-sm" placeholder="Search" aria-controls="manageQuotationsTable">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <table class="table" id="manageQuotationsTable">
                                <thead>
                                    <tr>
                                        <th>Quotation #</th>
                                        <th>Client</th>
                                        <th>Date</th>
                                        <th>Sub Total</th>
                                        <th>VAT</th>
                                        <th>Grand Total</th>
                                        <th>Status</th>
                                        <th>Options</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Quotation Modal -->
<div class="modal fade" id="viewQuotationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-eye-open"></i> View Quotation</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>Quotation Number</th>
                                <td id="view_quotation_number"></td>
                            </tr>
                            <tr>
                                <th>Client</th>
                                <td id="view_client_name"></td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td id="view_created_at"></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td id="view_status"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th>Sub Total</th>
                                <td id="view_sub_total"></td>
                            </tr>
                            <tr>
                                <th>VAT Amount</th>
                                <td id="view_vat_amount"></td>
                            </tr>
                            <tr>
                                <th>Grand Total</th>
                                <td id="view_grand_total"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <h4>Quotation Items</h4>
                        <table class="table table-bordered table-striped" id="viewQuotationItemsTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="view_quotation_items"></tbody>
                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <h4>Notes</h4>
                        <p id="view_notes"></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printQuotation(currentQuotationId)">
                    <i class="glyphicon glyphicon-print"></i> Print
                </button>
                <button type="button" class="btn btn-info" onclick="emailQuotation(currentQuotationId)">
                    <i class="glyphicon glyphicon-envelope"></i> Email
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Email Quotation Modal -->
<div class="modal fade" id="emailQuotationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="glyphicon glyphicon-envelope"></i> Email Quotation</h4>
            </div>
            <form id="emailQuotationForm">
                <div class="modal-body">
                    <div id="email-messages"></div>

                    <div class="form-group">
                        <label for="emailTo" class="control-label">To Email *</label>
                        <input type="email" class="form-control" id="emailTo" name="emailTo" required>
                        <div class="help-block with-errors"></div>
                    </div>

                    <div class="form-group">
                        <label for="emailSubject" class="control-label">Subject *</label>
                        <input type="text" class="form-control" id="emailSubject" name="emailSubject" required>
                        <div class="help-block with-errors"></div>
                    </div>

                    <div class="form-group">
                        <label for="emailMessage" class="control-label">Message</label>
                        <textarea class="form-control" id="emailMessage" name="emailMessage" rows="4"></textarea>
                        <div class="help-block with-errors"></div>
                    </div>

                    <input type="hidden" id="quotationId" name="quotationId">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="sendEmailBtn">Send Email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Quotation Modal -->
<div class="modal fade" id="editQuotationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Edit Quotation</h4>
            </div>
            <div id="edit-quotation-messages"></div>
            <div class="modal-body">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<style>
/* Table Styles */
.table {
    font-size: 11px !important;
}

.table > thead > tr > th {
    background-color: #337ab7;
    color: white;
    font-weight: normal;
    vertical-align: middle !important;
    border-bottom: 0 !important;
}

.table-striped > tbody > tr:nth-of-type(odd) {
    background-color: #f9f9f9;
}

.table-striped > tbody > tr:nth-of-type(even) {
    background-color: #ffffff;
}

.table > tbody > tr > td {
    vertical-align: middle;
    padding: 4px 8px;
}

/* Button Styles */
.btn {
    padding: 2px 6px;
    font-size: 10px;
}

.btn-group {
    display: flex;
    gap: 2px;
}

/* Action Column */
.action-buttons {
    white-space: nowrap;
}

/* Status Labels */
.label {
    font-size: 9px;
    padding: 3px 6px;
}

/* Search and Length Menu */
.dataTables_length select {
    height: 25px;
    font-size: 10px;
    padding: 2px;
}

.dataTables_filter input {
    height: 25px;
    font-size: 10px;
    padding: 2px 6px;
}

/* Pagination */
.pagination > li > a {
    padding: 4px 8px;
    font-size: 10px;
}

/* Status Badge Styles */
.badge-active {
    background-color: #5cb85c;
}
.badge-pending {
    background-color: #f0ad4e;
}
.badge-cancelled {
    background-color: #d9534f;
}
</style>

<!-- Custom button handlers -->
<script>
$(document).ready(function() {
    // Initialize custom export buttons
    $('#copyBtn').on('click', function() {
        $('.buttons-copy').click();
    });
    $('#csvBtn').on('click', function() {
        $('.buttons-csv').click();
    });
    $('#printBtn').on('click', function() {
        $('.buttons-print').click();
    });
});
</script>

<script src="custom/js/manageQuotations.js"></script>
<?php require_once 'includes/footer.php'; ?> 