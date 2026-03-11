<?php
require 'config.php';
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', :pass, 'admin')");
    $stmt->execute(['pass' => password_hash('admin123', PASSWORD_DEFAULT)]);
    echo "Admin user created!\n";
} else {
    echo "Admin already exists.\n";
}
?>
