<?php
// Production Expense Modal
?>
<!-- Production Expense Modal -->
<div class="modal fade" id="productionExpenseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="productionExpenseForm" action="../expense/api/createExpense.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-industry"></i> Add Production Expense</h4>
                </div>
                <div class="modal-body">
                    <div id="production-expense-messages"></div>
                    <input type="hidden" name="expense_type" value="production">
                    
                    <div class="form-group">
                        <label for="productionExpenseDate">Date: *</label>
                        <input type="date" class="form-control" id="productionExpenseDate" name="expense_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="productionExpenseCategory">Category: *</label>
                        <select class="form-control" id="productionExpenseCategory" name="category_id" required>
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="productionExpenseAmount">Amount: *</label>
                        <input type="number" step="0.01" class="form-control" id="productionExpenseAmount" name="amount" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="productionOrderSelect">Production Order: *</label>
                        <select class="form-control" id="productionOrderSelect" name="production_order_id" required>
                            <option value="">Select Production Order</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="productionExpenseReference">Reference:</label>
                        <input type="text" class="form-control" id="productionExpenseReference" name="reference" placeholder="Reference Number/Details">
                    </div>
                    
                    <div class="form-group">
                        <label for="productionExpenseDescription">Description:</label>
                        <textarea class="form-control" id="productionExpenseDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="productionExpenseAttachment">Attachment:</label>
                        <input type="file" class="form-control" id="productionExpenseAttachment" name="attachment">
                        <p class="help-block">Allowed types: jpg, jpeg, png, pdf, doc, docx</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveProductionExpense">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Salary Expense Modal -->
<div class="modal fade" id="salaryExpenseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="salaryExpenseForm" action="../expense/api/createExpense.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-money"></i> Add Salary Expense</h4>
                </div>
                <div class="modal-body">
                    <div id="salary-expense-messages"></div>
                    <input type="hidden" name="expense_type" value="salary">
                    
                    <div class="form-group">
                        <label for="salaryExpenseDate">Date: *</label>
                        <input type="date" class="form-control" id="salaryExpenseDate" name="expense_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="salaryExpenseCategory">Category: *</label>
                        <select class="form-control" id="salaryExpenseCategory" name="category_id" required>
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="salaryExpenseAmount">Amount: *</label>
                        <input type="number" step="0.01" class="form-control" id="salaryExpenseAmount" name="amount" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="salaryExpenseEmployee">Employee: *</label>
                        <select class="form-control" id="salaryExpenseEmployee" name="employee_id" required>
                            <option value="">Select Employee</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="salaryExpensePeriod">Pay Period: *</label>
                        <input type="month" class="form-control" id="salaryExpensePeriod" name="pay_period" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="salaryExpenseReference">Reference:</label>
                        <input type="text" class="form-control" id="salaryExpenseReference" name="reference" placeholder="Reference Number/Details">
                    </div>
                    
                    <div class="form-group">
                        <label for="salaryExpenseDescription">Description:</label>
                        <textarea class="form-control" id="salaryExpenseDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="salaryExpenseAttachment">Attachment:</label>
                        <input type="file" class="form-control" id="salaryExpenseAttachment" name="attachment">
                        <p class="help-block">Allowed types: jpg, jpeg, png, pdf, doc, docx</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveSalaryExpense">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Rent Expense Modal -->
<div class="modal fade" id="rentExpenseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rentExpenseForm" action="../expense/api/createExpense.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-building"></i> Add Rent Expense</h4>
                </div>
                <div class="modal-body">
                    <div id="rent-expense-messages"></div>
                    <input type="hidden" name="expense_type" value="rent">
                    
                    <div class="form-group">
                        <label for="rentExpenseDate">Date: *</label>
                        <input type="date" class="form-control" id="rentExpenseDate" name="expense_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="rentExpenseCategory">Category: *</label>
                        <select class="form-control" id="rentExpenseCategory" name="category_id" required>
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="rentExpenseAmount">Amount: *</label>
                        <input type="number" step="0.01" class="form-control" id="rentExpenseAmount" name="amount" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="rentExpensePeriod">Rent Period: *</label>
                        <input type="month" class="form-control" id="rentExpensePeriod" name="rent_period" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="rentExpenseReference">Reference:</label>
                        <input type="text" class="form-control" id="rentExpenseReference" name="reference" placeholder="Reference Number/Details">
                    </div>
                    
                    <div class="form-group">
                        <label for="rentExpenseDescription">Description:</label>
                        <textarea class="form-control" id="rentExpenseDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="rentExpenseAttachment">Attachment:</label>
                        <input type="file" class="form-control" id="rentExpenseAttachment" name="attachment">
                        <p class="help-block">Allowed types: jpg, jpeg, png, pdf, doc, docx</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveRentExpense">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Maintenance Expense Modal -->
<div class="modal fade" id="maintenanceExpenseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="maintenanceExpenseForm" action="../expense/api/createExpense.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-wrench"></i> Add Maintenance Expense</h4>
                </div>
                <div class="modal-body">
                    <div id="maintenance-expense-messages"></div>
                    <input type="hidden" name="expense_type" value="maintenance">
                    
                    <div class="form-group">
                        <label for="maintenanceExpenseDate">Date: *</label>
                        <input type="date" class="form-control" id="maintenanceExpenseDate" name="expense_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="maintenanceExpenseCategory">Category: *</label>
                        <select class="form-control" id="maintenanceExpenseCategory" name="category_id" required>
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="maintenanceExpenseAmount">Amount: *</label>
                        <input type="number" step="0.01" class="form-control" id="maintenanceExpenseAmount" name="amount" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="maintenanceExpenseAsset">Asset/Equipment: *</label>
                        <select class="form-control" id="maintenanceExpenseAsset" name="asset_id" required>
                            <option value="">Select Asset/Equipment</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="maintenanceExpenseReference">Reference:</label>
                        <input type="text" class="form-control" id="maintenanceExpenseReference" name="reference" placeholder="Reference Number/Details">
                    </div>
                    
                    <div class="form-group">
                        <label for="maintenanceExpenseDescription">Description:</label>
                        <textarea class="form-control" id="maintenanceExpenseDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="maintenanceExpenseAttachment">Attachment:</label>
                        <input type="file" class="form-control" id="maintenanceExpenseAttachment" name="attachment">
                        <p class="help-block">Allowed types: jpg, jpeg, png, pdf, doc, docx</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveMaintenanceExpense">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Labor Expense Modal -->
<div class="modal fade" id="laborExpenseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="laborExpenseForm" action="../expense/api/createExpense.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-users"></i> Add Labor Expense</h4>
                </div>
                <div class="modal-body">
                    <div id="labor-expense-messages"></div>
                    <input type="hidden" name="expense_type" value="labor">
                    
                    <div class="form-group">
                        <label for="laborExpenseDate">Date: *</label>
                        <input type="date" class="form-control" id="laborExpenseDate" name="expense_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="laborExpenseCategory">Category: *</label>
                        <select class="form-control" id="laborExpenseCategory" name="category_id" required>
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="laborExpenseAmount">Amount: *</label>
                        <input type="number" step="0.01" class="form-control" id="laborExpenseAmount" name="amount" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="laborExpenseWorkers">Number of Workers: *</label>
                        <input type="number" class="form-control" id="laborExpenseWorkers" name="workers_count" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="laborExpenseHours">Hours Worked: *</label>
                        <input type="number" step="0.5" class="form-control" id="laborExpenseHours" name="hours_worked" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="laborExpenseReference">Reference:</label>
                        <input type="text" class="form-control" id="laborExpenseReference" name="reference" placeholder="Reference Number/Details">
                    </div>
                    
                    <div class="form-group">
                        <label for="laborExpenseDescription">Description:</label>
                        <textarea class="form-control" id="laborExpenseDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="laborExpenseAttachment">Attachment:</label>
                        <input type="file" class="form-control" id="laborExpenseAttachment" name="attachment">
                        <p class="help-block">Allowed types: jpg, jpeg, png, pdf, doc, docx</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveLaborExpense">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Other Expense Modal -->
<div class="modal fade" id="otherExpenseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="otherExpenseForm" action="../expense/api/createExpense.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus-circle"></i> Add Other Expense</h4>
                </div>
                <div class="modal-body">
                    <div id="other-expense-messages"></div>
                    <input type="hidden" name="expense_type" value="other">
                    
                    <div class="form-group">
                        <label for="otherExpenseDate">Date: *</label>
                        <input type="date" class="form-control" id="otherExpenseDate" name="expense_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="otherExpenseCategory">Category: *</label>
                        <select class="form-control" id="otherExpenseCategory" name="category_id" required>
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="otherExpenseAmount">Amount: *</label>
                        <input type="number" step="0.01" class="form-control" id="otherExpenseAmount" name="amount" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="otherExpenseReference">Reference:</label>
                        <input type="text" class="form-control" id="otherExpenseReference" name="reference" placeholder="Reference Number/Details">
                    </div>
                    
                    <div class="form-group">
                        <label for="otherExpenseDescription">Description:</label>
                        <textarea class="form-control" id="otherExpenseDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="otherExpenseAttachment">Attachment:</label>
                        <input type="file" class="form-control" id="otherExpenseAttachment" name="attachment">
                        <p class="help-block">Allowed types: jpg, jpeg, png, pdf, doc, docx</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="saveOtherExpense">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div> 