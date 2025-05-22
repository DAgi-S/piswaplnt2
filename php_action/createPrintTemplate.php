<?php
require_once 'core.php';
require_once 'db_connect.php';

// Set header to JSON
header('Content-Type: application/json');

$response = array();

try {
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Validate required fields
    $required_fields = array(
        'template_name', 'page_size', 'orientation', 'font_family', 'font_size',
        'margin_top', 'margin_right', 'margin_bottom', 'margin_left',
        'header_alignment', 'logo_width', 'footer_text'
    );

    // Initialize response array
    $response = array('success' => false, 'message' => '', 'debug' => array());

    // Log received POST data for debugging
    $response['debug']['post_data'] = $_POST;

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("$field is required");
        }
    }

    // Sanitize input
    $template_name = mysqli_real_escape_string($connect, trim($_POST['template_name']));
    $page_size = mysqli_real_escape_string($connect, trim($_POST['page_size']));
    $orientation = mysqli_real_escape_string($connect, trim($_POST['orientation']));
    $font_family = mysqli_real_escape_string($connect, trim($_POST['font_family']));
    $font_size = mysqli_real_escape_string($connect, trim($_POST['font_size']));
    $margin_top = mysqli_real_escape_string($connect, trim($_POST['margin_top']));
    $margin_right = mysqli_real_escape_string($connect, trim($_POST['margin_right']));
    $margin_bottom = mysqli_real_escape_string($connect, trim($_POST['margin_bottom']));
    $margin_left = mysqli_real_escape_string($connect, trim($_POST['margin_left']));
    $header_alignment = mysqli_real_escape_string($connect, trim($_POST['header_alignment']));
    $logo_path = isset($_POST['logo_path']) ? mysqli_real_escape_string($connect, trim($_POST['logo_path'])) : 'assets/images/logo.png';
    $logo_width = mysqli_real_escape_string($connect, trim($_POST['logo_width']));
    $footer_text = mysqli_real_escape_string($connect, trim($_POST['footer_text']));

    // Check if template name already exists
    $check_sql = "SELECT id FROM print_settings WHERE template_name = ?";
    $stmt = $connect->prepare($check_sql);
    $stmt->bind_param('s', $template_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Template name already exists");
    }

    // Insert new template
    $insert_sql = "INSERT INTO print_settings (
        template_name, page_size, orientation, font_family, font_size,
        margin_top, margin_right, margin_bottom, margin_left,
        header_alignment, logo_path, logo_width, footer_text,
        created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $connect->prepare($insert_sql);
    $stmt->bind_param('sssssssssssss',
        $template_name, $page_size, $orientation, $font_family, $font_size,
        $margin_top, $margin_right, $margin_bottom, $margin_left,
        $header_alignment, $logo_path, $logo_width, $footer_text
    );

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Template created successfully';
        $response['debug']['template_id'] = $connect->insert_id;
    } else {
        throw new Exception("Error creating template: " . $stmt->error);
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['debug']['error'] = $e->getTraceAsString();
} finally {
    // Ensure proper JSON response
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
} 