<?php
require_once '../php_action/core.php';
require_once '../includes/auth_check.php';

try {
    // Get default currency settings
    $sql = "SELECT * FROM currency_settings WHERE is_default = 1 AND status = 1 LIMIT 1";
    $result = $connect->query($sql);
    
    if ($result->num_rows > 0) {
        $settings = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'settings' => [
                'code' => $settings['currency_code'],
                'symbol' => $settings['currency_symbol'],
                'position' => $settings['currency_position'],
                'thousandSeparator' => $settings['thousand_separator'],
                'decimalSeparator' => $settings['decimal_separator'],
                'decimals' => intval($settings['decimals'])
            ]
        ]);
    } else {
        // Fallback default settings
        echo json_encode([
            'success' => true,
            'settings' => [
                'code' => 'ETB',
                'symbol' => 'Br',
                'position' => 'left',
                'thousandSeparator' => ',',
                'decimalSeparator' => '.',
                'decimals' => 2
            ]
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 