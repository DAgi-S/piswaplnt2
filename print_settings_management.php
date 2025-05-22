<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('BASEPATH', true);
require_once 'includes/header.php';
require_once 'includes/print_settings.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Location: login.php');
    exit();
}

// Initialize messages array
$messages = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $template_name = $_POST['template_name'];
        $settings = [
            'page_size' => $_POST['page_size'],
            'orientation' => $_POST['orientation'],
            'margin_top' => $_POST['margin_top'],
            'margin_right' => $_POST['margin_right'],
            'margin_bottom' => $_POST['margin_bottom'],
            'margin_left' => $_POST['margin_left'],
            'font_family' => $_POST['font_family'],
            'font_size' => $_POST['font_size'],
            'header_alignment' => $_POST['header_alignment'],
            'logo_path' => $_POST['logo_path'],
            'logo_width' => $_POST['logo_width'],
            'footer_text' => $_POST['footer_text']
        ];

        // Basic validation
        foreach ($settings as $key => $value) {
            $settings[$key] = trim($value);
            if ($value === '') {
                throw new Exception("Field '$key' cannot be empty");
            }
        }

        // Validate margin format (must end with mm, cm, px, etc.)
        $margin_fields = ['margin_top', 'margin_right', 'margin_bottom', 'margin_left'];
        foreach ($margin_fields as $field) {
            if (!preg_match('/^\d+(\.\d+)?(mm|cm|px|pt|em|rem)$/', $settings[$field])) {
                throw new Exception("Invalid $field format. Must be a number followed by a unit (e.g., 25mm, 2.5cm, 10px)");
            }
        }

        // Validate font size format
        if (!preg_match('/^\d+(\.\d+)?(px|pt|em|rem)$/', $settings['font_size'])) {
            throw new Exception("Invalid font size format. Must be a number followed by a unit (e.g., 12px, 1.2em)");
        }

        // Validate logo width format
        if (!preg_match('/^\d+(\.\d+)?(px|%|em|rem)$/', $settings['logo_width'])) {
            throw new Exception("Invalid logo width format. Must be a number followed by a unit (e.g., 150px, 50%)");
        }

        // Save settings
        save_print_settings($template_name, $settings);
        $messages['success'] = "Print settings updated successfully!";
        
    } catch (Exception $e) {
        $messages['error'] = "Error: " . $e->getMessage();
    }
}

// Get current settings for the selected template
$template_name = isset($_GET['template']) ? $_GET['template'] : 'default';
error_log("Debug: Getting print styles for template: " . $template_name);

$current_settings = get_print_styles($template_name);
error_log("Debug: Current settings: " . print_r($current_settings, true));

// Get list of available templates
try {
    error_log("Debug: Fetching available templates");
    $result = $connect->query("SELECT DISTINCT template_name FROM print_settings ORDER BY template_name");
    $templates = array();
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row['template_name'];
    }
    error_log("Debug: Available templates: " . print_r($templates, true));
} catch (Exception $e) {
    error_log("Error fetching templates: " . $e->getMessage());
    $templates = ['default'];
}

// If no templates exist, ensure default template is available
if (empty($templates)) {
    error_log("Debug: No templates found, creating default template");
    $templates = ['default'];
    
    // Insert default template if it doesn't exist
    try {
        $sql = "INSERT IGNORE INTO print_settings (
            template_name, page_size, orientation, margin_top, margin_right,
            margin_bottom, margin_left, font_family, font_size, header_alignment,
            logo_path, logo_width, footer_text
        ) VALUES (
            'default', 'A4', 'portrait', '25mm', '20mm',
            '25mm', '20mm', 'Arial, sans-serif', '12px', 'left',
            'assets/images/logo.png', '150px', 'Copyright © " . date('Y') . " Your Company Name. All rights reserved.'
        )";
        $connect->query($sql);
        error_log("Debug: Default template created successfully");
    } catch (Exception $e) {
        error_log("Error creating default template: " . $e->getMessage());
    }
}
?>

<link rel="stylesheet" href="assests/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="custom/css/custom.css">
<link rel="stylesheet" href="assests/plugins/datatables/jquery.dataTables.min.css">
<link rel="stylesheet" href="assests/plugins/datatables/buttons.dataTables.min.css">
<link rel="stylesheet" href="assets/plugins/datatables/css/responsive.dataTables.min.css">
<!-- Add any other necessary CSS files -->

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Print Settings Management</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                        <li class="breadcrumb-item">Settings</li>
                        <li class="breadcrumb-item active">Print Settings</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($messages['success'])): ?>
            <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-check"></i> Success!</h5>
                <?php echo $messages['success']; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($messages['error'])): ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h5><i class="icon fas fa-ban"></i> Error!</h5>
                <?php echo $messages['error']; ?>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Print Templates</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addTemplateModal">
                            <i class="fas fa-plus"></i> Add New Template
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table id="printTemplatesTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Template Name</th>
                                <th>Page Size</th>
                                <th>Orientation</th>
                                <th>Font Family</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Add Template Modal -->
<div class="modal fade" id="addTemplateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Print Template</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addTemplateForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="template_name">Template Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="template_name" id="template_name" required>
                                <small class="form-text text-muted">Enter a unique name for this template</small>
                            </div>
                            <div class="form-group">
                                <label for="page_size">Page Size <span class="text-danger">*</span></label>
                                <select name="page_size" id="page_size" class="form-control" required>
                                    <option value="A4">A4</option>
                                    <option value="A5">A5</option>
                                    <option value="Letter">Letter</option>
                                    <option value="Legal">Legal</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="orientation">Orientation <span class="text-danger">*</span></label>
                                <select name="orientation" id="orientation" class="form-control" required>
                                    <option value="portrait">Portrait</option>
                                    <option value="landscape">Landscape</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="font_family">Font Family <span class="text-danger">*</span></label>
                                <select name="font_family" id="font_family" class="form-control" required>
                                    <option value="Arial, sans-serif">Arial</option>
                                    <option value="Times New Roman, serif">Times New Roman</option>
                                    <option value="Helvetica, sans-serif">Helvetica</option>
                                    <option value="Calibri, sans-serif">Calibri</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="font_size">Font Size <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="font_size" id="font_size" value="12px" required>
                                <small class="form-text text-muted">Enter size with unit (e.g., 12px, 1em)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="margin_top">Top Margin <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="margin_top" id="margin_top" value="25mm" required>
                                <small class="form-text text-muted">Enter margin with unit (e.g., 25mm, 1in)</small>
                            </div>
                            <div class="form-group">
                                <label for="margin_right">Right Margin <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="margin_right" id="margin_right" value="20mm" required>
                            </div>
                            <div class="form-group">
                                <label for="margin_bottom">Bottom Margin <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="margin_bottom" id="margin_bottom" value="25mm" required>
                            </div>
                            <div class="form-group">
                                <label for="margin_left">Left Margin <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="margin_left" id="margin_left" value="20mm" required>
                            </div>
                            <div class="form-group">
                                <label for="header_alignment">Header Alignment <span class="text-danger">*</span></label>
                                <select name="header_alignment" id="header_alignment" class="form-control" required>
                                    <option value="left">Left</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="logo_path">Logo Path <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="logo_path" id="logo_path" value="assets/images/logo.png" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-info" onclick="previewLogo(this)">
                                            <i class="fas fa-eye"></i> Preview
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Enter the path to your logo file</small>
                            </div>
                            <div class="form-group">
                                <label for="logo_width">Logo Width <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="logo_width" id="logo_width" value="150px" required>
                                <small class="form-text text-muted">Enter width with unit (e.g., 150px, 50%)</small>
                            </div>
                            <div class="form-group">
                                <label for="footer_text">Footer Text <span class="text-danger">*</span></label>
                                <textarea name="footer_text" id="footer_text" class="form-control" rows="2" required>Copyright © {year} Your Company Name. All rights reserved.</textarea>
                                <small class="form-text text-muted">Use {year} to automatically insert current year</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Template Modal -->
<div class="modal fade" id="editTemplateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Print Template</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editTemplateForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_template_name">Template Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="template_name" id="edit_template_name" required readonly>
                                <small class="form-text text-muted">Template name cannot be changed</small>
                            </div>
                            <div class="form-group">
                                <label for="edit_page_size">Page Size <span class="text-danger">*</span></label>
                                <select name="page_size" id="edit_page_size" class="form-control" required>
                                    <option value="A4">A4</option>
                                    <option value="A5">A5</option>
                                    <option value="Letter">Letter</option>
                                    <option value="Legal">Legal</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit_orientation">Orientation <span class="text-danger">*</span></label>
                                <select name="orientation" id="edit_orientation" class="form-control" required>
                                    <option value="portrait">Portrait</option>
                                    <option value="landscape">Landscape</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit_font_family">Font Family <span class="text-danger">*</span></label>
                                <select name="font_family" id="edit_font_family" class="form-control" required>
                                    <option value="Arial, sans-serif">Arial</option>
                                    <option value="Times New Roman, serif">Times New Roman</option>
                                    <option value="Helvetica, sans-serif">Helvetica</option>
                                    <option value="Calibri, sans-serif">Calibri</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="edit_font_size">Font Size <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="font_size" id="edit_font_size" required>
                                <small class="form-text text-muted">Enter size with unit (e.g., 12px, 1em)</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_margin_top">Top Margin <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="margin_top" id="edit_margin_top" required>
                                <small class="form-text text-muted">Enter margin with unit (e.g., 25mm, 1in)</small>
                            </div>
                            <div class="form-group">
                                <label for="edit_margin_right">Right Margin <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="margin_right" id="edit_margin_right" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_margin_bottom">Bottom Margin <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="margin_bottom" id="edit_margin_bottom" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_margin_left">Left Margin <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="margin_left" id="edit_margin_left" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_header_alignment">Header Alignment <span class="text-danger">*</span></label>
                                <select name="header_alignment" id="edit_header_alignment" class="form-control" required>
                                    <option value="left">Left</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="edit_logo_path">Logo Path <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="logo_path" id="edit_logo_path" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-info" onclick="previewLogo(this)">
                                            <i class="fas fa-eye"></i> Preview
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Enter the path to your logo file</small>
                            </div>
                            <div class="form-group">
                                <label for="edit_logo_width">Logo Width <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="logo_width" id="edit_logo_width" required>
                                <small class="form-text text-muted">Enter width with unit (e.g., 150px, 50%)</small>
                            </div>
                            <div class="form-group">
                                <label for="edit_footer_text">Footer Text <span class="text-danger">*</span></label>
                                <textarea name="footer_text" id="edit_footer_text" class="form-control" rows="2" required></textarea>
                                <small class="form-text text-muted">Use {year} to automatically insert current year</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Template</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Print Preview</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <iframe id="previewFrame" style="width: 100%; height: 600px; border: 1px solid #ddd;"></iframe>
            </div>
        </div>
    </div>
</div>

<script src="assests/jquery/jquery.min.js"></script>
<script src="assests/bootstrap/js/bootstrap.min.js"></script>
<script src="assests/plugins/datatables/js/jquery.dataTables.min.js"></script>
<script src="assests/plugins/datatables/js/dataTables.buttons.min.js"></script>
<script src="assests/plugins/datatables/js/buttons.html5.min.js"></script>
<script src="assests/plugins/datatables/js/buttons.print.min.js"></script>
<script src="assests/plugins/datatables/extensions/responsive/js/dataTables.responsive.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#printTemplatesTable').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": "php_action/fetchPrintTemplates.php",
            "type": "POST",
            "dataSrc": function(json) {
                if (json.error) {
                    console.error('DataTable error:', json.message);
                    return [];
                }
                return json.data || [];
            },
            "error": function(xhr, error, thrown) {
                console.error('DataTable AJAX error:', error, thrown);
            }
        },
        "columns": [
            { "data": "template_name" },
            { "data": "page_size" },
            { "data": "orientation" },
            { "data": "font_family" },
            { 
                "data": "updated_at",
                "render": function(data) {
                    return data ? new Date(data).toLocaleString() : '';
                }
            },
            {
                "data": "actions",
                "orderable": false,
                "render": function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button type="button" class="btn btn-info btn-sm" onclick="previewTemplate('${row.template_name}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" onclick="editTemplate('${row.template_name}')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="deleteTemplate('${row.template_name}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        "responsive": true,
        "lengthChange": true,
        "autoWidth": false,
        "buttons": ["copy", "csv", "excel", "pdf", "print"],
        "order": [[0, "asc"]],
        "language": {
            "emptyTable": "No print templates found",
            "processing": "Loading print templates...",
            "zeroRecords": "No matching print templates found"
        }
    });

    // Add Template Form Submit
    $('#addTemplateForm').on('submit', function(e) {
        e.preventDefault();
        
        // Basic form validation
        let isValid = true;
        let errorMessages = [];
        
        // Validate template name
        const templateName = $('#template_name').val().trim();
        if (!templateName) {
            isValid = false;
            errorMessages.push('Template name is required');
        }
        
        // Validate margins format
        const marginFields = ['margin_top', 'margin_right', 'margin_bottom', 'margin_left'];
        marginFields.forEach(field => {
            const value = $(`#${field}`).val().trim();
            if (!value.match(/^\d+(\.\d+)?(mm|cm|px|pt|em|rem)$/)) {
                isValid = false;
                errorMessages.push(`Invalid ${field.replace('_', ' ')} format. Must be a number followed by a unit (e.g., 25mm, 2.5cm)`);
            }
        });
        
        // Validate font size format
        const fontSize = $('#font_size').val().trim();
        if (!fontSize.match(/^\d+(\.\d+)?(px|pt|em|rem)$/)) {
            isValid = false;
            errorMessages.push('Invalid font size format. Must be a number followed by a unit (e.g., 12px, 1.2em)');
        }
        
        // Validate logo width format
        const logoWidth = $('#logo_width').val().trim();
        if (!logoWidth.match(/^\d+(\.\d+)?(px|%|em|rem)$/)) {
            isValid = false;
            errorMessages.push('Invalid logo width format. Must be a number followed by a unit (e.g., 150px, 50%)');
        }
        
        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: errorMessages.join('<br>'),
            });
            return;
        }
        
        // Show loading state
        Swal.fire({
            title: 'Creating template...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Submit form if validation passes
        $.ajax({
            url: 'php_action/createPrintTemplate.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                console.log('Server response:', response);
                
                if (response.success) {
                    // Hide modal and remove backdrop
                    $('#addTemplateModal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    
                    // Reset form
                    $('#addTemplateForm')[0].reset();
                    
                    // Reload the table
                    if (typeof table !== 'undefined') {
                        table.ajax.reload();
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message || 'Template created successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to create template',
                        footer: response.debug ? `Debug info: ${JSON.stringify(response.debug)}` : null
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {xhr, status, error});
                let errorMessage = 'Failed to create template. Please try again.';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    footer: `Status: ${status}, Error: ${error}`
                });
            }
        });
    });

    // Reset form when modal is closed
    $('#addTemplateModal').on('hidden.bs.modal', function() {
        $('#addTemplateForm')[0].reset();
        // Reset any validation states or error messages
        $('#addTemplateForm .is-invalid').removeClass('is-invalid');
    });

    // Edit Template Form Submit
    $('#editTemplateForm').on('submit', function(e) {
        e.preventDefault();
        
        // Basic form validation
        let isValid = true;
        let errorMessages = [];
        
        // Validate margins format
        const marginFields = ['edit_margin_top', 'edit_margin_right', 'edit_margin_bottom', 'edit_margin_left'];
        marginFields.forEach(field => {
            const value = $(`#${field}`).val().trim();
            if (!value.match(/^\d+(\.\d+)?(mm|cm|px|pt|em|rem)$/)) {
                isValid = false;
                errorMessages.push(`Invalid ${field.replace('edit_margin_', '').replace('_', ' ')} format. Must be a number followed by a unit (e.g., 25mm, 2.5cm)`);
            }
        });
        
        // Validate font size format
        const fontSize = $('#edit_font_size').val().trim();
        if (!fontSize.match(/^\d+(\.\d+)?(px|pt|em|rem)$/)) {
            isValid = false;
            errorMessages.push('Invalid font size format. Must be a number followed by a unit (e.g., 12px, 1.2em)');
        }
        
        // Validate logo width format
        const logoWidth = $('#edit_logo_width').val().trim();
        if (!logoWidth.match(/^\d+(\.\d+)?(px|%|em|rem)$/)) {
            isValid = false;
            errorMessages.push('Invalid logo width format. Must be a number followed by a unit (e.g., 150px, 50%)');
        }
        
        if (!isValid) {
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: errorMessages.join('<br>'),
            });
            return;
        }
        
        // Show loading state
        Swal.fire({
            title: 'Updating template...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Submit form if validation passes
        $.ajax({
            url: 'php_action/updatePrintTemplate.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Hide modal and remove backdrop
                    $('#editTemplateModal').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                    
                    // Reload the table
                    if (typeof table !== 'undefined') {
                        table.ajax.reload();
                    }
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message || 'Template updated successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to update template',
                        footer: response.debug ? `Debug info: ${JSON.stringify(response.debug)}` : null
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', {xhr, status, error});
                let errorMessage = 'Failed to update template. Please try again.';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                    footer: `Status: ${status}, Error: ${error}`
                });
            }
        });
    });
});

function editTemplate(templateName) {
    // Show loading state
    Swal.fire({
        title: 'Loading template...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: 'php_action/fetchPrintTemplate.php',
        type: 'POST',
        data: { template_name: templateName },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            
            if (response.success) {
                const template = response.template;
                
                // Populate form fields
                $('#edit_template_name').val(template.template_name);
                $('#edit_page_size').val(template.page_size);
                $('#edit_orientation').val(template.orientation);
                $('#edit_font_family').val(template.font_family);
                $('#edit_font_size').val(template.font_size);
                $('#edit_margin_top').val(template.margin_top);
                $('#edit_margin_right').val(template.margin_right);
                $('#edit_margin_bottom').val(template.margin_bottom);
                $('#edit_margin_left').val(template.margin_left);
                $('#edit_header_alignment').val(template.header_alignment);
                $('#edit_logo_path').val(template.logo_path);
                $('#edit_logo_width').val(template.logo_width);
                $('#edit_footer_text').val(template.footer_text);
                
                // Show modal
                $('#editTemplateModal').modal('show');
            } else {
                Swal.fire('Error', response.message || 'Failed to fetch template', 'error');
            }
        },
        error: function(xhr, status, error) {
            Swal.close();
            console.error('AJAX error:', {xhr, status, error});
            Swal.fire('Error', 'Failed to fetch template details', 'error');
        }
    });
}

function deleteTemplate(templateName) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'php_action/deletePrintTemplate.php',
                type: 'POST',
                data: { template_name: templateName },
                success: function(response) {
                    try {
                        var data = JSON.parse(response);
                        if(data.success) {
                            table.ajax.reload();
                            Swal.fire('Deleted!', 'Template has been deleted.', 'success');
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    } catch(e) {
                        console.error('JSON parse error:', e);
                        Swal.fire('Error', 'Invalid server response', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    Swal.fire('Error', 'Failed to delete template', 'error');
                }
            });
        }
    });
}

function previewTemplate(templateName) {
    window.open('print_preview.php?template=' + encodeURIComponent(templateName), '_blank');
}

function previewLogo(button) {
    const logoPath = $(button).closest('.input-group').find('input').val();
    if (logoPath) {
        window.open(logoPath, '_blank');
    } else {
        Swal.fire('Error', 'Please enter a logo path first.', 'error');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?> 