<?php
header('Content-Type: application/json');

$host = "localhost";
$dbname = "bg_removal_app";
$user = "root";
$pass = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$upload_id = isset($_GET['upload_id']) ? intval($_GET['upload_id']) : 0;
error_log("Checking status for upload_id: " . $upload_id); // Debug log

if ($upload_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid upload ID']);
    exit;
}

// Fetch processed image record from database
$stmt = $pdo->prepare("SELECT * FROM processed_images WHERE upload_id = ?");
$stmt->execute([$upload_id]);
$processed = $stmt->fetch(PDO::FETCH_ASSOC);

error_log("Processed image query result: " . print_r($processed, true)); // Debug log

if ($processed) {
    // Build a browser-accessible URL dynamically
    $processed_url = "http://localhost/frontend/client/pages/processed/" . basename($processed['output_path']);
    error_log("Processed URL: " . $processed_url); // Debug log

    echo json_encode([
        'success' => true,
        'data' => [
            'status' => 'completed',
            'processed_image_url' => $processed_url,
            'processed_id' => $processed['id']
        ]
    ]);
} else {
    // Check if upload exists but not processed yet
    $stmt = $pdo->prepare("SELECT * FROM uploads WHERE id = ?");
    $stmt->execute([$upload_id]);
    $upload = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Upload query result: " . print_r($upload, true)); // Debug log
    
    if ($upload) {
        // Still processing
        echo json_encode([
            'success' => true,
            'data' => [
                'status' => 'processing',
                'message' => 'AI is still working its magic...'
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Upload not found'
        ]);
    }
}
?>