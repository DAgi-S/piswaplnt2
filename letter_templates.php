<?php require_once 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <div class="page-heading"> <i class="glyphicon glyphicon-edit"></i> Manage Letter Templates</div>
            </div>
            <div class="panel-body">
                <div class="remove-messages"></div>

                <div class="div-action pull-right" style="padding-bottom:20px;">
                    <button class="btn btn-default button1" data-toggle="modal" data-target="#addTemplateModal"> 
                        <i class="glyphicon glyphicon-plus-sign"></i> Add Template 
                    </button>
                </div>

                <table class="table" id="manageLatterTemplatesTable">
                    <thead>
                        <tr>
                            <th>Letter Code</th>
                            <th>Letter For</th>
                            <th>Location</th>
                            <th>Subject</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="form-horizontal" id="submitTemplateForm" action="php_action/createTemplate.php" method="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add Letter Template</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Letter Code</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="letterCode" name="letterCode" placeholder="Letter Code" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Letter For</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="letterFor" name="letterFor" placeholder="Letter For" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Location</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="location" name="location" placeholder="Location" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Subject</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" id="subject" name="subject" placeholder="Letter Subject" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Letter Content</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="letterContent" name="letterContent" rows="5" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary" id="createTemplateBtn">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Template Modal -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editTemplateForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Edit Letter Template</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="templateId" id="templateId">
                    <div class="form-group">
                        <label>Letter Code</label>
                        <input type="text" class="form-control" id="letterCode" name="letterCode" required>
                    </div>
                    <div class="form-group">
                        <label>Letter For</label>
                        <input type="text" class="form-control" id="letterFor" name="letterFor" required>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label>Letter Content</label>
                        <textarea class="form-control" id="letterContent" name="letterContent" rows="5" required></textarea>
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

<script src="custom/js/letter_templates.js"></script>
<?php require_once 'includes/footer.php'; ?>