<?php
// Basic download testing script
require('fpdf.php');

// Start output buffering to track any unexpected output
ob_start();

// Enable error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'download_test_errors.log');
error_reporting(E_ALL);

// Begin logging
error_log("Starting test_download.php");

try {
    // Create a simple PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Download Test - Forced Download');
    $pdf->Ln(20);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'This is a test PDF for verifying the download process.');
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Generated: ' . date('Y-m-d H:i:s'));
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'If you can see this PDF, direct downloads are working correctly.');
    
    // Check for any unexpected output
    $unexpected_output = ob_get_contents();
    if (!empty($unexpected_output)) {
        error_log("WARNING: Unexpected output detected before headers: " . $unexpected_output);
    }
    
    // Clear any output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Test if headers can be sent
    if (headers_sent($file, $line)) {
        error_log("ERROR: Headers already sent in $file on line $line");
        echo "<p>Error: Headers already sent in $file on line $line. Download will not work.</p>";
        exit;
    }
    
    // Force download approach 1 - standard method with specific headers
    error_log("Sending PDF with standard headers");
    
    // Set download headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="test_download.pdf"');
    header('Cache-Control: max-age=0');
    header('Content-Length: ' . strlen($pdf->Output('S')));
    header('Pragma: public');
    
    // Output the PDF as a string
    $pdf_content = $pdf->Output('S');
    echo $pdf_content;
    
    // If we got here, we should have sent the file
    error_log("PDF output complete");
    exit;

} catch (Exception $e) {
    // Log any errors
    error_log("Error in test_download.php: " . $e->getMessage());
    echo "<h1>Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 