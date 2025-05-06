<?php
session_start();

// Check if files are available in session
if (!isset($_SESSION['excel_file']) || !isset($_SESSION['background_image'])) {
    header('Location: index.php');
    exit;
}

// Function to parse CSV/Excel file
function parseInputFile($file) {
    $data = [];
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    if ($extension === 'csv') {
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Skip header row
            fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== FALSE) {
                if (!empty($row[0]) || !empty($row[1])) {
                    $data[] = [
                        'fullName' => trim($row[0] . ' ' . $row[1]),
                        'columnC' => isset($row[2]) ? trim($row[2]) : '',
                        'columnD' => isset($row[3]) ? trim($row[3]) : '',
                        'columnE' => isset($row[4]) ? trim($row[4]) : ''
                    ];
                }
            }
            fclose($handle);
        }
    } else {
        // For Excel files, we'll need PHPExcel or similar library
        die('Please use CSV format for now. Excel support coming soon.');
    }
    
    return $data;
}

try {
    // Create PDF using basic PHP GD
    $data = parseInputFile($_SESSION['excel_file']);
    
    // Set up PDF headers
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="USAA_Certificates.pdf"');
    
    // Create PDF content
    $pdf_content = "%PDF-1.4\n";
    
    // Add a basic PDF structure (this is a simplified version)
    foreach ($data as $index => $entry) {
        // Create a new page
        $pdf_content .= "\n% Page " . ($index + 1) . "\n";
        $pdf_content .= "1 0 obj\n";
        $pdf_content .= "<< /Type /Page\n";
        $pdf_content .= "   /Contents 2 0 R\n";
        $pdf_content .= ">>\n";
        $pdf_content .= "endobj\n";
        
        // Add page content
        $pdf_content .= "2 0 obj\n";
        $pdf_content .= "<< /Length " . strlen($entry['fullName']) . " >>\n";
        $pdf_content .= "stream\n";
        $pdf_content .= "BT\n";
        $pdf_content .= "/F1 24 Tf\n";
        $pdf_content .= "1 0 0 rg\n"; // Red color for name
        $pdf_content .= "100 500 Td\n";
        $pdf_content .= "(" . $entry['fullName'] . ") Tj\n";
        $pdf_content .= "ET\n";
        $pdf_content .= "endstream\n";
        $pdf_content .= "endobj\n";
    }
    
    // Output the PDF
    echo $pdf_content;
    
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