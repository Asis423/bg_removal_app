<?php
// db.php

$host = "localhost";       // Usually localhost
$dbname = "bg_removal_app"; // Your database name
$user = "root";     // Your DB username
$pass = ""; // Your DB password

// Create connection using PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    // Set PDO error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
