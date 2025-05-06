<?php
session_start();
require('fpdf.php');

// Check if files are available in session
if (!isset($_SESSION['excel_file']) || !isset($_SESSION['background_image'])) {
    header('Location: index.php');
    exit;
}

// Function to parse CSV file
function parseInputFile($file) {
    $data = [];
    if (($handle = fopen($file, "r")) !== FALSE) {
        // Skip header row
        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== FALSE) {
            if (!empty($row[0]) || !empty($row[1])) {
                $data[] = [
                    'fullName' => strtoupper(trim($row[0] . ' ' . $row[1])), // Uppercase the names
                    'columnC' => isset($row[2]) ? strtoupper(trim($row[2])) : '', // Uppercase all data
                    'columnD' => isset($row[3]) ? strtoupper(trim($row[3])) : '',
                    'columnE' => isset($row[4]) ? strtoupper(trim($row[4])) : ''
                ];
            }
        }
        fclose($handle);
    }
    return $data;
}

try {
    // Get position values from session with fallbacks
    $nameYPosition = isset($_SESSION['name_y_pos']) ? $_SESSION['name_y_pos'] : 50;
    $detailsYPosition = isset($_SESSION['details_y_pos']) ? $_SESSION['details_y_pos'] : 60;
    $nameFontSize = isset($_SESSION['name_font_size']) ? $_SESSION['name_font_size'] : 30;
    $detailsFontSize = isset($_SESSION['details_font_size']) ? $_SESSION['details_font_size'] : 16;
    
    // Parse the CSV data
    $data = parseInputFile($_SESSION['excel_file']);
    
    // Create PDF
    class CertificatePDF extends FPDF {
        // Variables to store position percentages
        public $nameYPercent = 50;
        public $detailsYPercent = 60;
        public $nameFontSize = 30;
        public $detailsFontSize = 16;
        
        // Set text position values
        public function setPositions($nameY, $detailsY) {
            $this->nameYPercent = $nameY;
            $this->detailsYPercent = $detailsY;
        }
        
        // Set font sizes
        public function setFontSizes($nameSize, $detailsSize) {
            $this->nameFontSize = $nameSize;
            $this->detailsFontSize = $detailsSize;
        }
        
        // Header - will contain background image
        function Header() {
            if (isset($_SESSION['background_image'])) {
                // Add background image
                $this->Image($_SESSION['background_image'], 0, 0, $this->GetPageWidth(), $this->GetPageHeight());
            }
        }
        
        // Custom method to write text with alternating colors
        function WriteDetailText($x, $y, $text, $detailsArray) {
            // Save the current position
            $this->SetXY($x, $y);
            
            // Initialize
            $currentX = $x;
            $pipe = '      |      '; // Increased spacing around pipe
            $pipeWidth = $this->GetStringWidth($pipe);
            
            // Set color for first part (blue for text)
            $this->SetTextColor(28, 53, 94); // New blue #1c355e
            
            foreach($detailsArray as $index => $detail) {
                if ($index > 0) {
                    // Save current position
                    $currentX = $this->GetX();
                    
                    // Change color to red for pipe
                    $this->SetTextColor(170, 31, 46); // Red #aa1f2e
                    
                    // Add pipe
                    $this->Cell($pipeWidth, 10, $pipe, 0, 0, 'L');
                    
                    // Reset to blue color for text
                    $this->SetTextColor(28, 53, 94); // New blue #1c355e
                }
                
                // Add detail text
                $detailWidth = $this->GetStringWidth($detail);
                $this->Cell($detailWidth, 10, $detail, 0, 0, 'L');
            }
        }
    }

    // Initialize PDF
    $pdf = new CertificatePDF('L', 'mm', 'Letter'); // Landscape orientation
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPositions($nameYPosition, $detailsYPosition);
    $pdf->setFontSizes($nameFontSize, $detailsFontSize);
    
    // Add Poppins font
    $pdf->AddFont('Poppins', 'B', 'poppins.php');

    // Add a page for each entry
    foreach ($data as $entry) {
        $pdf->AddPage();
        
        // Get page dimensions
        $pageWidth = $pdf->GetPageWidth();
        $pageHeight = $pdf->GetPageHeight();
        
        // Calculate Y positions based on percentages (convert % to mm)
        $nameY = ($pdf->nameYPercent / 100) * $pageHeight;
        $detailsY = ($pdf->detailsYPercent / 100) * $pageHeight;
        
        // Set font for name - using Poppins with red color and user-defined size
        $pdf->SetFont('Poppins', 'B', $pdf->nameFontSize);
        $pdf->SetTextColor(170, 31, 46); // Red #aa1f2e
        
        // Calculate text width for centering
        $nameWidth = $pdf->GetStringWidth($entry['fullName']);
        $x = ($pageWidth - $nameWidth) / 2;
        
        // Add name (centered at calculated Y position)
        $pdf->SetXY($x, $nameY);
        $pdf->Cell($nameWidth, 10, $entry['fullName'], 0, 1, 'C');
        
        // Collect details that are not empty
        $details = [];
        if (!empty($entry['columnC'])) $details[] = $entry['columnC'];
        if (!empty($entry['columnD'])) $details[] = $entry['columnD'];
        if (!empty($entry['columnE'])) $details[] = $entry['columnE'];
        
        // Add combined additional fields (centered at calculated Y position) with alternating colors
        if (!empty($details)) {
            $pdf->SetFont('Poppins', 'B', $pdf->detailsFontSize);
            
            // Calculate total width
            $totalWidth = 0;
            $pipe = '      |      '; // Increased spacing around pipe
            $pipeWidth = $pdf->GetStringWidth($pipe);
            
            foreach($details as $index => $detail) {
                if ($index > 0) {
                    $totalWidth += $pipeWidth;
                }
                $totalWidth += $pdf->GetStringWidth($detail);
            }
            
            // Calculate starting X position for centering
            $startX = ($pageWidth - $totalWidth) / 2;
            
            // Write details with colored pipes
            $pdf->WriteDetailText($startX, $detailsY, $details, $details);
        }
    }

    // Output PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="USAA_Certificates.pdf"');
    $pdf->Output('D', 'USAA_Certificates.pdf');
    
    // Clean up
    unlink($_SESSION['excel_file']);
    unlink($_SESSION['background_image']);
    unset($_SESSION['excel_file']);
    unset($_SESSION['background_image']);
    
} catch (Exception $e) {
    error_log('Certificate Generation Error: ' . $e->getMessage());
    $_SESSION['error'] = 'Error generating certificates. Please try again.';
    header('Location: index.php');
    exit;
}
?> 