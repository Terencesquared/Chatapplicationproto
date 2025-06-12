<?php
// Save this as check_php_config.php and visit it in your browser
echo "<h3>PHP Error Configuration:</h3>";
echo "Error log location: " . (ini_get('error_log') ?: 'Not set') . "<br>";
echo "Log errors enabled: " . (ini_get('log_errors') ? 'Yes' : 'No') . "<br>";
echo "Display errors: " . (ini_get('display_errors') ? 'Yes' : 'No') . "<br>";
echo "PHP version: " . phpversion() . "<br>";
echo "Current working directory: " . getcwd() . "<br>";

// Try to write a test error
error_log("Test error message from check_php_config.php");
echo "<br>Test error logged (check your error log location above)";
?>