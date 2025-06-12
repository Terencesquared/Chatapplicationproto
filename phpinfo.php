<?php
// Quick PHP diagnostic - save as phpinfo.php and open in browser

echo "<h2>PHP Configuration Check</h2>";

echo "<h3>Basic Info</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Config File: " . php_ini_loaded_file() . "<br><br>";

echo "<h3>PDO Extensions</h3>";
echo "PDO: " . (extension_loaded('pdo') ? '✅ Loaded' : '❌ Not loaded') . "<br>";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ Loaded' : '❌ Not loaded') . "<br>";
echo "MySQLi: " . (extension_loaded('mysqli') ? '✅ Loaded' : '❌ Not loaded') . "<br><br>";

if (class_exists('PDO')) {
    echo "<h3>Available PDO Drivers</h3>";
    $drivers = PDO::getAvailableDrivers();
    foreach ($drivers as $driver) {
        echo "- $driver<br>";
    }
} else {
    echo "<h3>❌ PDO Class Not Available</h3>";
}

echo "<h3>Connection Test</h3>";
try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "✅ MySQL connection successful<br>";
} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "<br>";
}

echo "<hr><h3>Full PHP Info</h3>";
phpinfo();
?>