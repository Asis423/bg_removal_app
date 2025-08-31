<?php
require_once 'includes/functions.php';
require_once 'config/session.php';

// Require login
requireLogin();

$userId = getCurrentUserId();
$user = getUserById($userId);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $fileTmp = $file['tmp_name'];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Validation
        if (!isValidImageFile($fileName)) {
            $error = 'Invalid file type. Only JPG, PNG, and WEBP files are allowed.';
        } elseif ($fileSize > 10 * 1024 * 1024) { // 10MB limit
            $error = 'File size too large. Maximum size is 10MB.';
        } else {
            // Create upload directory if it doesn't exist
            $uploadDir = 'uploads/original/' . $userId . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $newFileName = generateRandomFilename($fileType);
            $uploadPath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                // Save to database
                if (saveUploadRecord($userId, $fileName, $uploadPath)) {
                    $success = 'Image uploaded successfully! You can now process it to remove the background.';
                } else {
                    $error = 'Failed to save upload record. Please try again.';
                    // Remove uploaded file if database save failed
                    unlink($uploadPath);
                }
            } else {
                $error = 'Failed to upload file. Please try again.';
            }
        }
    } else {
        $error = 'Please select a valid image file.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Image - BG Remover Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="client/components/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="brand">
                <div class="brand-icon"><i class="fas fa-magic"></i></div>
                BG Remover Pro
            </a>
            <ul class="nav-menu">
                <li><a class="nav-link" href="index.php">Home</a></li>
                <li><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li><a class="nav-link active" href="upload.php">Upload Image</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#" id="userDropdown">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($user['username']); ?>
                    </a>
                    <div class="dropdown-menu">
                        <a href="profile.php">Profile</a>
                        <a href="auth/logout.php">Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="upload-page-header">
            <h1>Upload Image</h1>
            <p>Upload your image to start removing the background with AI</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
                <div class="success-actions">
                    <a href="index.php" class="btn-primary">
                        <i class="fas fa-magic"></i>
                        Process Image
                    </a>
                    <a href="dashboard.php" class="btn-secondary">
                        <i class="fas fa-images"></i>
                        View Dashboard
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="upload-container">
            <div class="upload-card">
                <div class="upload-header">
                    <h2>Choose Your Image</h2>
                    <p>Select an image file to upload and process</p>
                </div>

                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="file-upload-area" onclick="document.getElementById('imageInput').click()">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">Click to select image or drag and drop</div>
                        <div class="upload-subtext">Supports JPG, PNG, WEBP • Max 10MB</div>
                        <input type="file" id="imageInput" name="image" accept="image/*" style="display: none;" onchange="handleFileSelect(this)">
                    </div>

                    <div id="filePreview" class="file-preview" style="display: none;">
                        <div class="preview-image">
                            <img id="previewImg" src="" alt="Preview">
                        </div>
                        <div class="preview-info">
                            <div class="file-name" id="fileName"></div>
                            <div class="file-size" id="fileSize"></div>
                        </div>
                    </div>

                    <div class="upload-actions">
                        <button type="submit" class="btn-primary btn-full" id="uploadBtn" disabled>
                            <i class="fas fa-upload"></i>
                            Upload Image
                        </button>
                    </div>
                </form>

                <div class="upload-tips">
                    <h3>Upload Tips</h3>
                    <ul>
                        <li>Use high-quality images for better results</li>
                        <li>Ensure the subject is clearly separated from the background</li>
                        <li>Supported formats: JPG, PNG, WEBP</li>
                        <li>Maximum file size: 10MB</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function handleFileSelect(input) {
            const file = input.files[0];
            if (file) {
                // Show preview
                const preview = document.getElementById('filePreview');
                const previewImg = document.getElementById('previewImg');
                const fileName = document.getElementById('fileName');
                const fileSize = document.getElementById('fileSize');
                const uploadBtn = document.getElementById('uploadBtn');

                // File info
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);

                // Image preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                };
                reader.readAsDataURL(file);

                // Show preview and enable upload button
                preview.style.display = 'block';
                uploadBtn.disabled = false;
            }
        }

        function formatFileSize(bytes) {
            if (bytes >= 1073741824) {
                return (bytes / 1073741824).toFixed(2) + ' GB';
            } else if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2) + ' MB';
            } else if (bytes >= 1024) {
                return (bytes / 1024).toFixed(2) + ' KB';
            } else {
                return bytes + ' bytes';
            }
        }

        // Toggle dropdown menu
        document.getElementById('userDropdown').addEventListener('click', function(e) {
            e.preventDefault();
            const dropdown = this.nextElementSibling;
            dropdown.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>
