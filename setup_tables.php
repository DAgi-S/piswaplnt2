<?php
// Database configuration for local environment
$localhost = "localhost";
$username = "root";
$password = "";
$dbname = "pistocklntmarch";

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=$localhost;dbname=$dbname",
        $username,
        $password,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        )
    );

    // Create print_settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS print_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            template_name VARCHAR(50) NOT NULL UNIQUE,
            page_size VARCHAR(20) NOT NULL DEFAULT 'A4',
            orientation VARCHAR(20) NOT NULL DEFAULT 'portrait',
            margin_top VARCHAR(20) NOT NULL DEFAULT '25mm',
            margin_right VARCHAR(20) NOT NULL DEFAULT '20mm',
            margin_bottom VARCHAR(20) NOT NULL DEFAULT '25mm',
            margin_left VARCHAR(20) NOT NULL DEFAULT '20mm',
            font_family VARCHAR(100) NOT NULL DEFAULT 'Arial, sans-serif',
            font_size VARCHAR(20) NOT NULL DEFAULT '12px',
            header_alignment VARCHAR(20) NOT NULL DEFAULT 'left',
            logo_path VARCHAR(255) DEFAULT 'assets/images/logo.png',
            logo_width VARCHAR(20) DEFAULT '150px',
            footer_text TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Create company_info table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS company_info (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            address TEXT,
            phone VARCHAR(50),
            email VARCHAR(100),
            website VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Insert default print settings
    $stmt = $pdo->prepare("
        INSERT INTO print_settings (
            template_name, page_size, orientation, margin_top, margin_right,
            margin_bottom, margin_left, font_family, font_size, header_alignment,
            logo_path, logo_width, footer_text
        ) VALUES (
            :template_name, :page_size, :orientation, :margin_top, :margin_right,
            :margin_bottom, :margin_left, :font_family, :font_size, :header_alignment,
            :logo_path, :logo_width, :footer_text
        ) ON DUPLICATE KEY UPDATE
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

    $default_settings = [
        'template_name' => 'default',
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

    $stmt->execute($default_settings);

    // Insert default company info
    $stmt = $pdo->prepare("
        INSERT INTO company_info (name, address, phone, email, website)
        VALUES (:name, :address, :phone, :email, :website)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            address = VALUES(address),
            phone = VALUES(phone),
            email = VALUES(email),
            website = VALUES(website)
    ");

    $default_company = [
        'name' => 'Your Company Name',
        'address' => '123 Business Street, City, Country',
        'phone' => '+1234567890',
        'email' => 'info@yourcompany.com',
        'website' => 'www.yourcompany.com'
    ];

    $stmt->execute($default_company);

    echo "Tables created and default data inserted successfully!";
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
} 