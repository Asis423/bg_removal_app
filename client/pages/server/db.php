<?php
// db.php

$host = "127.0.0.1";
$dbname = "bg_removal_app"; // Your database name
$user = "root";     // Your DB username
$pass = ""; // Your DB password

// Create connection using PDO
try {
$pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $user, $pass);
    // Set PDO error mode to exception for better error handling
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
