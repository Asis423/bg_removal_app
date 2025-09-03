<?php
header('Content-Type: application/json');

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['image'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and WEBP are allowed.']);
    exit;
}

// Validate file size (10MB max)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File size must be less than 10MB.']);
    exit;
}

// FastAPI endpoint URL
$fastapi_url = "http://127.0.0.1:8000/api/upload/";

try {
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fastapi_url);
    curl_setopt($ch, CURLOPT_POST, 1);

    // Prepare file for multipart/form-data
    $cfile = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
    $post_data = ['file' => $cfile]; // you can add 'user_id' => 123 if needed

    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Send request to FastAPI
    $response = curl_exec($ch);
    if ($response === false) {
        throw new Exception('Curl error: ' . curl_error($ch));
    }
    curl_close($ch);

    // Decode FastAPI response
    $data = json_decode($response, true);
    if (!$data || !isset($data['output_path'])) {
        throw new Exception('Invalid response from server.');
    }

    // Convert output_path to URL for frontend display
    $processed_image_url = str_replace('\\', '/', $data['output_path']); // Windows path fix
    $processed_image_url = "http://127.0.0.1:8000/" . ltrim($processed_image_url, '/');

    echo json_encode([
        'success' => true,
        'message' => 'Image uploaded successfully. Processing complete!',
        'upload_id' => $data['upload_id'],
        'processed_id' => $data['processed_id'],
        'processed_image_url' => $processed_image_url
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
