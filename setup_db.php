<?php
// setup_db.php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';

try {
    // Connect without database name first
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read the SQL file
    $sql = file_get_contents('database.sql');
    
    // Execute the SQL to create DB and Tables
    $pdo->exec($sql);
    
    echo "Database and tables created successfully!";
} catch (PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>
