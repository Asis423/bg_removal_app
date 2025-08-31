<?php
require_once 'config/database.php';

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
function createUser($name, $email, $password) {
    global $pdo;
    $hashedPassword = hashPassword($password);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    return $stmt->execute([$name, $email, $hashedPassword]);
}

// Function to get user's image count
function getUserImageCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM images WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'];
}

// Function to get user's images
function getUserImages($userId, $limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM images WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

// Function to get all images for admin
function getAllImages($limit = 50) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT i.*, u.name as user_name, u.email as user_email 
        FROM images i 
        JOIN users u ON i.user_id = u.id 
        ORDER BY i.created_at DESC 
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

// Function to get total images count
function getTotalImagesCount() {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM images");
    $result = $stmt->fetch();
    return $result['count'];
}

// Function to get images by status
function getImagesByStatus($status) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM images WHERE status = ?");
    $stmt->execute([$status]);
    $result = $stmt->fetch();
    return $result['count'];
}

// Function to save image record
function saveImageRecord($userId, $originalFilename, $originalPath, $fileSize) {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO images (user_id, original_filename, original_path, file_size) 
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$userId, $originalFilename, $originalPath, $fileSize]);
}

// Function to update image status
function updateImageStatus($imageId, $status, $processedPath = null) {
    global $pdo;
    if ($processedPath) {
        $stmt = $pdo->prepare("
            UPDATE images 
            SET status = ?, processed_path = ?, processed_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        return $stmt->execute([$status, $processedPath, $imageId]);
    } else {
        $stmt = $pdo->prepare("UPDATE images SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $imageId]);
    }
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
?>
