<?php
// upload.php
session_start();
require "./server/db.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$is_admin = $_SESSION["is_admin"];

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["image"])) {
    $file = $_FILES["image"];
    
    // Validate file upload
    if ($file["error"] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Upload error: ' . $file["error"]]);
        exit;
    }
    
    $allowed = ["jpg","jpeg","png","webp"];
    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, WEBP allowed.']);
        exit;
    }
    
    // Check file size (max 10MB)
    if ($file["size"] > 10000000) {
        echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 10MB.']);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $newFileName = uniqid("img_", true) . "." . $ext;
    $savedPath = "uploads/" . $newFileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file["tmp_name"], $uploadDir . $newFileName)) {
        echo json_encode(['success' => false, 'message' => 'Error moving file.']);
        exit;
    }
    
    // Save record into DB
    try {
        $stmt = $pdo->prepare("INSERT INTO uploads (user_id, original_filename, saved_path, uploaded_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $file["name"], $savedPath]);
        $upload_id = $pdo->lastInsertId();
        
        // Send to FastAPI for processing
        $fastapi_url = "http://127.0.0.1:8000/api/upload/";
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fastapi_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        
        // Prepare file for multipart/form-data
        $cfile = new CURLFile($uploadDir . $newFileName, $file['type'], $file['name']);
        $post_data = [
            'file' => $cfile,
            'user_id' => $user_id,
            'upload_id' => $upload_id
        ];
        
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
        
        if (!$data || !isset($data['success']) || !$data['success']) {
            throw new Exception('Invalid response from processing server');
        }
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Upload successful! Image is being processed.',
            'upload_id' => $upload_id
        ]);
        
    } catch (Exception $e) {
        // Log error
        error_log("Upload error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'Error processing image: ' . $e->getMessage()
        ]);
    }
    
    exit;
}
?>

<!-- Upload Component UI -->
<div class="upload-section">
    <h2>Upload New Image</h2>
    <div class="upload-container">
        <div class="upload-area" id="uploadArea">
            <div class="upload-icon">
                <i class="fas fa-cloud-upload-alt"></i>
            </div>
            <div class="upload-text">Drag & Drop or Click to Browse</div>
            <div class="upload-subtext">Supports JPG, PNG, WEBP - Max 10MB</div>
            <input type="file" id="fileInput" name="image" class="file-input" accept="image/*">
        </div>

        <div class="preview-container" id="previewContainer">
            <div class="preview-title">Image Preview</div>
            <img id="imagePreview" class="image-preview" src="" alt="Preview">
            <div class="upload-progress" id="uploadProgress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-text" id="progressText">0%</div>
            </div>
        </div>

        <button type="button" id="uploadButton" class="btn-upload" disabled>
            <i class="fas fa-upload"></i> Upload Image
        </button>

        <div id="message" class="message"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const uploadButton = document.getElementById('uploadButton');
    const messageDiv = document.getElementById('message');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    let selectedFile = null;
    
    // Click on upload area to trigger file input
    uploadArea.addEventListener('click', () => {
        fileInput.click();
    });
    
    // File input change handler
    fileInput.addEventListener('change', handleFileSelect);
    
    // Drag and drop functionality
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', function() {
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFileSelect({target: {files: e.dataTransfer.files}});
        }
    });
    
    // Upload button click handler
    uploadButton.addEventListener('click', handleUpload);
    
    function handleFileSelect(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        selectedFile = file;
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showMessage('Invalid file type. Only JPG, PNG, WEBP allowed.', 'error');
            resetFileInput();
            return;
        }
        
        // Validate file size
        if (file.size > 10 * 1024 * 1024) {
            showMessage('File is too large. Maximum size is 10MB.', 'error');
            resetFileInput();
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            imagePreview.src = e.target.result;
            previewContainer.style.display = 'block';
            uploadButton.disabled = false;
            hideMessage();
        };
        reader.readAsDataURL(file);
    }
    
    function handleUpload() {
        if (!selectedFile) return;
        
        // Disable upload button during upload
        uploadButton.disabled = true;
        uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        
        // Show progress bar
        uploadProgress.style.display = 'block';
        progressFill.style.width = '0%';
        progressText.textContent = '0%';
        
        const formData = new FormData();
        formData.append('image', selectedFile);
        
        // Create XMLHttpRequest for progress tracking
        const xhr = new XMLHttpRequest();
        
        // Track upload progress
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressFill.style.width = percentComplete + '%';
                progressText.textContent = Math.round(percentComplete) + '%';
            }
        });
        
        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        showMessage(response.message, 'success');
                        
                        // Start polling for processing status
                        pollProcessingStatus(response.upload_id);
                    } else {
                        showMessage(response.message, 'error');
                        resetUploadUI();
                    }
                } catch (e) {
                    showMessage('Error parsing server response', 'error');
                    resetUploadUI();
                }
            } else {
                showMessage('Upload failed. Server error: ' + xhr.status, 'error');
                resetUploadUI();
            }
        });
        
        xhr.addEventListener('error', function() {
            showMessage('Upload failed. Network error.', 'error');
            resetUploadUI();
        });
        
        xhr.open('POST', 'upload.php', true);
        xhr.send(formData);
    }
    
    function pollProcessingStatus(uploadId) {
        let attempts = 0;
        const maxAttempts = 30; // 30 attempts with 3-second intervals = 90 seconds total
        
        const checkStatus = function() {
            fetch(`check_status.php?upload_id=${uploadId}`)
                .then(response => response.json())
                .then(data => {
                    attempts++;
                    
                    if (data.success) {
                        if (data.data.status === "completed") {
                            // Processing complete
                            showMessage('Processing complete! Image is ready.', 'success');
                            resetUploadUI();
                            
                            // Refresh the page to show the new upload in the list
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                            
                        } else if (data.data.status === "processing") {
                            // Still processing, check again after delay
                            if (attempts < maxAttempts) {
                                setTimeout(checkStatus, 3000);
                            } else {
                                showMessage('Processing timed out. Please try again.', 'error');
                                resetUploadUI();
                            }
                        } else if (data.data.status === "failed") {
                            showMessage('Processing failed. Please try again.', 'error');
                            resetUploadUI();
                        }
                    } else {
                        showMessage('Error checking status: ' + data.message, 'error');
                        resetUploadUI();
                    }
                })
                .catch(error => {
                    attempts++;
                    
                    if (attempts < maxAttempts) {
                        setTimeout(checkStatus, 3000);
                    } else {
                        showMessage('Error checking processing status', 'error');
                        resetUploadUI();
                    }
                });
        };
        
        // Start polling
        setTimeout(checkStatus, 3000);
    }
    
    function resetUploadUI() {
        uploadButton.disabled = false;
        uploadButton.innerHTML = '<i class="fas fa-upload"></i> Upload Image';
        uploadProgress.style.display = 'none';
        resetFileInput();
    }
    
    function resetFileInput() {
        fileInput.value = '';
        selectedFile = null;
        previewContainer.style.display = 'none';
        imagePreview.src = '';
    }
    
    function showMessage(message, type) {
        messageDiv.textContent = message;
        messageDiv.className = 'message ' + (type === 'success' ? 'success-message' : 'error-message');
        messageDiv.style.display = 'block';
    }
    
    function hideMessage() {
        messageDiv.style.display = 'none';
    }
});
</script>

<style>
.upload-progress {
    margin-top: 1rem;
    display: none;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background-color: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background-color: var(--primary-blue);
    width: 0%;
    transition: width 0.3s ease;
}

.progress-text {
    text-align: center;
    margin-top: 0.5rem;
    font-size: 0.9rem;
    color: #666;
}
</style>