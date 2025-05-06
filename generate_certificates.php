<?php
session_start();

// Check if files are available in session
if (!isset($_SESSION['excel_file']) || !isset($_SESSION['background_image'])) {
    header('Location: index.php');
    exit;
}

// Require Composer's autoloader
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use FPDF;

class CertificatePDF extends FPDF {
    protected $backgroundImage;

    public function setBackgroundImage($image) {
        $this->backgroundImage = $image;
    }

    public function Header() {
        if ($this->backgroundImage) {
            $this->Image($this->backgroundImage, 0, 0, $this->GetPageWidth(), $this->GetPageHeight());
        }
    }
}

try {
    // Load the Excel file
    $spreadsheet = IOFactory::load($_SESSION['excel_file']);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Create PDF
    $pdf = new CertificatePDF('L', 'mm', 'A4');
    $pdf->setBackgroundImage($_SESSION['background_image']);
    $pdf->SetAutoPageBreak(true, 0);
    
    // Get data from Excel
    $highestRow = $worksheet->getHighestRow();
    
    // Process each row (skip header row)
    for ($row = 2; $row <= $highestRow; $row++) {
        $firstName = trim($worksheet->getCell('A' . $row)->getValue());
        $lastName = trim($worksheet->getCell('B' . $row)->getValue());
        $columnC = trim($worksheet->getCell('C' . $row)->getValue());
        $columnD = trim($worksheet->getCell('D' . $row)->getValue());
        $columnE = trim($worksheet->getCell('E' . $row)->getValue());
        
        // Skip empty rows
        if (empty($firstName) && empty($lastName)) {
            continue;
        }
        
        // Add new page for each certificate
        $pdf->AddPage();
        
        // Set font for name
        $pdf->SetFont('Arial', 'B', 24);
        $pdf->SetTextColor(255, 0, 0); // Red color for name
        
        // Calculate center position for name
        $fullName = $firstName . ' ' . $lastName;
        $nameWidth = $pdf->GetStringWidth($fullName);
        $pageWidth = $pdf->GetPageWidth();
        $x = ($pageWidth - $nameWidth) / 2;
        
        // Add name
        $pdf->SetXY($x, 100);
        $pdf->Cell($nameWidth, 10, $fullName, 0, 1, 'C');
        
        // Set font for details
        $pdf->SetFont('Arial', '', 16);
        
        // Calculate total width of details line
        $detailsText = $columnC . ' | ' . $columnD . ' | ' . $columnE;
        $detailsWidth = $pdf->GetStringWidth($detailsText);
        $detailsX = ($pageWidth - $detailsWidth) / 2;
        
        // Position for details
        $pdf->SetXY($detailsX, 120);
        
        // Add first column (blue)
        $pdf->SetTextColor(0, 0, 255);
        $pdf->Write(10, $columnC . ' ');
        
        // Add first pipe (red)
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Write(10, '|');
        
        // Add second column (blue)
        $pdf->SetTextColor(0, 0, 255);
        $pdf->Write(10, ' ' . $columnD . ' ');
        
        // Add second pipe (red)
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Write(10, '|');
        
        // Add third column (blue)
        $pdf->SetTextColor(0, 0, 255);
        $pdf->Write(10, ' ' . $columnE);
    }
    
    // Clean up uploaded files
    unlink($_SESSION['excel_file']);
    unlink($_SESSION['background_image']);
    
    // Clear session
    unset($_SESSION['excel_file']);
    unset($_SESSION['background_image']);
    
    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="USAA_Certificates.pdf"');
    $pdf->Output('D', 'USAA_Certificates.pdf');
    
} catch (Exception $e) {
    // Log error
    error_log('Certificate Generation Error: ' . $e->getMessage());
    
    // Redirect to index with error
    $_SESSION['error'] = 'Error generating certificates. Please try again.';
    header('Location: index.php');
    exit;
}
?> 