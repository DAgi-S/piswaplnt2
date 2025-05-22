<?php
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <ol class="breadcrumb">
            <li><a href="dashboard.php">Home</a></li>
            <li class="active">Loan Management</li>
        </ol>

        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"> <i class="fa fa-money"></i> Loan Management</div>
            </div>

            <div class="panel-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="div-action pull-right" style="padding-bottom:20px;">
                            <button class="btn btn-primary" data-toggle="modal" data-target="#addLoanModal">
                                <i class="fa fa-plus"></i> Add New Loan
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-aqua">
                            <span class="info-box-icon"><i class="fa fa-money"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Active Loans</span>
                                <span class="info-box-number" id="totalActiveLoans">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-green">
                            <span class="info-box-icon"><i class="fa fa-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Paid Amount</span>
                                <span class="info-box-number" id="totalPaidAmount">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-yellow">
                            <span class="info-box-icon"><i class="fa fa-clock-o"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Pending Payments</span>
                                <span class="info-box-number" id="pendingPayments">0</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-red">
                            <span class="info-box-icon"><i class="fa fa-warning"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Overdue Loans</span>
                                <span class="info-box-number" id="overdueLoans">0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <table class="table table-hover table-striped" id="loansTable">
                            <thead>
                                <tr>
                                    <th>Loan ID</th>
                                    <th>Borrower</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Interest Rate</th>
                                    <th>Loan Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
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

<!-- Add Loan Modal -->
<div class="modal fade" id="addLoanModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="addLoanForm" action="php_action/createLoan.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add New Loan</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Borrower Type</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="borrowerType" name="borrowerType" required>
                                <option value="">Select Type</option>
                                <option value="client">Client</option>
                                <option value="employee">Employee</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Borrower</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="borrowerId" name="borrowerId" required>
                                <option value="">Select Borrower</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Loan Amount</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="loanAmount" name="loanAmount" placeholder="Enter loan amount" required step="0.01">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Interest Rate (%)</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="interestRate" name="interestRate" placeholder="Enter interest rate" required step="0.01">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Loan Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="loanDate" name="loanDate" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Due Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="dueDate" name="dueDate" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Purpose</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="purpose" name="purpose" rows="3" placeholder="Enter loan purpose"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createLoanBtn">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Loan Modal -->
<div class="modal fade" id="viewLoanModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Loan Details</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Loan Information</h4>
                        <table class="table">
                            <tr>
                                <td><strong>Loan ID:</strong></td>
                                <td id="view-loanId"></td>
                            </tr>
                            <tr>
                                <td><strong>Borrower:</strong></td>
                                <td id="view-borrower"></td>
                            </tr>
                            <tr>
                                <td><strong>Amount:</strong></td>
                                <td id="view-amount"></td>
                            </tr>
                            <tr>
                                <td><strong>Interest Rate:</strong></td>
                                <td id="view-interestRate"></td>
                            </tr>
                            <tr>
                                <td><strong>Loan Date:</strong></td>
                                <td id="view-loanDate"></td>
                            </tr>
                            <tr>
                                <td><strong>Due Date:</strong></td>
                                <td id="view-dueDate"></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td id="view-status"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h4>Payment Summary</h4>
                        <table class="table">
                            <tr>
                                <td><strong>Total Amount:</strong></td>
                                <td id="view-totalAmount"></td>
                            </tr>
                            <tr>
                                <td><strong>Paid Amount:</strong></td>
                                <td id="view-paidAmount"></td>
                            </tr>
                            <tr>
                                <td><strong>Remaining Amount:</strong></td>
                                <td id="view-remainingAmount"></td>
                            </tr>
                            <tr>
                                <td><strong>Next Payment Due:</strong></td>
                                <td id="view-nextPaymentDue"></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <h4>Payment History</h4>
                        <table class="table table-bordered" id="paymentHistoryTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Reference</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="paymentHistoryBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="addPaymentBtn">Add Payment</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="addPaymentForm" action="php_action/createLoanPayment.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Payment</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="paymentLoanId" name="loanId">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Amount</label>
                        <div class="col-sm-9">
                            <input type="number" class="form-control" id="paymentAmount" name="paymentAmount" required step="0.01">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Payment Date</label>
                        <div class="col-sm-9">
                            <input type="date" class="form-control" id="paymentDate" name="paymentDate" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Payment Method</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="paymentMethod" name="paymentMethod" required>
                                <option value="">Select Method</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Account</label>
                        <div class="col-sm-9">
                            <select class="form-control" id="account" name="account" required>
                                <option value="">Select Account</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Reference Number</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="referenceNumber" name="referenceNumber">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Notes</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="paymentNotes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createPaymentBtn">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="custom/js/loan_management.js"></script>

<?php require_once 'includes/footer.php'; ?> 