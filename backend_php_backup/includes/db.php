<?php
// Include security module
require_once __DIR__ . '/security.php';

// Initialize security instance
$security = Security::getInstance();

// Database configuration
$host = 'localhost';
$dbname = 'textile_haven';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Set additional security attributes
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Log security event
    $security->logSecurityEvent('database_connection_failed', ['error' => $e->getMessage()]);
    die("Connection failed: " . $e->getMessage());
}
