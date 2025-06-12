<?php
// Database Connection Test Script
// This will help us identify the exact database connection issue

// Database Configuration (same as your config.php)
define('DB_HOST', 'localhost');
define('DB_NAME', 'chat_app');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

echo "Testing database connection...\n";
echo "Host: " . DB_HOST . "\n";
echo "Database: " . DB_NAME . "\n";
echo "User: " . DB_USER . "\n";
echo "Charset: " . DB_CHARSET . "\n\n";

try {
    // Test 1: Basic PDO connection
    echo "Step 1: Testing basic PDO connection...\n";
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    echo "✓ Basic connection successful\n\n";
    
    // Test 2: Check if database exists
    echo "Step 2: Checking if database exists...\n";
    $stmt = $pdo->query("SHOW DATABASES LIKE '" . DB_NAME . "'");
    $dbExists = $stmt->rowCount() > 0;
    
    if ($dbExists) {
        echo "✓ Database '" . DB_NAME . "' exists\n\n";
    } else {
        echo "✗ Database '" . DB_NAME . "' does not exist\n";
        echo "Creating database...\n";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database created successfully\n\n";
    }
    
    // Test 3: Connect to specific database
    echo "Step 3: Testing connection to specific database...\n";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    echo "✓ Connection to database successful\n\n";
    
    // Test 4: Check if tables exist
    echo "Step 4: Checking required tables...\n";
    $requiredTables = ['users', 'chat_rooms', 'room_participants', 'messages'];
    $existingTables = [];
    
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $existingTables[] = $row[0];
    }
    
    foreach ($requiredTables as $table) {
        if (in_array($table, $existingTables)) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' missing\n";
        }
    }
    
    if (count($existingTables) === 0) {
        echo "\nNo tables found. You need to create the database schema.\n";
        echo "Would you like me to create the tables? (This will create the schema)\n";
    }
    
    echo "\n✓ All database tests completed successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    
    // Common solutions
    echo "\nPossible solutions:\n";
    echo "1. Make sure XAMPP/WAMP is running\n";
    echo "2. Check if MySQL service is started\n";
    echo "3. Verify database credentials\n";
    echo "4. Create the database manually in phpMyAdmin\n";
    echo "5. Check if port 3306 is available\n";
}
?>