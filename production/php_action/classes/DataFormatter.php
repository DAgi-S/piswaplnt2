<?php

class DataFormatter {
    private static $instance = null;
    private $dateFormat = 'Y-m-d';
    private $timeFormat = 'H:i:s';
    private $datetimeFormat = 'Y-m-d H:i:s';
    private $numberDecimals = 2;
    private $thousandsSeparator = ',';
    private $decimalSeparator = '.';
    private $currencySymbol = '$';

    private function __construct() {
        // Load settings from database or config file if needed
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function formatDate($value, $format = null) {
        if (empty($value)) return null;
        
        try {
            $date = is_numeric($value) ? 
                DateTime::createFromFormat('U', $value) : 
                new DateTime($value);
                
            return $date->format($format ?? $this->dateFormat);
        } catch (Exception $e) {
            error_log("Date formatting error: " . $e->getMessage());
            return $value;
        }
    }

    public function formatTime($value, $format = null) {
        if (empty($value)) return null;
        
        try {
            $date = is_numeric($value) ? 
                DateTime::createFromFormat('U', $value) : 
                new DateTime($value);
                
            return $date->format($format ?? $this->timeFormat);
        } catch (Exception $e) {
            error_log("Time formatting error: " . $e->getMessage());
            return $value;
        }
    }

    public function formatDateTime($value, $format = null) {
        if (empty($value)) return null;
        
        try {
            $date = is_numeric($value) ? 
                DateTime::createFromFormat('U', $value) : 
                new DateTime($value);
                
            return $date->format($format ?? $this->datetimeFormat);
        } catch (Exception $e) {
            error_log("DateTime formatting error: " . $e->getMessage());
            return $value;
        }
    }

    public function formatNumber($value, $decimals = null) {
        if (!is_numeric($value)) return $value;
        
        return number_format(
            $value,
            $decimals ?? $this->numberDecimals,
            $this->decimalSeparator,
            $this->thousandsSeparator
        );
    }

    public function formatCurrency($value, $decimals = null) {
        if (!is_numeric($value)) return $value;
        
        return $this->currencySymbol . $this->formatNumber($value, $decimals);
    }

    public function formatPercentage($value, $decimals = 1) {
        if (!is_numeric($value)) return $value;
        
        return $this->formatNumber($value, $decimals) . '%';
    }

    public function parseDate($value) {
        if (empty($value)) return null;
        
        try {
            $date = DateTime::createFromFormat($this->dateFormat, $value);
            return $date ? $date->format('Y-m-d') : null;
        } catch (Exception $e) {
            error_log("Date parsing error: " . $e->getMessage());
            return null;
        }
    }

    public function parseDateTime($value) {
        if (empty($value)) return null;
        
        try {
            $date = DateTime::createFromFormat($this->datetimeFormat, $value);
            return $date ? $date->format('Y-m-d H:i:s') : null;
        } catch (Exception $e) {
            error_log("DateTime parsing error: " . $e->getMessage());
            return null;
        }
    }

    public function parseNumber($value) {
        if (empty($value)) return null;
        
        // Remove thousand separators and convert decimal separator
        $value = str_replace($this->thousandsSeparator, '', $value);
        $value = str_replace($this->decimalSeparator, '.', $value);
        
        return is_numeric($value) ? floatval($value) : null;
    }

    public function parseCurrency($value) {
        if (empty($value)) return null;
        
        // Remove currency symbol and any whitespace
        $value = str_replace($this->currencySymbol, '', $value);
        $value = trim($value);
        
        return $this->parseNumber($value);
    }

    public function parsePercentage($value) {
        if (empty($value)) return null;
        
        // Remove % symbol and any whitespace
        $value = str_replace('%', '', $value);
        $value = trim($value);
        
        return $this->parseNumber($value);
    }

    // Setters for format configuration
    public function setDateFormat($format) {
        $this->dateFormat = $format;
    }

    public function setTimeFormat($format) {
        $this->timeFormat = $format;
    }

    public function setDateTimeFormat($format) {
        $this->datetimeFormat = $format;
    }

    public function setNumberFormat($decimals, $thousandsSep, $decimalSep) {
        $this->numberDecimals = $decimals;
        $this->thousandsSeparator = $thousandsSep;
        $this->decimalSeparator = $decimalSep;
    }

    public function setCurrencySymbol($symbol) {
        $this->currencySymbol = $symbol;
    }
} 