<?php
// This is a simple script to test PDF downloading
require('fpdf.php');

// Enable error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'download_test_errors.log');
error_reporting(E_ALL);

error_log("Starting download test");

try {
    // Create a simple PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'PDF Download Test');
    $pdf->Ln(20);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'This is a test PDF to verify download functionality.');
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'If you can see this PDF, downloads are working correctly.');
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Generated: ' . date('Y-m-d H:i:s'));
    
    error_log("PDF created successfully");
    
    // Check if headers already sent
    if (headers_sent($filename, $linenum)) {
        error_log("Headers already sent in $filename on line $linenum");
        echo "<p>Error: Headers already sent. Cannot download PDF.</p>";
        exit;
    }
    
    // Disable output buffering - make sure nothing has been sent to browser yet
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set download headers
    error_log("Setting headers for PDF download");
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="download_test.pdf"');
    header('Cache-Control: max-age=0');
    
    // Output PDF for download
    error_log("Sending PDF to browser");
    $pdf->Output('D', 'download_test.pdf');
    
    // This should never be executed if PDF is downloaded correctly
    error_log("Code executed after PDF output - this shouldn't happen");
    
} catch (Exception $e) {
    error_log("Download test error: " . $e->getMessage());
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 