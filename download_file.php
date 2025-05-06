<?php
session_start();

// Enable error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'pdf_generation_errors.log');
error_reporting(E_ALL);

// Debug information
error_log("download_file.php accessed - Session data: " . json_encode($_SESSION));

// Check if the download info is available in the session
if (!isset($_SESSION['pdf_file']) || !file_exists($_SESSION['pdf_file'])) {
    error_log("PDF file not found: " . (isset($_SESSION['pdf_file']) ? $_SESSION['pdf_file'] : 'Session variable not set'));
    
    // Show error with debug information
    echo '<div style="color: red; padding: 20px; background: #fff; border: 2px solid #aa1f2e; margin: 20px;">';
    echo '<h2>Error: Unable to download certificates</h2>';
    echo '<p>The certificate file was not found or session information has been lost.</p>';
    echo '<p>Please try generating the certificates again.</p>';
    echo '<a href="index.php" style="display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #1c355e; color: white; text-decoration: none; border-radius: 4px;">Return to Generator</a>';
    echo '</div>';
    exit;
}

// Get the file information
$file_path = $_SESSION['pdf_file'];
$file_name = isset($_SESSION['pdf_filename']) ? $_SESSION['pdf_filename'] : 'USAA_Certificates.pdf';

// Debug file information
error_log("File info - Path: $file_path, Name: $file_name, Exists: " . (file_exists($file_path) ? 'Yes' : 'No') . ", Size: " . (file_exists($file_path) ? filesize($file_path) : 'N/A'));

// Verify file can be read
if (!is_readable($file_path)) {
    error_log("File is not readable: $file_path");
    echo '<div style="color: red; padding: 20px; background: #fff; border: 2px solid #aa1f2e; margin: 20px;">';
    echo '<h2>Error: Unable to read certificate file</h2>';
    echo '<p>The server cannot read the certificate file due to permission issues.</p>';
    echo '<p>Please try generating the certificates again or contact support.</p>';
    echo '<a href="index.php" style="display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #1c355e; color: white; text-decoration: none; border-radius: 4px;">Return to Generator</a>';
    echo '</div>';
    exit;
}

try {
    // Set headers for file download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');
    
    // Clear any output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Output the file content
    readfile($file_path);
    
    // Log the download
    error_log("PDF downloaded successfully: " . $file_name);
    
    // Exit to prevent any additional output
    exit;
} catch (Exception $e) {
    error_log("Error during download: " . $e->getMessage());
    echo '<div style="color: red; padding: 20px; background: #fff; border: 2px solid #aa1f2e; margin: 20px;">';
    echo '<h2>Error during download</h2>';
    echo '<p>An error occurred while trying to download the certificates.</p>';
    echo '<p>Please try again or contact support.</p>';
    echo '<a href="index.php" style="display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #1c355e; color: white; text-decoration: none; border-radius: 4px;">Return to Generator</a>';
    echo '</div>';
    exit;
}
?> 