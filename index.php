<?php
require_once 'includes/functions.php';
require_once 'config/session.php';

// Get user info if logged in
$user = null;
if (isLoggedIn()) {
    $user = getUserById(getCurrentUserId());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>BG Remover Pro - AI Background Removal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="client/components/css/style.css" />
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="#" class="brand">
                <div class="brand-icon"><i class="fas fa-magic"></i></div>
                BG Remover Pro
            </a>
            <ul class="nav-menu">
                <li><a class="nav-link" href="#home">Home</a></li>
                <li><a class="nav-link" href="#about">About</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li><a class="nav-link" href="upload.php">Upload</a></li>
                    <?php if (isAdmin()): ?>
                        <li><a class="nav-link" href="admin/dashboard.php">Admin</a></li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" id="userDropdown">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </a>
                        <div class="dropdown-menu">
                            <a href="dashboard.php">Dashboard</a>
                            <a href="profile.php">Profile</a>
                            <a href="auth/logout.php">Logout</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a class="nav-link" href="auth/login.php">Login</a></li>
                    <li><a class="nav-link" href="auth/register.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Home Section -->
    <div id="home" class="page-section active">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">Remove Backgrounds with AI Magic</h1>
                <p class="hero-subtitle">
                    Transform your images instantly with our advanced AI-powered background removal tool. Professional results in seconds.
                </p>
                <div class="hero-cta">
                    <?php if (isLoggedIn()): ?>
                        <a href="upload.php" class="btn-primary">
                            <i class="fas fa-upload"></i> Upload & Process Image
                        </a>
                        <a href="dashboard.php" class="btn-secondary">
                            <i class="fas fa-images"></i> View My Uploads
                        </a>
                    <?php else: ?>
                        <a href="#upload" class="btn-primary">
                            <i class="fas fa-upload"></i> Start Removing Backgrounds
                        </a>
                        <a href="auth/register.php" class="btn-secondary">
                            <i class="fas fa-user-plus"></i> Sign Up Free
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Upload Section -->
        <section class="upload-section">
            <div class="upload-container">
                <?php if (isLoggedIn()): ?>
                    <div class="upload-area" onclick="window.location.href='upload.php'">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">Click to upload and process your image</div>
                        <div class="upload-subtext">Supports JPG, PNG, WEBP • Max 10MB</div>
                    </div>
                <?php else: ?>
                    <div class="upload-area" onclick="document.getElementById('fileInput').click()" 
                         ondrop="handleDrop(event)" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)">
                        <div class="upload-icon">
                            <i class="fas fa-cloud-upload-alt"></i>
                        </div>
                        <div class="upload-text">Drop your image here or click to browse</div>
                        <div class="upload-subtext">Supports JPG, PNG, WEBP • Max 10MB</div>
                        <div class="upload-note">
                            <i class="fas fa-info-circle"></i>
                            <a href="auth/register.php">Sign up</a> to save and track your uploads
                        </div>
                    </div>
                    <input type="file" id="fileInput" class="file-input" accept="image/*" onchange="handleFileSelect(event)">
                <?php endif; ?>
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
                            <img id="originalImage" alt="Original Image" />
                            <div class="step-loading">Waiting for upload...</div>
                        </div>
                    </div>

                    <div class="process-step" id="step2">
                        <div class="step-number">2</div>
                        <h3 class="step-title">AI Analysis</h3>
                        <p class="step-description">Analyzing image content and detecting subjects</p>
                        <div class="step-image">
                            <div class="step-loading">Ready for analysis...</div>
                        </div>
                    </div>

                    <div class="process-step" id="step3">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Background Removal</h3>
                        <p class="step-description">Removing background with precision AI algorithms</p>
                        <div class="step-image">
                            <img id="processedImage" alt="Processed Image" />
                            <div class="step-loading">Awaiting processing...</div>
                        </div>
                    </div>
                </div>

                <!-- Resolution Selection (Always visible) -->
                <div id="resolutionContainer" class="resolution-section">
                    <h3 class="resolution-title">Choose Output Resolution</h3>
                    <div class="resolution-options">
                        <div class="resolution-card selected" data-resolution="original" tabindex="0">
                            <div class="resolution-label">Original</div>
                            <div class="resolution-size">Keep original size</div>
                        </div>
                        <div class="resolution-card" data-resolution="hd" tabindex="0">
                            <div class="resolution-label">HD Quality</div>
                            <div class="resolution-size">1920x1080</div>
                        </div>
                        <div class="resolution-card" data-resolution="4k" tabindex="0">
                            <div class="resolution-label">4K Quality</div>
                            <div class="resolution-size">3840x2160</div>
                        </div>
                    </div>

                    <div class="download-actions">
                        <button class="btn-download" type="button">
                            <i class="fas fa-download"></i> Download Image
                        </button>
                        <button class="btn-reset" type="button">
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
                <p>We're revolutionizing image editing with cutting-edge AI technology that makes professional background removal accessible to everyone.</p>
                
                <?php if (!isLoggedIn()): ?>
                    <div class="about-cta">
                        <a href="auth/register.php" class="btn-primary">Get Started Free</a>
                        <a href="auth/login.php" class="btn-secondary">Already have an account?</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="client/components/js/upload.js" defer></script>
    <script>
        // Toggle dropdown menu for logged-in users
        <?php if (isLoggedIn()): ?>
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
        <?php endif; ?>
    </script>
</body>
</html>
