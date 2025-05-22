<?php
require_once __DIR__ . '/../php_action/db_connect.php';

/**
 * Get all active terms templates
 */
function getAllTermsTemplates() {
    global $connect;
    $sql = "SELECT id, template_name, business_sector FROM warranty_terms_templates WHERE is_active = 1 ORDER BY business_sector, template_name";
    $result = $connect->query($sql);
    
    $templates = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $templates[] = $row;
        }
    }
    return $templates;
}

/**
 * Get template content by ID
 */
function getTemplateById($id) {
    global $connect;
    $id = (int)$id;
    
    $sql = "SELECT * FROM warranty_terms_templates WHERE id = ? AND is_active = 1";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Get templates by business sector
 */
function getTemplatesByBusinessSector($sector) {
    global $connect;
    
    $sql = "SELECT * FROM warranty_terms_templates WHERE business_sector = ? AND is_active = 1";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $sector);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $templates = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $templates[] = $row;
        }
    }
    return $templates;
}

/**
 * Get all unique business sectors
 */
function getAllBusinessSectors() {
    global $connect;
    $sql = "SELECT DISTINCT business_sector FROM warranty_terms_templates WHERE is_active = 1 ORDER BY business_sector";
    $result = $connect->query($sql);
    
    $sectors = array();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sectors[] = $row['business_sector'];
        }
    }
    return $sectors;
} 