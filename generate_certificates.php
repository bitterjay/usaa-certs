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
    error_log("Generating PDF with " . $recordCount . " pages");
    
    // Make sure no output has been sent yet
    if (headers_sent($file, $linenum)) {
        error_log("Headers already sent in $file on line $linenum");
        echo "<div style='color: red; padding: 20px; background: #fff; border: 2px solid #aa1f2e; margin: 20px;'>";
        echo "<h2>Error: Unable to download certificates</h2>";
        echo "<p>Headers have already been sent, which prevents the download from working properly.</p>";
        echo "<p>Please try again or contact support if this problem persists.</p>";
        echo "</div>";
        exit;
    } else {
        error_log("Setting headers for PDF download");
    }
    
    // Explicitly clear any previous output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Generate the PDF content as a string first
    error_log("Generating PDF content as string");
    $pdf_content = $pdf->Output('S');
    $pdf_size = strlen($pdf_content);
    error_log("PDF size: " . $pdf_size . " bytes");
    
    // Create a temporary file for the PDF and save it
    $temp_dir = sys_get_temp_dir();
    $temp_file = tempnam($temp_dir, 'usaa_certs_');
    file_put_contents($temp_file, $pdf_content);
    
    // Store the PDF file path and information in the session
    $_SESSION['pdf_file'] = $temp_file;
    $_SESSION['pdf_filename'] = $filename;
    $_SESSION['record_count'] = $recordCount;
    
    // Log the total processing time
    $endTime = microtime(true);
    $processingTime = $endTime - $startTime;
    error_log("Total processing time: " . round($processingTime, 2) . " seconds for $recordCount certificates");
    
    // Output the processing complete page with confirmation to proceed to download
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>USA Archery Certificate Generator - Processing Complete</title>
        <style>
            @font-face {
                font-family: 'Poppins';
                src: url('fonts/Poppins-Bold.ttf') format('truetype');
                font-weight: bold;
                font-style: normal;
            }
            
            body {
                font-family: 'Poppins', Arial, sans-serif;
                line-height: 1.6;
                margin: 0;
                padding: 20px;
                background-color: #f5f5f5;
                position: relative;
                min-height: 100vh;
            }
            
            .container {
                max-width: 800px;
                margin: 0 auto;
                background-color: white;
                padding: 40px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                text-align: center;
                margin-top: 60px;
            }
            
            h1 {
                color: #333;
                margin-bottom: 20px;
            }
            
            .processing-icon {
                display: inline-block;
                width: 80px;
                height: 80px;
                background-color: #4CAF50;
                border-radius: 50%;
                margin-bottom: 20px;
                position: relative;
            }
            
            .processing-icon:after {
                content: '';
                position: absolute;
                top: 25px;
                left: 28px;
                width: 25px;
                height: 15px;
                border-left: 3px solid white;
                border-bottom: 3px solid white;
                transform: rotate(-45deg);
            }
            
            .button {
                background-color: #aa1f2e; /* USA Archery Red */
                color: white;
                padding: 15px 30px;
                border: none;
                border-radius: 4px;
                font-size: 20px;
                font-weight: bold;
                cursor: pointer;
                margin: 20px 0;
                display: inline-block;
                text-decoration: none;
                transition: background-color 0.3s;
                animation: pulse 2s infinite;
            }
            
            @keyframes pulse {
                0% {
                    transform: scale(1);
                    box-shadow: 0 0 0 0 rgba(170, 31, 46, 0.7);
                }
                
                70% {
                    transform: scale(1.05);
                    box-shadow: 0 0 0 10px rgba(170, 31, 46, 0);
                }
                
                100% {
                    transform: scale(1);
                    box-shadow: 0 0 0 0 rgba(170, 31, 46, 0);
                }
            }
            
            .button:hover {
                background-color: #8e1926;
                animation: none;
                transform: scale(1.05);
            }
            
            .certificate-info {
                margin: 20px 0;
                padding: 15px;
                background-color: #f9f9f9;
                border-radius: 4px;
                text-align: left;
            }
            
            .home-link {
                margin-top: 20px;
                display: inline-block;
                color: #1c355e; /* USA Archery Blue */
                text-decoration: none;
            }
            
            .home-link:hover {
                text-decoration: underline;
            }
            
            .version {
                position: fixed;
                bottom: 10px;
                right: 20px;
                font-size: 12px;
                color: #888;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="processing-icon"></div>
            <h1>Certificate Processing Complete</h1>
            
            <p>Your certificates have been processed successfully and are ready for download.</p>
            
            <div class="certificate-info">
                <p><strong>File:</strong> <?php echo htmlspecialchars($filename); ?></p>
                <p><strong>Certificates Generated:</strong> <?php echo $recordCount; ?></p>
                <p><strong>Processing Time:</strong> <?php echo round($processingTime, 2); ?> seconds</p>
            </div>
            
            <div style="margin: 30px 0;">
                <p><strong>Important:</strong> Click the button below to proceed to the download page.</p>
                <a href="download_prompt.php" class="button">DOWNLOAD YOUR CERTIFICATES</a>
                <p style="font-size: 14px; color: #666; margin-top: 5px;">You must click this button to access your generated certificates.</p>
            </div>
            
            <a href="index.php" class="home-link">Cancel and Return to Generator</a>
        </div>
        
        <div class="version">v<?php echo isset($app_version) ? $app_version : '1.4.3'; ?></div>
    </body>
    </html>
    <?php
    exit;
    
} catch (Exception $e) {
    error_log('Certificate Generation Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    $_SESSION['error'] = 'Error generating certificates. Please try again.';
    header('Location: index.php');
    exit;
}
?> 