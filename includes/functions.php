<?php
require_once '../config/database.php';

// Function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Function to get user by email
function getUserByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

// Function to get user by ID
function getUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Function to create user
function createUser($username, $email, $password) {
    global $pdo;
    $hashedPassword = hashPassword($password);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    return $stmt->execute([$username, $email, $hashedPassword]);
}

// Function to get user's upload count
function getUserUploadCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM uploads WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'];
}

// Function to get user's uploads
function getUserUploads($userId, $limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.*, pi.output_path, pi.processed_at 
        FROM uploads u 
        LEFT JOIN processed_images pi ON u.id = pi.upload_id 
        WHERE u.user_id = ? 
        ORDER BY u.uploaded_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

// Function to get all uploads for admin
function getAllUploads($limit = 50) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.*, us.username, us.email, pi.output_path, pi.processed_at 
        FROM uploads u 
        JOIN users us ON u.user_id = us.id 
        LEFT JOIN processed_images pi ON u.id = pi.upload_id 
        ORDER BY u.uploaded_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// Function to get total users count
function getTotalUsersCount() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    return $result['count'];
}

// Function to get total uploads count
function getTotalUploadsCount() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM uploads");
    $result = $stmt->fetch();
    return $result['count'];
}

// Function to get total processed images count
function getTotalProcessedCount() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM processed_images");
    $result = $stmt->fetch();
    return $result['count'];
}

// Function to get total downloads count
function getTotalDownloadsCount() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM downloads");
    $result = $stmt->fetch();
    return $result['count'];
}

// Function to save upload record
function saveUploadRecord($userId, $originalFilename, $savedPath) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO uploads (user_id, original_filename, saved_path) 
        VALUES (?, ?, ?)
    ");
    return $stmt->execute([$userId, $originalFilename, $savedPath]);
}

// Function to save processed image record
function saveProcessedImageRecord($uploadId, $outputPath) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO processed_images (upload_id, output_path) 
        VALUES (?, ?)
    ");
    return $stmt->execute([$uploadId, $outputPath]);
}

// Function to save download record
function saveDownloadRecord($userId, $processedImageId) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO downloads (user_id, processed_image_id) 
        VALUES (?, ?)
    ");
    return $stmt->execute([$userId, $processedImageId]);
}

// Function to generate random filename
function generateRandomFilename($extension) {
    return uniqid() . '_' . time() . '.' . $extension;
}

// Function to check file type
function isValidImageFile($filename) {
    $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $allowedTypes);
}

// Function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Function to format date
function formatDate($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

// Function to get upload by ID
function getUploadById($uploadId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.*, us.username, pi.output_path, pi.processed_at 
        FROM uploads u 
        JOIN users us ON u.user_id = us.id 
        LEFT JOIN processed_images pi ON u.id = pi.upload_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$uploadId]);
    return $stmt->fetch();
}
?>
