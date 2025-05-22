<?php

/**
 * Standardized currency formatting function for the guest system
 * 
 * @param float|string $amount The amount to format
 * @param string $currency The currency symbol/code (default: ETB)
 * @param bool $includeSymbol Whether to include the currency symbol (default: true)
 * @return string Formatted amount
 */
function formatGuestCurrency($amount, $currency = 'ETB', $includeSymbol = true) {
    // Convert amount to float to ensure proper formatting
    $amount = floatval($amount);
    
    // Format with 2 decimal places, thousands separator
    $formatted = number_format($amount, 2, '.', ',');
    
    // Return with or without currency symbol based on parameter
    return $includeSymbol ? $currency . ' ' . $formatted : $formatted;
}

/**
 * Format a number for display in reports (no currency symbol)
 * 
 * @param float|string $number The number to format
 * @param int $decimals Number of decimal places (default: 2)
 * @return string Formatted number
 */
function formatNumber($number, $decimals = 2) {
    return number_format(floatval($number), $decimals, '.', ',');
}

/**
 * Clean and validate amount input
 * 
 * @param mixed $amount The amount to clean
 * @return float Cleaned amount
 */
function cleanAmount($amount) {
    // Remove any currency symbols and thousands separators
    $clean = preg_replace('/[^0-9.-]/', '', $amount);
    return floatval($clean);
} 