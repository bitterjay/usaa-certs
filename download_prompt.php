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
$record_count = isset($_SESSION['record_count']) ? $_SESSION['record_count'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USA Archery Certificate Generator - Download Ready</title>
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
        
        .success-icon {
            display: inline-block;
            width: 80px;
            height: 80px;
            background-color: #4CAF50;
            border-radius: 50%;
            margin-bottom: 20px;
            position: relative;
        }
        
        .success-icon:after {
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
        
        .download-button {
            background-color: #aa1f2e; /* USA Archery Red */
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 4px;
            font-size: 18px;
            cursor: pointer;
            margin: 20px 0;
            display: inline-block;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .download-button:hover {
            background-color: #8e1926;
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
        <div class="success-icon"></div>
        <h1>Certificates Generated Successfully!</h1>
        
        <p>Your certificates have been generated and are ready to download.</p>
        
        <div class="certificate-info">
            <p><strong>File:</strong> <?php echo htmlspecialchars($file_name); ?></p>
            <p><strong>Certificates Generated:</strong> <?php echo $record_count; ?></p>
        </div>
        
        <a href="download_file.php" class="download-button">Download Certificates</a>
        
        <p>Click the button above to download your certificates as a PDF file.</p>
        
        <a href="index.php" class="home-link">Return to Certificate Generator</a>
    </div>
    
    <div class="version">v<?php echo isset($app_version) ? $app_version : '1.4.2'; ?></div>
</body>
</html> 