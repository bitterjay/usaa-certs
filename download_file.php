<?php
session_start();

// Check if the download info is available in the session
if (!isset($_SESSION['pdf_file']) || !file_exists($_SESSION['pdf_file'])) {
    header('Location: index.php');
    exit;
}

// Get the file information
$file_path = $_SESSION['pdf_file'];
$file_name = isset($_SESSION['pdf_filename']) ? $_SESSION['pdf_filename'] : 'USAA_Certificates.pdf';

// Set headers for file download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: max-age=0');
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
?> 