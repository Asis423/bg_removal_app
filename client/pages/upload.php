<?php
session_start();
header('Content-Type: application/json');

// -------------------------
// 1. Check session (login)
// -------------------------
$logged_in = isset($_SESSION['user_id']);
$user_id = $logged_in ? $_SESSION['user_id'] : null;

// -------------------------
// 2. Validate file
// -------------------------
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['image'];
$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
    exit;
}

// -------------------------
// 3. Save file locally
// -------------------------
$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
$filename = uniqid('img_', true) . "." . pathinfo($file['name'], PATHINFO_EXTENSION);
$target_path = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $target_path)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
    exit;
}

// -------------------------
// 4. If logged in → insert into DB
// -------------------------
$upload_id = null;
if ($logged_in) {
    $conn = new mysqli("127.0.0.1", "root", "", "bg_removal_app");
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO uploads (user_id, file_name, file_path, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $file['name'], "uploads/" . $filename);
    if ($stmt->execute()) {
        $upload_id = $stmt->insert_id; // ✅ saved in DB
    }
    $stmt->close();
    $conn->close();
}

// -------------------------
// 5. Forward to FastAPI
// -------------------------
$fastapi_url = "http://127.0.0.1:8000/api/upload/";
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fastapi_url);
    curl_setopt($ch, CURLOPT_POST, 1);

    $cfile = new CURLFile($target_path, $file['type'], $file['name']);
    $post_data = ['file' => $cfile];

    if ($logged_in) {
        $post_data['user_id'] = $user_id;  // also send to FastAPI
        $post_data['upload_id'] = $upload_id; // let FastAPI know which DB row
    }

    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if ($response === false) {
        throw new Exception('Curl error: ' . curl_error($ch));
    }
    curl_close($ch);

    $data = json_decode($response, true);

    echo json_encode([
        'success'   => true,
        'message'   => 'Image uploaded successfully. Processing started...',
        'upload_id' => $upload_id ?? null,
        'fastapi'   => $data
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
