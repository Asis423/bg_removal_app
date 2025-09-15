<?php
header('Content-Type: application/json');

// -------------------------
// 1. Check uploaded file
// -------------------------
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['image'];

// -------------------------
// 2. Validate file type
// -------------------------
$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and WEBP are allowed.']);
    exit;
}

// -------------------------
// 3. Validate file size
// -------------------------
if ($file['size'] > 10 * 1024 * 1024) { // 10MB
    echo json_encode(['success' => false, 'message' => 'File size must be less than 10MB.']);
    exit;
}

// -------------------------
// 4. FastAPI endpoint
// -------------------------
$fastapi_url = "http://127.0.0.1:8000/api/upload/";

try {
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fastapi_url);
    curl_setopt($ch, CURLOPT_POST, 1);

    // Prepare file for multipart/form-data
    $cfile = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
    $post_data = ['file' => $cfile]; // add 'user_id' if needed
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Send request
    $response = curl_exec($ch);
    if ($response === false) {
        throw new Exception('Curl error: ' . curl_error($ch));
    }
    curl_close($ch);

    // Decode response from FastAPI
    $data = json_decode($response, true);
    if (!$data || !isset($data['upload_id'])) {
        throw new Exception('Invalid response from server: ' . $response);
    }

    // -------------------------
    // 5. Return upload_id only
    // -------------------------
    echo json_encode([
        'success' => true,
        'message' => 'Image uploaded successfully. Processing started...',
        'upload_id' => $data['upload_id']
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
