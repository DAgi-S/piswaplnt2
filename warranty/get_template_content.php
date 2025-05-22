<?php
require_once __DIR__ . '/../includes/auth.php';
require_once 'warranty_terms_functions.php';

// Check if user is logged in
if (!isset($_SESSION['userId'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

if (!isset($_POST['template_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Template ID is required']);
    exit();
}

$templateId = (int)$_POST['template_id'];
$template = getTemplateById($templateId);

if ($template) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'template_name' => $template['template_name'],
            'business_sector' => $template['business_sector'],
            'terms_content' => $template['terms_content']
        ]
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Template not found']);
} 