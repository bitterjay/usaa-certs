<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');
error_reporting(E_ALL);

// Test database connection and other dependencies
require 'vendor/autoload.php';

try {
    // Test FPDF
    $pdf = new FPDF();
    
    // Test PhpSpreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    
    echo "All dependencies are working correctly.";
} catch (Exception $e) {
    error_log("Error testing dependencies: " . $e->getMessage());
    echo "Error occurred. Check php_errors.log for details.";
} 