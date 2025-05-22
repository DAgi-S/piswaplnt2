<?php
/**
 * Print Settings Management
 * 
 * This file centralizes and manages print layouts, styles, and settings
 * for different printable pages in the system.
 */

// Prevent direct access
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once 'db_connect.php';

/**
 * Get print styles for a specific template
 * @param string $template_name Template name
 * @return array Print settings
 */
function get_print_styles($template_name = 'default') {
    global $connect;
    
    try {
        if (!$connect) {
            throw new Exception("Database connection not available");
        }

        $stmt = $connect->prepare("SELECT * FROM print_settings WHERE template_name = ?");
        $stmt->bind_param('s', $template_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $settings = $result->fetch_assoc();
        
        if ($settings) {
            // Replace {year} in footer text if it exists
            if (!empty($settings['footer_text'])) {
                $settings['footer_text'] = str_replace('{year}', date('Y'), $settings['footer_text']);
            }
            
            return $settings;
        }
        
        // If no template found, insert default template
        $default_settings = [
            'template_name' => $template_name,
            'page_size' => 'A4',
            'orientation' => 'portrait',
            'margin_top' => '25mm',
            'margin_right' => '20mm',
            'margin_bottom' => '25mm',
            'margin_left' => '20mm',
            'font_family' => 'Arial, sans-serif',
            'font_size' => '12px',
            'header_alignment' => 'left',
            'logo_path' => 'assets/images/logo.png',
            'logo_width' => '150px',
            'footer_text' => 'Copyright © {year} Your Company Name. All rights reserved.'
        ];
        
        // Insert default settings
        $stmt = $connect->prepare("
            INSERT INTO print_settings (
                template_name, page_size, orientation, margin_top, margin_right,
                margin_bottom, margin_left, font_family, font_size, header_alignment,
                logo_path, logo_width, footer_text
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param('sssssssssssss', 
            $default_settings['template_name'],
            $default_settings['page_size'],
            $default_settings['orientation'],
            $default_settings['margin_top'],
            $default_settings['margin_right'],
            $default_settings['margin_bottom'],
            $default_settings['margin_left'],
            $default_settings['font_family'],
            $default_settings['font_size'],
            $default_settings['header_alignment'],
            $default_settings['logo_path'],
            $default_settings['logo_width'],
            $default_settings['footer_text']
        );
        
        $stmt->execute();
        
        return $default_settings;
    } catch (Exception $e) {
        error_log("Error in get_print_styles: " . $e->getMessage());
        return [
            'page_size' => 'A4',
            'orientation' => 'portrait',
            'margin_top' => '25mm',
            'margin_right' => '20mm',
            'margin_bottom' => '25mm',
            'margin_left' => '20mm',
            'font_family' => 'Arial, sans-serif',
            'font_size' => '12px',
            'header_alignment' => 'left',
            'logo_path' => 'assets/images/logo.png',
            'logo_width' => '150px',
            'footer_text' => 'Copyright © ' . date('Y') . ' Your Company Name. All rights reserved.'
        ];
    }
}

/**
 * Generate CSS based on print settings
 * @param string $template_name Template name
 * @return string CSS styles
 */
function generate_print_css($template_name = 'default') {
    $settings = get_print_styles($template_name);
    
    return "
        @page {
            size: {$settings['page_size']} {$settings['orientation']};
            margin: {$settings['margin_top']} {$settings['margin_right']} {$settings['margin_bottom']} {$settings['margin_left']};
        }
        
        body {
            font-family: {$settings['font_family']};
            font-size: {$settings['font_size']};
            line-height: 1.5;
            color: #333;
            background: #fff;
        }
        
        .print-header {
            text-align: {$settings['header_alignment']};
        }
        
        .print-logo {
            width: {$settings['logo_width']};
        }
        
        @media print {
            .preview-watermark {
                display: none;
            }
            
            .document-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
        }
    ";
}

/**
 * Get company logo path for printing
 * 
 * @return string The path to the company logo
 */
function get_print_logo() {
    global $connect;
    
    try {
        $stmt = $connect->prepare("SELECT logo_path FROM print_settings WHERE template_name = 'default'");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['logo_path'] ?? 'assets/images/logo.png';
    } catch (Exception $e) {
        error_log("Error in get_print_logo: " . $e->getMessage());
        return 'assets/images/logo.png';
    }
}

/**
 * Get company information for print documents
 * @return array Company information
 */
function get_print_company_info() {
    global $connect;
    
    try {
        if (!$connect) {
            throw new Exception("Database connection not available");
        }

        $stmt = $connect->prepare("SELECT * FROM company_info LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        $info = $result->fetch_assoc();
        
        if ($info) {
            return $info;
        }
        
        // If no company info found, insert default
        $default_info = [
            'name' => 'Your Company Name',
            'address' => '123 Business Street, City, Country',
            'phone' => '+1234567890',
            'email' => 'info@yourcompany.com',
            'website' => 'www.yourcompany.com'
        ];
        
        $stmt = $connect->prepare("
            INSERT INTO company_info (name, address, phone, email, website)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param('sssss',
            $default_info['name'],
            $default_info['address'],
            $default_info['phone'],
            $default_info['email'],
            $default_info['website']
        );
        
        $stmt->execute();
        
        return $default_info;
    } catch (Exception $e) {
        error_log("Error in get_print_company_info: " . $e->getMessage());
        return [
            'name' => 'Your Company Name',
            'address' => '123 Business Street, City, Country',
            'phone' => '+1234567890',
            'email' => 'info@yourcompany.com',
            'website' => 'www.yourcompany.com'
        ];
    }
}

/**
 * Save print settings for a template
 * @param string $template_name Template name
 * @param array $settings Print settings
 * @return bool Success status
 */
function save_print_settings($template_name, $settings) {
    global $connect;
    
    try {
        if (!$connect) {
            throw new Exception("Database connection not available");
        }

        // Validate required fields
        $required_fields = ['page_size', 'orientation', 'margin_top', 'margin_right', 
                          'margin_bottom', 'margin_left', 'font_family', 'font_size', 
                          'header_alignment', 'logo_path', 'logo_width', 'footer_text'];
        
        foreach ($required_fields as $field) {
            if (!isset($settings[$field]) || trim($settings[$field]) === '') {
                throw new Exception("Missing required field: " . $field);
            }
        }

        $settings['template_name'] = $template_name;
        
        $stmt = $connect->prepare("
            INSERT INTO print_settings (
                template_name, page_size, orientation, margin_top, margin_right,
                margin_bottom, margin_left, font_family, font_size, header_alignment,
                logo_path, logo_width, footer_text
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                page_size = VALUES(page_size),
                orientation = VALUES(orientation),
                margin_top = VALUES(margin_top),
                margin_right = VALUES(margin_right),
                margin_bottom = VALUES(margin_bottom),
                margin_left = VALUES(margin_left),
                font_family = VALUES(font_family),
                font_size = VALUES(font_size),
                header_alignment = VALUES(header_alignment),
                logo_path = VALUES(logo_path),
                logo_width = VALUES(logo_width),
                footer_text = VALUES(footer_text)
        ");
        
        $stmt->bind_param('sssssssssssss',
            $settings['template_name'],
            $settings['page_size'],
            $settings['orientation'],
            $settings['margin_top'],
            $settings['margin_right'],
            $settings['margin_bottom'],
            $settings['margin_left'],
            $settings['font_family'],
            $settings['font_size'],
            $settings['header_alignment'],
            $settings['logo_path'],
            $settings['logo_width'],
            $settings['footer_text']
        );
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Error in save_print_settings: " . $e->getMessage());
        throw $e;
    }
} 