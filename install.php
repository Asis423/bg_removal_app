<?php
/**
 * BG Remover Pro - Installation Script
 * Run this file once to set up your database and initial configuration
 */

// Check if already installed
if (file_exists('config/database.php') && file_exists('config/session.php')) {
    echo "Application appears to be already installed.\n";
    echo "If you need to reinstall, please remove the config files first.\n";
    exit;
}

echo "BG Remover Pro - Installation Script\n";
echo "====================================\n\n";

// Create config directory
if (!is_dir('config')) {
    mkdir('config', 0755, true);
    echo "✓ Created config directory\n";
}

// Create includes directory
if (!is_dir('includes')) {
    mkdir('includes', 0755, true);
    echo "✓ Created includes directory\n";
}

// Create uploads directories
if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
    echo "✓ Created uploads directory\n";
}

if (!is_dir('uploads/original')) {
    mkdir('uploads/original', 0755, true);
    echo "✓ Created uploads/original directory\n";
}

if (!is_dir('uploads/processed')) {
    mkdir('uploads/processed', 0755, true);
    echo "✓ Created uploads/processed directory\n";
}

// Create auth directory
if (!is_dir('auth')) {
    mkdir('auth', 0755, true);
    echo "✓ Created auth directory\n";
}

// Create admin directory
if (!is_dir('admin')) {
    mkdir('admin', 0755, true);
    echo "✓ Created admin directory\n";
}

echo "\nDirectory structure created successfully!\n\n";

// Database configuration
echo "Database Configuration:\n";
echo "Please enter your database details:\n\n";

$dbHost = readline("Database Host (default: localhost): ") ?: 'localhost';
$dbName = readline("Database Name (default: bg_removal_app): ") ?: 'bg_removal_app';
$dbUser = readline("Database Username (default: root): ") ?: 'root';
$dbPass = readline("Database Password: ");

echo "\nAttempting to connect to database...\n";

try {
    $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connection successful\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
    echo "✓ Database '$dbName' created/verified\n";
    
    // Select the database
    $pdo->exec("USE `$dbName`");
    
    // Create tables
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        original_path VARCHAR(500) NOT NULL,
        processed_path VARCHAR(500) NULL,
        status ENUM('uploaded', 'processing', 'completed', 'failed') DEFAULT 'uploaded',
        resolution VARCHAR(50) DEFAULT 'original',
        file_size INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ";
    
    $pdo->exec($sql);
    echo "✓ Database tables created successfully\n";
    
    // Insert default admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Admin User', 'admin@bgremover.com', $adminPassword, 'admin']);
    echo "✓ Default admin user created\n";
    
    // Insert sample user
    $userPassword = password_hash('user123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Demo User', 'user@demo.com', $userPassword, 'user']);
    echo "✓ Demo user created\n";
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    echo "Please check your database credentials and try again.\n";
    exit;
}

// Create database config file
$dbConfig = "<?php
// Database configuration
define('DB_HOST', '$dbHost');
define('DB_NAME', '$dbName');
define('DB_USER', '$dbUser');
define('DB_PASS', '$dbPass');

try {
    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME, DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException \$e) {
    die(\"Connection failed: \" . \$e->getMessage());
}
?>";

file_put_contents('config/database.php', $dbConfig);
echo "✓ Database configuration file created\n";

// Create session config file
$sessionConfig = "<?php
session_start();

// Function to check if user is logged in
function isLoggedIn() {
    return isset(\$_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset(\$_SESSION['user_role']) && \$_SESSION['user_role'] === 'admin';
}

// Function to get current user ID
function getCurrentUserId() {
    return \$_SESSION['user_id'] ?? null;
}

// Function to get current user role
function getCurrentUserRole() {
    return \$_SESSION['user_role'] ?? null;
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Function to redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}
?>";

file_put_contents('config/session.php', $sessionConfig);
echo "✓ Session configuration file created\n";

echo "\n🎉 Installation completed successfully!\n\n";
echo "Default Login Credentials:\n";
echo "Admin: admin@bgremover.com / admin123\n";
echo "User:  user@demo.com / user123\n\n";
echo "Next steps:\n";
echo "1. Access your application in a web browser\n";
echo "2. Login with the default credentials\n";
echo "3. Start uploading and processing images\n";
echo "4. Customize the application as needed\n\n";
echo "For support, check the README.md file.\n";
?>
