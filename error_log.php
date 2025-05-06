<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');
error_reporting(E_ALL);

try {
    // Test file permissions
    $test_file = 'test_permissions.txt';
    file_put_contents($test_file, 'Test write permissions');
    unlink($test_file);
    
    // Test session handling
    session_start();
    $_SESSION['test'] = true;
    session_write_close();
    
    // Test GD library
    if (!extension_loaded('gd')) {
        throw new Exception('GD library is not installed');
    }
    
    echo "Basic PHP functionality is working correctly. The following extensions are available:<br>";
    echo "Loaded extensions: " . implode(', ', get_loaded_extensions());
    
} catch (Exception $e) {
    error_log("Error testing functionality: " . $e->getMessage());
    echo "Error occurred. Check php_errors.log for details.";
} 