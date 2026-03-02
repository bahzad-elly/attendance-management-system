<?php
// config/db.php

$host = 'localhost';
$dbname = 'baams_db';
$username = 'root'; // Change this if your local MySQL uses a different username
$password = '';     // Change this if your local MySQL has a password setup

try {
    // Establish the PDO connection with utf8mb4 encoding for security and compatibility
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO error mode to exception for easier debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Turn off emulated prepared statements to enforce strict typing and maximum security
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
    // Set default fetch mode to associative array for cleaner code later
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // If the connection fails, stop the script and show an error
    die("Database connection failed: " . $e->getMessage());
}
?>