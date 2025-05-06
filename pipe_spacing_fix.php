<?php
// This script checks and fixes pipe spacing in both index.php and generate_certificates.php

echo "<h1>Pipe Spacing Fix Utility</h1>";

function checkAndFixFile($filename, $patterns, $replacements) {
    echo "<h2>Processing $filename</h2>";
    
    // Check if file exists
    if (!file_exists($filename)) {
        echo "<p>Error: File $filename not found!</p>";
        return false;
    }
    
    // Read file content
    $content = file_get_contents($filename);
    if ($content === false) {
        echo "<p>Error: Could not read $filename!</p>";
        return false;
    }
    
    // Check for patterns
    $foundPatterns = [];
    foreach ($patterns as $index => $pattern) {
        if (preg_match($pattern, $content)) {
            $foundPatterns[] = $pattern;
            
            // Replace pattern
            $content = preg_replace($pattern, $replacements[$index], $content);
            echo "<p>Found and replaced pattern in $filename</p>";
        }
    }
    
    if (empty($foundPatterns)) {
        echo "<p>No patterns found to replace in $filename.</p>";
        return false;
    }
    
    // Write updated content back to file
    if (file_put_contents($filename, $content) === false) {
        echo "<p>Error: Could not write to $filename!</p>";
        return false;
    }
    
    echo "<p style='color:green'>Successfully updated $filename with wider pipe spacing!</p>";
    return true;
}

// Patterns and replacements for index.php
$indexPatterns = [
    '/(<span class="pipe">)(\s*)\|(\s*)(<\/span>)/i',
    '/(detailsHTML \+= `<span class="pipe">)(\s*)\|(\s*)(<\/span>`)/i'
];
$indexReplacements = [
    '$1      |      $4',
    '$1      |      $4'
];

// Patterns and replacements for generate_certificates.php
$generatePatterns = [
    '/(\$pipe = \')(\s*)\|(\s*)(\';).*?(Added|Increased)/i'
];
$generateReplacements = [
    '$1      |      $4 // Increased spacing around pipe'
];

// Fix the files
$indexFixed = checkAndFixFile('index.php', $indexPatterns, $indexReplacements);
$generateFixed = checkAndFixFile('generate_certificates.php', $generatePatterns, $generateReplacements);

if ($indexFixed || $generateFixed) {
    echo "<p><strong>Fix applied successfully. Please try generating certificates now.</strong></p>";
} else {
    echo "<p>No changes were needed or errors occurred. Please check the errors above.</p>";
}

// Show current PHP session data for debugging
echo "<h2>Current Session Data</h2>";
echo "<pre>";
session_start();
print_r($_SESSION);
echo "</pre>";
?> 