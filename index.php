<?php
session_start();
// Define application version
$app_version = "1.4.3";

// Clear previous PDF file data
if (isset($_SESSION['pdf_file'])) {
    // Delete temporary file if it exists
    if (file_exists($_SESSION['pdf_file'])) {
        @unlink($_SESSION['pdf_file']);
    }
    unset($_SESSION['pdf_file']);
    unset($_SESSION['pdf_filename']);
    unset($_SESSION['record_count']);
}

// Initialize position values in session if not set
if (!isset($_SESSION['name_y_pos'])) $_SESSION['name_y_pos'] = 50;
if (!isset($_SESSION['details_y_pos'])) $_SESSION['details_y_pos'] = 60;
if (!isset($_SESSION['name_font_size'])) $_SESSION['name_font_size'] = 30;
if (!isset($_SESSION['details_font_size'])) $_SESSION['details_font_size'] = 16;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USA Archery Certificate Generator</title>
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
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
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
        .version {
            position: fixed;
            bottom: 10px;
            right: 20px;
            font-size: 12px;
            color: #888;
        }
        #preview-container {
            margin-top: 30px;
            text-align: center;
            display: none;
            position: relative;
        }
        #preview-container img {
            max-width: 100%;
            height: auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .preview-text {
            position: absolute;
            left: 0;
            right: 0;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            cursor: move;
            font-family: 'Poppins', Arial, sans-serif;
        }
        .preview-name {
            font-size: 30px;
            color: #aa1f2e;
        }
        .preview-details {
            font-size: 16px;
        }
        .preview-details .pipe {
            color: #aa1f2e;
        }
        .preview-details .text {
            color: #1c355e;
        }
        .position-controls {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .position-controls h4 {
            margin-top: 0;
        }
        .position-slider {
            width: 100%;
            margin: 10px 0;
        }
        .control-group {
            margin-bottom: 15px;
        }
        .control-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .slider-value {
            display: inline-block;
            width: 40px;
            text-align: right;
            margin-left: 10px;
        }
        .csv-preview {
            margin-top: 20px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .record-navigation {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 15px 0;
        }
        .record-slider {
            flex-grow: 1;
            margin: 0 15px;
        }
        .nav-button {
            background-color: #1c355e;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
        }
        .nav-button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .record-counter {
            margin: 0 15px;
            font-weight: bold;
        }
        .record-preview {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .field {
            margin-bottom: 10px;
        }
        .field-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        .direct-navigation {
            display: flex;
            justify-content: center;
            margin: 10px 0;
        }
        .direct-navigation button {
            width: auto;
            margin: 0 5px;
            background-color: #1c355e;
            padding: 8px 15px;
        }
        .font-size-controls {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .font-control {
            width: 48%;
            margin-bottom: 15px;
        }
        
        /* Loading overlay */
        #loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #aa1f2e;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 2s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-message {
            color: white;
            font-size: 18px;
            text-align: center;
            max-width: 80%;
        }
        
        .error-message {
            background-color: #aa1f2e;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            display: none;
            max-width: 80%;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>USA Archery Certificate Generator</h1>
        
        <?php
        if (isset($_POST['submit'])) {
            // Process files directly instead of uploading them
            if (!empty($_FILES['excel_file']['tmp_name']) && !empty($_FILES['background_image']['tmp_name'])) {
                $excel_tmp = $_FILES['excel_file']['tmp_name'];
                $bg_tmp = $_FILES['background_image']['tmp_name'];
                
                // Save font size and position settings to session
                $_SESSION['name_font_size'] = isset($_POST['name_font_size']) ? intval($_POST['name_font_size']) : 30;
                $_SESSION['details_font_size'] = isset($_POST['details_font_size']) ? intval($_POST['details_font_size']) : 16;
                $_SESSION['name_y_pos'] = isset($_POST['name_y_pos']) ? floatval($_POST['name_y_pos']) : 50;
                $_SESSION['details_y_pos'] = isset($_POST['details_y_pos']) ? floatval($_POST['details_y_pos']) : 60;
                
                // Store the paths to the temporary files directly
                $_SESSION['excel_file'] = $excel_tmp;
                $_SESSION['background_image'] = $bg_tmp;
                $_SESSION['excel_filename'] = $_FILES['excel_file']['name'];
                $_SESSION['bg_filename'] = $_FILES['background_image']['name'];
                
                // Redirect to certificate generation script
                header('Location: generate_certificates.php');
                exit;
            } else {
                echo '<div class="error">Please select both an Excel file and a background image.</div>';
            }
        }
        ?>

        <form class="upload-form" method="POST" enctype="multipart/form-data" id="certificate-form">
            <div class="form-group">
                <label for="excel_file">Upload Excel/CSV File (with columns A-E):</label>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls,.csv" required>
                <small>Format: Column A (First Name), B (Last Name), C-E (Additional Details)</small>
                <small>Supported formats: CSV files only</small>
            </div>

            <div class="form-group">
                <label for="background_image">Upload Background Image:</label>
                <input type="file" name="background_image" id="background_image" accept="image/*" required>
                <small>Recommended: High-resolution JPG or PNG file</small>
            </div>

            <div id="preview-container">
                <h3>Certificate Preview</h3>
                <div style="position: relative; display: inline-block;">
                    <img id="preview-image" src="" alt="Background Image Preview">
                    <div class="preview-text preview-name" id="draggable-name">JOHN DOE</div>
                    <div class="preview-text preview-details" id="draggable-details">
                        <span class="text">COLUMN A</span><span class="pipe">      |      </span><span class="text">COLUMN B</span><span class="pipe">      |      </span><span class="text">COLUMN C</span>
                    </div>
                </div>
                
                <div class="direct-navigation">
                    <button type="button" id="prev-direct" class="nav-button" disabled>&lt; Prev</button>
                    <span id="direct-counter" class="record-counter" style="margin: 0 15px;">Record 0 of 0</span>
                    <button type="button" id="next-direct" class="nav-button" disabled>Next &gt;</button>
                </div>
                
                <div class="position-controls">
                    <h4>Adjust Text Settings</h4>
                    <p>Drag the text elements directly on the preview image to position them, or use the controls below.</p>
                    
                    <div class="font-size-controls">
                        <div class="font-control">
                            <label class="control-label" for="name-font-size">Name Font Size:</label>
                            <div style="display: flex; align-items: center;">
                                <input type="range" id="name-font-size" class="position-slider" name="name_font_size" min="20" max="60" value="<?php echo $_SESSION['name_font_size']; ?>" step="1">
                                <span class="slider-value" id="name-font-value"><?php echo $_SESSION['name_font_size']; ?>px</span>
                            </div>
                        </div>
                        
                        <div class="font-control">
                            <label class="control-label" for="details-font-size">Details Font Size:</label>
                            <div style="display: flex; align-items: center;">
                                <input type="range" id="details-font-size" class="position-slider" name="details_font_size" min="10" max="30" value="<?php echo $_SESSION['details_font_size']; ?>" step="1">
                                <span class="slider-value" id="details-font-value"><?php echo $_SESSION['details_font_size']; ?>px</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label class="control-label" for="name-position">Name Position (Vertical):</label>
                        <input type="range" id="name-position" class="position-slider" name="name_y_pos" min="10" max="90" value="<?php echo $_SESSION['name_y_pos']; ?>" step="1">
                        <span class="slider-value" id="name-position-value"><?php echo $_SESSION['name_y_pos']; ?>%</span>
                    </div>
                    
                    <div class="control-group">
                        <label class="control-label" for="details-position">Details Position (Vertical):</label>
                        <input type="range" id="details-position" class="position-slider" name="details_y_pos" min="10" max="90" value="<?php echo $_SESSION['details_y_pos']; ?>" step="1">
                        <span class="slider-value" id="details-position-value"><?php echo $_SESSION['details_y_pos']; ?>%</span>
                    </div>
                </div>
                
                <div id="csv-preview-container" class="csv-preview" style="display: none;">
                    <h4>CSV Data Preview</h4>
                    <div id="record-preview" class="record-preview"></div>
                    
                    <div class="record-navigation">
                        <button id="prev-record" class="nav-button" disabled>&lt; Previous</button>
                        <input type="range" id="record-slider" class="record-slider position-slider" min="0" max="0" value="0" step="1">
                        <button id="next-record" class="nav-button" disabled>Next &gt;</button>
                        <div id="record-counter" class="record-counter">Record 0 of 0</div>
                    </div>
                </div>
            </div>

            <button type="submit" name="submit">Generate Certificates</button>
        </form>
    </div>
    
    <div class="version">v<?php echo $app_version; ?></div>
    
    <!-- Loading overlay -->
    <div id="loading-overlay">
        <div class="spinner"></div>
        <div class="loading-message">
            <p>Processing certificates...</p>
            <p>This may take a moment for large datasets.</p>
            <p>Please don't close this page.</p>
        </div>
        <div class="error-message" id="error-message">
            An error occurred while processing certificates.
        </div>
    </div>
    
    <script>
        // CSV data storage
        let csvData = [];
        let currentRecordIndex = 0;
        
        // Make elements draggable
        function makeElementDraggable(element, parentImage, verticalOnly = true) {
            let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
            let isDragging = false;
            let imageRect;
            
            element.onmousedown = dragMouseDown;

            function dragMouseDown(e) {
                e = e || window.event;
                e.preventDefault();
                // Get image boundaries
                imageRect = parentImage.getBoundingClientRect();
                
                // Get the mouse cursor position at startup
                pos3 = e.clientX;
                pos4 = e.clientY;
                document.onmouseup = closeDragElement;
                document.onmousemove = elementDrag;
                isDragging = true;
            }

            function elementDrag(e) {
                if (!isDragging) return;
                
                e = e || window.event;
                e.preventDefault();
                
                // Calculate the new cursor position
                pos1 = pos3 - e.clientX;
                pos2 = pos4 - e.clientY;
                pos3 = e.clientX;
                pos4 = e.clientY;
                
                // Set the element's new position - vertically only if verticalOnly is true
                const newTop = element.offsetTop - pos2;
                
                // Make sure we stay within image bounds
                if (newTop >= 0 && newTop <= imageRect.height - element.offsetHeight) {
                    element.style.top = newTop + "px";
                    
                    // Update percentage for vertical position
                    const percentage = Math.round((newTop / imageRect.height) * 100);
                    
                    // Update the corresponding slider
                    if (element.id === 'draggable-name') {
                        document.getElementById('name-position').value = percentage;
                        document.getElementById('name-position-value').innerText = percentage + '%';
                    } else if (element.id === 'draggable-details') {
                        document.getElementById('details-position').value = percentage;
                        document.getElementById('details-position-value').innerText = percentage + '%';
                    }
                }
                
                // Only move horizontally if not vertical only
                if (!verticalOnly) {
                    const newLeft = element.offsetLeft - pos1;
                    if (newLeft >= 0 && newLeft <= imageRect.width - element.offsetWidth) {
                        element.style.left = newLeft + "px";
                    }
                }
            }

            function closeDragElement() {
                // Stop moving when mouse button is released
                document.onmouseup = null;
                document.onmousemove = null;
                isDragging = false;
            }
        }
        
        // Parse CSV data
        function parseCSV(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsText(file);
                reader.onload = function(event) {
                    const csv = event.target.result;
                    const lines = csv.split(/\r\n|\n/);
                    const result = [];
                    const headers = lines[0].split(',');
                    
                    // Skip header
                    for (let i = 1; i < lines.length; i++) {
                        if (!lines[i].trim()) continue;
                        
                        const obj = {};
                        const currentline = lines[i].split(',');
                        
                        obj.firstName = currentline[0] ? currentline[0].trim() : '';
                        obj.lastName = currentline[1] ? currentline[1].trim() : '';
                        obj.columnC = currentline[2] ? currentline[2].trim() : '';
                        obj.columnD = currentline[3] ? currentline[3].trim() : '';
                        obj.columnE = currentline[4] ? currentline[4].trim() : '';
                        
                        if (obj.firstName || obj.lastName) {
                            result.push(obj);
                        }
                    }
                    
                    resolve(result);
                };
                reader.onerror = function() {
                    reject(new Error('Error reading CSV file'));
                };
            });
        }
        
        // Display record in the preview
        function displayRecord(index) {
            if (csvData.length === 0) return;
            
            const record = csvData[index];
            const recordPreview = document.getElementById('record-preview');
            
            // Create HTML for record display
            let html = `
                <div class="field"><span class="field-label">First Name:</span> ${record.firstName}</div>
                <div class="field"><span class="field-label">Last Name:</span> ${record.lastName}</div>
            `;
            
            if (record.columnC) html += `<div class="field"><span class="field-label">Column C:</span> ${record.columnC}</div>`;
            if (record.columnD) html += `<div class="field"><span class="field-label">Column D:</span> ${record.columnD}</div>`;
            if (record.columnE) html += `<div class="field"><span class="field-label">Column E:</span> ${record.columnE}</div>`;
            
            recordPreview.innerHTML = html;
            
            // Update counter text
            const counterText = `Record ${index + 1} of ${csvData.length}`;
            document.getElementById('record-counter').textContent = counterText;
            document.getElementById('direct-counter').textContent = counterText;
            
            // Update slider position
            document.getElementById('record-slider').value = index;
            
            // Update button states - both sets of navigation controls
            const isFirst = index === 0;
            const isLast = index === csvData.length - 1;
            
            document.getElementById('prev-record').disabled = isFirst;
            document.getElementById('next-record').disabled = isLast;
            document.getElementById('prev-direct').disabled = isFirst;
            document.getElementById('next-direct').disabled = isLast;
            
            currentRecordIndex = index;
            
            // Auto apply to preview
            applyRecordToPreview();
        }
        
        // Apply current record to the preview
        function applyRecordToPreview() {
            if (csvData.length === 0 || currentRecordIndex >= csvData.length) return;
            
            const record = csvData[currentRecordIndex];
            const nameElement = document.getElementById('draggable-name');
            const detailsElement = document.getElementById('draggable-details');
            
            // Set name
            const fullName = `${record.firstName} ${record.lastName}`.toUpperCase();
            nameElement.textContent = fullName;
            
            // Construct details with colored pipes
            let detailsHTML = '';
            let hasDetails = false;
            
            if (record.columnC) {
                detailsHTML += `<span class="text">${record.columnC.toUpperCase()}</span>`;
                hasDetails = true;
            }
            
            if (record.columnD) {
                if (hasDetails) detailsHTML += `<span class="pipe">      |      </span>`;
                detailsHTML += `<span class="text">${record.columnD.toUpperCase()}</span>`;
                hasDetails = true;
            }
            
            if (record.columnE) {
                if (hasDetails) detailsHTML += `<span class="pipe">      |      </span>`;
                detailsHTML += `<span class="text">${record.columnE.toUpperCase()}</span>`;
            }
            
            // Set details with colored format
            detailsElement.innerHTML = detailsHTML || '<span class="text">NO ADDITIONAL DATA</span>';
        }
        
        // Preview functionality
        document.getElementById('background_image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview-container');
                    const previewImg = document.getElementById('preview-image');
                    const nameElement = document.querySelector('.preview-name');
                    const detailsElement = document.querySelector('.preview-details');
                    
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    
                    // Set font sizes from session
                    nameElement.style.fontSize = '<?php echo $_SESSION["name_font_size"]; ?>px';
                    detailsElement.style.fontSize = '<?php echo $_SESSION["details_font_size"]; ?>px';
                    
                    // Adjust text position after image loads
                    previewImg.onload = function() {
                        // Position elements based on saved percentages
                        const namePos = <?php echo $_SESSION['name_y_pos']; ?>;
                        const detailsPos = <?php echo $_SESSION['details_y_pos']; ?>;
                        
                        nameElement.style.top = (previewImg.height * (namePos / 100)) + 'px';
                        detailsElement.style.top = (previewImg.height * (detailsPos / 100)) + 'px';
                        
                        // Make elements draggable after positioning
                        makeElementDraggable(nameElement, previewImg);
                        makeElementDraggable(detailsElement, previewImg);
                    };
                };
                reader.readAsDataURL(file);
            }
        });
        
        // CSV file handling
        document.getElementById('excel_file').addEventListener('change', async function(e) {
            const file = e.target.files[0];
            if (file) {
                try {
                    csvData = await parseCSV(file);
                    
                    if (csvData.length > 0) {
                        // Show CSV preview
                        document.getElementById('csv-preview-container').style.display = 'block';
                        
                        // Set up slider
                        const slider = document.getElementById('record-slider');
                        slider.max = csvData.length - 1;
                        slider.value = 0;
                        
                        // Display first record
                        displayRecord(0);
                    } else {
                        alert('No valid records found in the CSV file.');
                    }
                } catch (error) {
                    console.error('Error parsing CSV:', error);
                    alert('Error parsing CSV file. Please check the format.');
                }
            }
        });
        
        // CSV navigation controls - main controls
        document.getElementById('prev-record').addEventListener('click', function() {
            if (currentRecordIndex > 0) {
                displayRecord(currentRecordIndex - 1);
            }
        });
        
        document.getElementById('next-record').addEventListener('click', function() {
            if (currentRecordIndex < csvData.length - 1) {
                displayRecord(currentRecordIndex + 1);
            }
        });
        
        document.getElementById('record-slider').addEventListener('input', function() {
            displayRecord(parseInt(this.value));
        });
        
        // Direct navigation buttons below preview
        document.getElementById('prev-direct').addEventListener('click', function() {
            if (currentRecordIndex > 0) {
                displayRecord(currentRecordIndex - 1);
            }
        });
        
        document.getElementById('next-direct').addEventListener('click', function() {
            if (currentRecordIndex < csvData.length - 1) {
                displayRecord(currentRecordIndex + 1);
            }
        });
        
        // Update positions when sliders change
        document.getElementById('name-position').addEventListener('input', function(e) {
            const nameElement = document.querySelector('.preview-name');
            const previewImg = document.getElementById('preview-image');
            const percentage = parseInt(this.value);
            
            nameElement.style.top = (previewImg.height * (percentage / 100)) + 'px';
            document.getElementById('name-position-value').innerText = percentage + '%';
        });
        
        document.getElementById('details-position').addEventListener('input', function(e) {
            const detailsElement = document.querySelector('.preview-details');
            const previewImg = document.getElementById('preview-image');
            const percentage = parseInt(this.value);
            
            detailsElement.style.top = (previewImg.height * (percentage / 100)) + 'px';
            document.getElementById('details-position-value').innerText = percentage + '%';
        });
        
        // Font size controls
        document.getElementById('name-font-size').addEventListener('input', function(e) {
            const nameElement = document.querySelector('.preview-name');
            const size = parseInt(this.value);
            
            nameElement.style.fontSize = size + 'px';
            document.getElementById('name-font-value').innerText = size + 'px';
        });
        
        document.getElementById('details-font-size').addEventListener('input', function(e) {
            const detailsElement = document.querySelector('.preview-details');
            const size = parseInt(this.value);
            
            detailsElement.style.fontSize = size + 'px';
            document.getElementById('details-font-value').innerText = size + 'px';
        });
        
        // Show loading overlay when form is submitted
        document.getElementById('certificate-form').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('excel_file');
            const imageInput = document.getElementById('background_image');
            
            if (fileInput.files.length > 0 && imageInput.files.length > 0) {
                document.getElementById('loading-overlay').style.display = 'flex';
                document.getElementById('error-message').style.display = 'none';
                
                // Estimate processing time based on file size
                const fileSize = fileInput.files[0].size;
                const recordCount = csvData.length || 1;
                
                // Update message if many records
                if (recordCount > 20) {
                    document.querySelector('.loading-message').innerHTML += 
                        `<p>Processing ${recordCount} certificates. This might take a few minutes.</p>`;
                }
                
                // We don't need to track generation for download since we're now using a prompt page
                // that will handle showing the success message
                
                // The form will submit normally
            }
        });
        
        // Check if we're returning from a failed generation
        window.addEventListener('load', function() {
            // Check if there was an error message set
            <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
            document.getElementById('error-message').textContent = '<?php echo addslashes($_SESSION['error']); ?>';
            document.getElementById('error-message').style.display = 'block';
            document.getElementById('loading-overlay').style.display = 'flex';
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                document.getElementById('loading-overlay').style.display = 'none';
            }, 5000);
            <?php 
            // Clear the error message
            unset($_SESSION['error']);
            endif; ?>
        });
    </script>
</body>
</html> 