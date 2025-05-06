<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USA Archery Certificate Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .upload-form {
            margin: 20px 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="file"] {
            display: block;
            width: 100%;
            padding: 10px;
            border: 2px dashed #ccc;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            width: 100%;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>USA Archery Certificate Generator</h1>
        
        <?php
        if (isset($_POST['submit'])) {
            // Create uploads directory if it doesn't exist
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }

            // Handle file uploads
            if (!empty($_FILES['excel_file']['name']) && !empty($_FILES['background_image']['name'])) {
                $excel_tmp = $_FILES['excel_file']['tmp_name'];
                $bg_tmp = $_FILES['background_image']['tmp_name'];
                
                $excel_path = 'uploads/' . basename($_FILES['excel_file']['name']);
                $bg_path = 'uploads/' . basename($_FILES['background_image']['name']);
                
                if (move_uploaded_file($excel_tmp, $excel_path) && 
                    move_uploaded_file($bg_tmp, $bg_path)) {
                    
                    $_SESSION['excel_file'] = $excel_path;
                    $_SESSION['background_image'] = $bg_path;
                    
                    header('Location: generate_certificates.php');
                    exit;
                } else {
                    echo '<div class="error">Error uploading files. Please try again.</div>';
                }
            } else {
                echo '<div class="error">Please select both an Excel file and a background image.</div>';
            }
        }
        ?>

        <form class="upload-form" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="excel_file">Upload Excel/CSV File (with columns A-E):</label>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls,.csv" required>
                <small>Format: Column A (First Name), B (Last Name), C-E (Additional Details)</small>
            </div>

            <div class="form-group">
                <label for="background_image">Upload Background Image:</label>
                <input type="file" name="background_image" id="background_image" accept="image/*" required>
                <small>Recommended: High-resolution JPG or PNG file</small>
            </div>

            <button type="submit" name="submit">Generate Certificates</button>
        </form>
    </div>
</body>
</html> 