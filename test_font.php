<?php
require('fpdf.php');

class TestPDF extends FPDF {
    function Header() {
        // Just a blank header
    }
}

try {
    // Initialize PDF
    $pdf = new TestPDF();
    
    // Check if Poppins font file exists
    if (!file_exists('font/poppins.php') || !file_exists('font/poppins.z')) {
        throw new Exception("Poppins font files not found in font/ directory");
    }
    
    // Try to add Poppins font
    $pdf->AddFont('Poppins', 'B', 'poppins.php');
    
    // Start PDF generation
    $pdf->AddPage();
    
    // Basic text with default font
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Testing default font (Arial)', 0, 1);
    
    // Try to use Poppins font
    $pdf->SetFont('Poppins', 'B', 14);
    $pdf->Cell(0, 10, 'Testing Poppins Bold font', 0, 1);
    
    // Testing pipe separators with spacing
    $pdf->SetFont('Poppins', 'B', 12);
    
    // Red color for name
    $pdf->SetTextColor(170, 31, 46); // #aa1f2e
    $pdf->Cell(50, 10, 'NAME', 0, 0);
    
    // Blue color for details
    $pdf->SetTextColor(28, 53, 94); // #1c355e
    $pdf->Cell(0, 10, 'DETAIL ONE      |      DETAIL TWO      |      DETAIL THREE', 0, 1);
    
    // Output the result
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="font_test.pdf"');
    $pdf->Output('I', 'font_test.pdf');
    
} catch (Exception $e) {
    header('Content-Type: text/html');
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    
    // List contents of font directory
    echo "<h2>Font Directory Contents:</h2>";
    echo "<pre>";
    if (is_dir('font')) {
        $files = scandir('font');
        print_r($files);
    } else {
        echo "Font directory doesn't exist";
    }
    echo "</pre>";
}
?> 