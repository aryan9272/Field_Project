<?php
// D:\Xampp\htdocs\FP\db_connect.php

// Define database connection parameters for PDO
$host = 'localhost';
$db   = 'fp'; // Database name must match your MySQL/MariaDB setup
$user = 'root'; // Default XAMPP/WAMP user
$pass = ''; // Default XAMPP/WAMP password (leave empty if none)

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Essential for debugging errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     // Create a new PDO instance (Database Connection)
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // If connection fails, stop script and display error for debugging
     die("Database connection failed: " . $e->getMessage());
}
?>