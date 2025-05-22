<?php
// Define BASEPATH to prevent direct access
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// Include core files with correct paths
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../php_action/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Location: ../login.php');
    exit();
}

// Check warranty certificate permission
if (!hasPermission('warranty_certificate')) {
    $_SESSION['error'] = "You don't have permission to access warranty certificates";
    header('Location: ../access_denied.php');
    exit();
}

// Include necessary files after authentication
require_once __DIR__ . '/warranty_functions.php';
require_once __DIR__ . '/warranty_terms_functions.php';

// Get all business sectors and templates
$businessSectors = getAllBusinessSectors();
$allTemplates = getAllTermsTemplates();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Warranty Certificate Generator - Pi Stock</title>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Custom CSS -->
    <style>
        .breadcrumb {
            margin: 20px 0;
            background-color: #f8f9fa;
        }
        
        .panel {
            margin-bottom: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 1px 1px rgba(0,0,0,.05);
        }
        
        .panel-heading {
            padding: 10px 15px;
            border-bottom: 1px solid #ddd;
            border-top-left-radius: 3px;
            border-top-right-radius: 3px;
            background-color: #f5f5f5;
        }
        
        .panel-body {
            padding: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .select2-container {
            width: 100% !important;
        }
    </style>
</head>
<body>

<div class="container">
    <ol class="breadcrumb">
        <li><a href="../dashboard.php">Home</a></li>
        <li class="active">Warranty Certificate</li>
    </ol>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">Generate New Warranty Certificate</h3>
        </div>
        <div class="panel-body">
            <form id="warrantyForm" method="POST" action="generate_certificate.php" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="client_id">Client</label>
                            <select class="form-control select2" id="client_id" name="client_id" required>
                                <option value="">Select Client</option>
                                <?php echo getClientsList(); ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="company_name">Company Name</label>
                            <input type="text" class="form-control" id="company_name" name="company_name" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="services_provided">Services Provided</label>
                            <textarea class="form-control" id="services_provided" name="services_provided" rows="3" required></textarea>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="serial_reference">Serial/Reference Number</label>
                            <input type="text" class="form-control" id="serial_reference" name="serial_reference" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="invoice_date">Invoice Date</label>
                            <input type="date" class="form-control" id="invoice_date" name="invoice_date" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="installation_date">Installation Date</label>
                            <input type="date" class="form-control" id="installation_date" name="installation_date" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="warranty_duration">Warranty Duration (months)</label>
                            <input type="number" class="form-control" id="warranty_duration" name="warranty_duration" value="12" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="authorized_by">Authorized By</label>
                            <input type="text" class="form-control" id="authorized_by" name="authorized_by" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="signature_image">Signature Image</label>
                            <input type="file" class="form-control" id="signature_image" name="signature_image" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="business_sector">Business Sector</label>
                            <select class="form-control" id="business_sector" name="business_sector">
                                <option value="">Select Business Sector</option>
                                <?php foreach ($businessSectors as $sector): ?>
                                    <option value="<?php echo htmlspecialchars($sector); ?>"><?php echo htmlspecialchars($sector); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="terms_template">Terms Template</label>
                            <select class="form-control" id="terms_template" name="terms_template">
                                <option value="">Select Template</option>
                                <?php foreach ($allTemplates as $template): ?>
                                    <option value="<?php echo $template['id']; ?>" data-sector="<?php echo htmlspecialchars($template['business_sector']); ?>">
                                        <?php echo htmlspecialchars($template['template_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="terms_conditions">Terms and Conditions</label>
                            <div class="pull-right">
                                <button type="button" class="btn btn-sm btn-default" id="editTermsBtn">
                                    <i class="glyphicon glyphicon-edit"></i> Edit
                                </button>
                                <button type="button" class="btn btn-sm btn-warning" id="resetTermsBtn">
                                    <i class="glyphicon glyphicon-refresh"></i> Reset
                                </button>
                            </div>
                            <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="8" required></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="button" class="btn btn-info" id="previewBtn">Preview Certificate</button>
                    <button type="button" class="btn btn-warning" id="printPreviewBtn">Print Preview</button>
                    <button type="button" class="btn btn-default" onclick="window.location.href='../dashboard.php'">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Certificate Preview</h4>
            </div>
            <div class="modal-body">
                <div id="certificatePreview"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="printPreviewContent()">Print</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Print Preview Modal -->
<div class="modal fade" id="printPreviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Print Preview</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <iframe id="printPreviewFrame" style="width: 100%; height: 600px; border: none;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="printCertificate()">Print</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2();

    // Auto-fill form when client is selected
    $('#client_id').change(function() {
        var clientId = $(this).val();
        if (clientId) {
            // Show loading state
            $('#company_name').prop('disabled', true);
            
            $.ajax({
                url: 'get_client_info.php',
                method: 'POST',
                data: { client_id: clientId },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        $('#company_name').val(response.data.company_name || '');
                    } else {
                        console.error('Server response:', response);
                        alert(response.message || 'Failed to fetch client information');
                        $('#company_name').val('');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    alert('Failed to fetch client information. Please try again.');
                    $('#company_name').val('');
                },
                complete: function() {
                    // Re-enable input
                    $('#company_name').prop('disabled', false);
                }
            });
        } else {
            $('#company_name').val('').prop('disabled', false);
        }
    });

    // Preview button click handler
    $('#previewBtn').on('click', function(e) {
        e.preventDefault();
        
        var formData = new FormData($('#warrantyForm')[0]);
        formData.append('preview_mode', 'true');
        
        $.ajax({
            url: 'preview_certificate.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#certificatePreview').html(response);
                $('#previewModal').modal('show');
            },
            error: function(xhr, status, error) {
                alert('Failed to generate preview: ' + error);
            }
        });
    });

    // Print preview button handler
    $('#printPreviewBtn').on('click', function(e) {
        e.preventDefault();
        var previewUrl = 'print_preview.php?' + new URLSearchParams(new FormData($('form')[0])).toString();
        window.open(previewUrl, '_blank');
    });

    // Store original terms content
    var originalTerms = $('#terms_conditions').val();
    
    // Handle business sector change
    $('#business_sector').change(function() {
        var selectedSector = $(this).val();
        var $templateSelect = $('#terms_template');
        
        // Reset template select
        $templateSelect.val('');
        
        // Show/hide templates based on sector
        $templateSelect.find('option').each(function() {
            var $option = $(this);
            if ($option.val() === '' || $option.data('sector') === selectedSector) {
                $option.show();
            } else {
                $option.hide();
            }
        });
    });
    
    // Handle template selection
    $('#terms_template').change(function() {
        var templateId = $(this).val();
        if (templateId) {
            // Show loading state
            var $termsTextarea = $('#terms_conditions');
            $termsTextarea.prop('disabled', true);
            
            // Fetch template content
            $.ajax({
                url: 'get_template_content.php',
                method: 'POST',
                data: { template_id: templateId },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        $termsTextarea.val(response.data.terms_content);
                        originalTerms = response.data.terms_content;
                    } else {
                        alert(response.message || 'Failed to load template');
                    }
                },
                error: function() {
                    alert('Failed to load template. Please try again.');
                },
                complete: function() {
                    $termsTextarea.prop('disabled', false);
                }
            });
        }
    });
    
    // Handle edit button
    $('#editTermsBtn').click(function() {
        var $termsTextarea = $('#terms_conditions');
        $termsTextarea.prop('readonly', function(i, readonly) {
            return !readonly;
        });
        $(this).toggleClass('btn-default btn-primary');
    });
    
    // Handle reset button
    $('#resetTermsBtn').click(function() {
        if (confirm('Are you sure you want to reset the terms to the original template?')) {
            $('#terms_conditions').val(originalTerms);
        }
    });
    
    // Initialize terms as readonly
    $('#terms_conditions').prop('readonly', true);
});

function printPreviewContent() {
    // Get the preview content
    var content = $('#certificatePreview').html();
    
    if (!content) {
        alert('No preview content available');
        return;
    }
    
    // Create a new window
    var printWin = window.open('', '_blank', 'width=800,height=600');
    
    // Add the content with styling
    printWin.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Warranty Certificate</title>
            <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
            <style>
                body { 
                    font-family: Arial, sans-serif;
                    padding: 20px;
                    margin: 0;
                }
                .certificate-content {
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                    background: white;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                td {
                    padding: 8px;
                    border: 1px solid #ddd;
                }
                .print-header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .print-footer {
                    text-align: center;
                    margin-top: 30px;
                    font-size: 12px;
                    color: #666;
                }
                @media print {
                    .no-print { display: none !important; }
                    body { margin: 0; padding: 15px; }
                    .certificate-content { padding: 0; }
                }
                .print-controls {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                    z-index: 9999;
                }
            </style>
        </head>
        <body>
            <div class="print-controls no-print">
                <button onclick="window.print()" class="btn btn-primary">Print</button>
                <button onclick="window.close()" class="btn btn-default">Close</button>
            </div>
            <div class="certificate-content">
                ${content}
            </div>
            <div class="print-footer no-print">
                Generated on ${new Date().toLocaleString()}
            </div>
        </body>
        </html>
    `);
    
    printWin.document.close();
}
</script>

</body>
</html> 