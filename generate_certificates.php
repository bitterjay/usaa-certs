<?php
session_start();
require('fpdf.php');

// Enable error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'pdf_generation_errors.log');
error_reporting(E_ALL);

// Debug session variables
error_log("Starting certificate generation with session data: " . json_encode($_SESSION));

// Check if files are available in session
if (!isset($_SESSION['excel_file']) || !isset($_SESSION['background_image'])) {
    error_log("Missing required files in session");
    header('Location: index.php');
    exit;
}

// Check if temporary files still exist
if (!file_exists($_SESSION['excel_file']) || !file_exists($_SESSION['background_image'])) {
    error_log("Temporary files no longer exist - session may have expired");
    $_SESSION['error'] = 'File processing error. Please try uploading again.';
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
    
    // Start timer to measure processing time
    $startTime = microtime(true);
    
    // Parse the CSV data from the temporary file
    error_log("Parsing CSV file: " . $_SESSION['excel_file']);
    $data = parseInputFile($_SESSION['excel_file']);
    $recordCount = count($data);
    error_log("Found " . $recordCount . " records in CSV file");
    
    // Calculate estimated processing time (rough estimate)
    $estimatedTimePerRecord = 0.1; // 100ms per record
    $estimatedTotalTime = $recordCount * $estimatedTimePerRecord;
    error_log("Estimated processing time: " . round($estimatedTotalTime, 2) . " seconds");
    
    // For very large datasets, increase PHP execution time
    if ($recordCount > 50) {
        set_time_limit(max(300, $recordCount * 5)); // 5 seconds per record or minimum 5 minutes
        error_log("Increased time limit for large dataset");
    }
    
    // Create PDF
    class CertificatePDF extends FPDF {
        // Variables to store position percentages
        public $nameYPercent = 50;
        public $detailsYPercent = 60;
        public $nameFontSize = 30;
        public $detailsFontSize = 16;
        public $bgImage = '';
        
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
        
        // Set background image
        public function setBgImage($image) {
            $this->bgImage = $image;
        }
        
        // Header - will contain background image
        function Header() {
            if ($this->bgImage && file_exists($this->bgImage)) {
                // Add background image
                $this->Image($this->bgImage, 0, 0, $this->GetPageWidth(), $this->GetPageHeight());
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
    error_log("Initializing PDF");
    $pdf = new CertificatePDF('L', 'mm', 'Letter'); // Landscape orientation
    $pdf->SetAutoPageBreak(false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->setPositions($nameYPosition, $detailsYPosition);
    $pdf->setFontSizes($nameFontSize, $detailsFontSize);
    $pdf->setBgImage($_SESSION['background_image']);
    
    // Add Poppins font
    error_log("Adding Poppins font");
    if (!file_exists('font/poppins.php')) {
        error_log("Error: font/poppins.php not found");
    }
    $pdf->AddFont('Poppins', 'B', 'poppins.php');

    // Add a page for each entry
    $processedCount = 0;
    foreach ($data as $entry) {
        $pdf->AddPage();
        
        // Track progress for large datasets
        $processedCount++;
        if ($recordCount > 50 && $processedCount % 20 == 0) {
            $percentComplete = round(($processedCount / $recordCount) * 100);
            error_log("Certificate generation progress: $percentComplete% ($processedCount of $recordCount)");
        }
        
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

    // Prepare filename from original file
    $filename = 'USAA_Certificates.pdf';
    if (isset($_SESSION['excel_filename'])) {
        $base = pathinfo($_SESSION['excel_filename'], PATHINFO_FILENAME);
        $filename = 'USAA_Certificates_' . $base . '.pdf';
    }
    
    // Output PDF
    error_log("Generating PDF with " . count($data) . " pages");
    
    // Make sure no output has been sent yet
    if (headers_sent($file, $linenum)) {
        error_log("Headers already sent in $file on line $linenum");
    } else {
        error_log("Setting headers for PDF download");
    }
    
    // Explicitly clear any previous output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    error_log("Sending PDF to browser");
    $pdf->Output('D', $filename);
    
    // Log the total processing time
    $endTime = microtime(true);
    $processingTime = $endTime - $startTime;
    error_log("Total processing time: " . round($processingTime, 2) . " seconds for $recordCount certificates");
    
    // Note: PHP's temporary files are automatically deleted after the script finishes
    // So we don't need to clean them up manually
    
} catch (Exception $e) {
    error_log('Certificate Generation Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    $_SESSION['error'] = 'Error generating certificates. Please try again.';
    header('Location: index.php');
    exit;
}
?> 