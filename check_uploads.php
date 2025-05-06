<?php
echo "<h1>Uploads Directory Check</h1>";

// Check if uploads directory exists
if (!is_dir('uploads')) {
    echo "<p>Uploads directory does not exist. Attempting to create...</p>";
    if (mkdir('uploads', 0777, true)) {
        echo "<p style='color:green'>Successfully created uploads directory!</p>";
    } else {
        echo "<p style='color:red'>Failed to create uploads directory.</p>";
    }
} else {
    echo "<p>Uploads directory exists.</p>";
}

// Check directory permissions
$perms = substr(sprintf('%o', fileperms('uploads')), -4);
echo "<p>Uploads directory permissions: " . $perms . "</p>";

// Try to write a test file
$testFile = 'uploads/test_file.txt';
if (file_put_contents($testFile, 'This is a test file to check write permissions.')) {
    echo "<p style='color:green'>Successfully wrote test file: " . $testFile . "</p>";
    
    // Check if we can read it back
    if (file_exists($testFile)) {
        echo "<p>Test file exists and can be read.</p>";
        echo "<p>Content: " . htmlspecialchars(file_get_contents($testFile)) . "</p>";
        
        // Clean up
        if (unlink($testFile)) {
            echo "<p>Test file successfully deleted.</p>";
        } else {
            echo "<p style='color:red'>Could not delete test file.</p>";
        }
    } else {
        echo "<p style='color:red'>Test file was written but cannot be found.</p>";
    }
} else {
    echo "<p style='color:red'>Failed to write test file. Check directory permissions.</p>";
}

// Check PHP version and loaded extensions
echo "<h2>PHP Environment</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Loaded Extensions: " . implode(', ', get_loaded_extensions()) . "</p>";

// Generate certificates function check
echo "<h2>Generate Certificates Function Check</h2>";
echo "<p>Session Variables:</p>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if the form submission is working
echo "<p>Form POST handling code check:</p>";
echo "<pre>";
$formCode = file_get_contents('index.php');
if (preg_match('/if\s*\(\s*isset\s*\(\s*\$_POST\s*\[\s*[\'"]submit[\'"]\s*\]\s*\)\s*\)\s*{(.+?)}/s', $formCode, $matches)) {
    echo htmlspecialchars($matches[0]);
} else {
    echo "Could not find form submission handling code.";
}
echo "</pre>";
?> 