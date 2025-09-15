<?php
session_start();
$initials = '';
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $name_parts = explode(' ', $username);
    $initials = strtoupper(substr($name_parts[0], 0, 1));
    if (isset($name_parts[1])) {
        $initials .= strtoupper(substr($name_parts[1], 0, 1));
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>BG Remover Pro - AI Background Removal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../components/css/style.css" />
    <link rel="stylesheet" href="../components/css/upload.css" />
    <link rel="stylesheet" href="../components/css/dashboard.css" />
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
                <li><a class="nav-link" href="about.php">About</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="profile-wrapper">
                        <div class="profile-circle"><?= htmlspecialchars($initials) ?></div>
                        <div class="dropdown">
                            <a href="user_dashboard.php">Dashboard</a>
                            <a href="settings.php">Settings</a>
                            <a href="./server/logout.php">Logout</a>
                        </div>
                    </li>

                <?php else: ?>
                    <li><a class="nav-link" href="login.php">Login</a></li>
                    <li><a class="nav-link" href="register.php">Sign Up</a></li>
                    <nav>
  <a id="auth-link" href="#" onclick="showLogin()">Login / Register</a>
  <a id="admin-link" href="#" style="display:none;">Admin Panel</a>
  <a id="logout-link" href="#" style="display:none;" onclick="logout()">Logout</a>
</nav>

                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Home Section -->
    <div id="home" class="page-section active">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">Remove Backgrounds With Style In Seconds </h1>
                <p class="hero-subtitle">
                    Transform your images instantly with our advanced AI-powered background removal tool. Professional
                    results in seconds.
                </p>
                <div class="hero-cta">
                    <a href="./login.php" class="btn-primary" onclick="scrollToUpload()">
                        <i class="fas fa-upload"></i> Start Removing Backgrounds
                    </a>
                    <a href="about.php" class="btn-secondary">
                        <i class="fas fa-play"></i> Learn More
                    </a>
                </div>
            </div>
        </section>

        <!-- Upload Section -->
        <section class="upload-section">
            <div class="upload-container">
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">Drag & Drop or Click to Browse</div>
                    <div class="upload-subtext">Supports JPG, PNG, WEBP - Max 10MB</div>
                </div>

                <input type="file" id="fileInput" class="file-input" accept="image/*">
                <div id="message" class="message"></div>
            </div>
        </section>


        <!-- Processing Section (Visible by default, no hiding) -->
        <div id="processingContainer" class="container">
            <div class="processing-section">
                <div class="processing-header">
                    <h2 class="processing-title">Processing Your Image</h2>
                    <p class="processing-subtitle">Our AI is working its magic to remove the background</p>
                </div>

                <div class="progress-container">
                    <div class="progress-bar" id="progressBar"></div>
                </div>

                <div class="process-steps">
                    <div class="process-step" id="step1">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Upload Complete</h3>
                        <p class="step-description">Your image has been uploaded successfully</p>
                        <div class="step-image">
                            <img id="imagePreview" class="image-preview preview-target" src="" alt="Preview">
                            <div class="step-loading">Waiting for upload...</div>
                        </div>
                    </div>

                    <div class="process-step" id="step2">
                        <div class="step-number">2</div>
                        <h3 class="step-title">AI Analysis</h3>
                        <p class="step-description">Analyzing image content and detecting subjects</p>
                        <div class="step-image">
                            <!-- Magic visual placeholder -->
                            <div class="magic-loader" id="magicLoader">
                                <span></span><span></span><span></span><span></span>
                            </div>
                            <div class="step-loading">Analyzing...</div>
                        </div>
                    </div>

                    <div class="process-step" id="step3">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Background Removal</h3>
                        <p class="step-description">Removing background with precision AI algorithms</p>
                        <div class="step-image">
                            <img id="processedImage" alt="Processed Image" style="display:none;" />
                            <div class="step-loading" id="step3Loading">Waiting for processing...</div>
                        </div>
                    </div>
                </div>

                <!-- Resolution Selection (Always visible) -->
              <div id="resolutionContainer" class="resolution-container hidden">
    <h3>Select Resolution</h3>
    <div class="resolution-options"></div>
    <div class="download-actions">
        <button class="btn-download" onclick="downloadImage()">
            <i class="fas fa-download"></i> Download Image
        </button>
        <button class="btn-reset" onclick="resetProcessor()">
            <i class="fas fa-refresh"></i> Process Another
        </button>
    </div>
</div>
            </div>
        </div>
    </div>

    <div id="about" class="page-section">
        <div class="container section">
            <div class="text-center">
                <h1>About BG Remover Pro</h1>
                <p>We're revolutionizing image editing with cutting-edge AI technology that makes professional
                    background removal accessible to everyone.</p>
            </div>
        </div>
    </div>

    <div id="login" class="page-section">
        <div class="container">
            <form action="login.php" method="POST">
                <input type="email" name="email" required placeholder="Email" />
                <input type="password" name="password" required placeholder="Password" />
                <button type="submit">Login</button>
            </form>
        </div>
    </div>

    <div id="register" class="page-section">
        <div class="container">
            <form action="signup.php" method="POST">
                <input type="text" name="name" required placeholder="Full Name" />
                <input type="email" name="email" required placeholder="Email" />
                <input type="password" name="password" required placeholder="Password" />
                <button type="submit">Register</button>
            </form>
        </div>
    </div>

    <div id="admin" class="page-section">
        <div class="container section">
            <h1>Admin Dashboard</h1>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>demo@user.com</td>
                        <td>Upload</td>
                        <td>Just now</td>
                        <td>Success</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <script src="../components/js/upload.js" defer></script>
    <script src="../components/js/main.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');
            const uploadButton = document.getElementById('uploadButton');
            const previewContainer = document.getElementById('previewContainer');
            const imagePreview = document.getElementById('imagePreview');
            const messageDiv = document.getElementById('message');

            // Click on upload area to trigger file input
            uploadArea.addEventListener('click', () => {
                fileInput.click();
            });

            // Drag and drop functionality
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');

                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    handleFileSelection();
                }
            });
        });
            // File input change event
            fileInput.addEventListener('change', handleFileSelection);

            // Upload button click event
            uploadButton.addEventListener('click', uploadImage);

            function handleFileSelection() {
                const file = fileInput.files[0];

                if (file) {
                    // Validate file type
                    if (!file.type.match('image.*')) {
                        showMessage('Please select a valid image file (JPG, PNG, WEBP).', 'error');
                        resetForm();
                        return;
                    }

                    // Validate file size (10MB max)
                    if (file.size > 10 * 1024 * 1024) {
                        showMessage('File size must be less than 10MB.', 'error');
                        resetForm();
                        return;
                    }

                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        document.querySelectorAll('.preview-target').forEach(img => {
                            img.src = e.target.result;
                            const loadingText = img.parentElement.querySelector('.step-loading');
                            if (loadingText) loadingText.style.display = 'none';
                        });
                        previewContainer.style.display = 'block';
                        uploadButton.disabled = false;
                    };
                    scrollToPreview();
                    reader.readAsDataURL(file);
                }
            }

            function uploadImage() {
                const file = fileInput.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('image', file);

                // Disable upload button during upload
                uploadButton.disabled = true;
                uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                
                // Send to server
                fetch('upload.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage(data.message, 'success');
                            // Start polling for processed image
                          pollProcessing(data.upload_id);
                        } else {
                            showMessage(data.message, 'error');
                            uploadButton.disabled = false;
                            uploadButton.innerHTML = '<i class="fas fa-upload"></i> Upload Image';
                        }
                    })
                    .catch(error => {
                        showMessage('Upload failed: ' + error, 'error');
                        uploadButton.disabled = false;
                        uploadButton.innerHTML = '<i class="fas fa-upload"></i> Upload Image';
                    });
            }

            function showMessage(message, type) {
                messageDiv.textContent = message;
                messageDiv.className = `message ${type}`;
                messageDiv.style.display = 'block';

                // Auto hide after 5 seconds
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            }

            function resetForm() {
                fileInput.value = '';
                previewContainer.style.display = 'none';
                uploadButton.disabled = true;
                uploadButton.innerHTML = '<i class="fas fa-upload"></i> Upload Image';
            }
            function scrollToUpload() {
                const uploadSection = document.querySelector(".upload-section");
                if (uploadSection) {
                    uploadSection.scrollIntoView({ behavior: "smooth" });
                }
            }
            function scrollToPreview() {
                const previewSection = document.getElementById("processingContainer");
                if (previewSection) {
                    previewSection.scrollIntoView({ behavior: "smooth" });
                }
            }
            function showMessage(message, type) {
    // Create or find message element
    let messageDiv = document.getElementById('message');
    if (!messageDiv) {
        messageDiv = document.createElement('div');
        messageDiv.id = 'message';
        document.body.appendChild(messageDiv);
    }
    
    messageDiv.textContent = message;
    messageDiv.className = `message ${type}`;
    messageDiv.style.display = 'block';

    // Auto hide after 5 seconds
    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 5000);
}

    </script>

</body>

</html>