<?php
session_start();
require "./server/db.php";

// Redirect if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$is_admin = $_SESSION["is_admin"];

// Get user initials for profile circle
$nameParts = explode(" ", $username);
$initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ""));

// Get user statistics
$upload_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM uploads WHERE user_id = ?");
$upload_count_stmt->execute([$user_id]);
$upload_count = $upload_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

$processed_count_stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM processed_images pi 
    JOIN uploads u ON pi.upload_id = u.id 
    WHERE u.user_id = ?
");
$processed_count_stmt->execute([$user_id]);
$processed_count = $processed_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

$download_count_stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM downloads d 
    JOIN processed_images pi ON d.processed_image_id = pi.id 
    JOIN uploads u ON pi.upload_id = u.id 
    WHERE u.user_id = ?
");
$download_count_stmt->execute([$user_id]);
$download_count = $download_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get recent uploads
$recent_uploads_stmt = $pdo->prepare("
    SELECT u.*, pi.id as processed_id, pi.output_path, 
           (SELECT COUNT(*) FROM downloads WHERE processed_image_id = pi.id) as download_count
    FROM uploads u 
    LEFT JOIN processed_images pi ON u.id = pi.upload_id 
    WHERE u.user_id = ? 
    ORDER BY u.uploaded_at DESC 
    LIMIT 5
");
$recent_uploads_stmt->execute([$user_id]);
$recent_uploads = $recent_uploads_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Image Upload
$uploadMessage = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["image"])) {
    $file = $_FILES["image"];

    if ($file["error"] === 0) {
        $allowed = ["jpg", "jpeg", "png", "webp"];
        $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            // Check file size (max 10MB)
            if ($file["size"] > 10000000) {
                $uploadMessage = "❌ File is too large. Maximum size is 10MB.";
            } else {
                $uploadDir = __DIR__ . "/uploads/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $newFileName = uniqid("img_", true) . "." . $ext;
                $savedPath = "uploads/" . $newFileName;

                if (move_uploaded_file($file["tmp_name"], $uploadDir . $newFileName)) {
                    // Save record into DB
                    $stmt = $pdo->prepare("INSERT INTO uploads (user_id, original_filename, saved_path, uploaded_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $file["name"], $savedPath]);
                    $upload_id = $pdo->lastInsertId();

                    // Simulate processing (in a real app, this would be done by a background worker)
                    // For demo purposes, we'll just copy the image as "processed"
                    $processedDir = __DIR__ . "/processed/";
                    if (!is_dir($processedDir)) {
                        mkdir($processedDir, 0777, true);
                    }

                    $processedFileName = "processed_" . $newFileName;
                    $processedPath = "processed/" . $processedFileName;

                    // In a real app, you would use your background removal algorithm here
                    // For demo, we'll just copy the file
                    copy($uploadDir . $newFileName, $processedDir . $processedFileName);

                    // Save processed image record
                    $stmt = $pdo->prepare("INSERT INTO processed_images (upload_id, output_path, processed_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$upload_id, $processedPath]);

                    $uploadMessage = "✅ Upload successful! Image is being processed.";

                    // Refresh page to show updated stats
                    header("Refresh:0");
                    exit();
                } else {
                    $uploadMessage = "❌ Error moving file.";
                }
            }
        } else {
            $uploadMessage = "❌ Invalid file type. Only JPG, PNG, WEBP allowed.";
        }
    } else {
        $uploadMessage = "❌ Upload error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>BG Remover Pro - User Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../components/css/style.css" />
    <style>
        :root {
            --primary-blue: rgb(0, 64, 145);
            --primary-orange: rgb(255, 140, 0);
            --accent-blue: rgb(20, 84, 165);
            --light-blue: rgb(240, 248, 255);
            --dark-blue: rgb(0, 44, 105);
        }

        body {
            font-family: "Segoe UI", sans-serif;
            background: #f5f7fa;
            margin: 0;
        }

        .navbar {
            background: var(--primary-blue);
            padding: 1rem;
            color: white;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: auto;
        }

        .nav-menu {
            flex: content;
            justify-content: end;
            background-color: var(--primary-blue);

        }

        .nav-link {
            color: white;
        }

        .about {
            margin-right: 2rem;
        }

        .brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-circle {
            background: var(--primary-orange);
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
            position: relative;
        }

        .dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 50px;
            background: white;
            color: black;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            min-width: 150px;
            z-index: 999;
        }

        .dropdown a {
            display: block;
            padding: 10px;
            color: black;
            text-decoration: none;
        }

        .dropdown a:hover {
            background: #eee;
        }

        .profile-wrapper {
            position: relative;
        }

        /* Dashboard specific styles */
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .welcome-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .welcome-section h1 {
            margin-top: 0;
            color: var(--primary-blue);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card i {
            font-size: 2rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .stat-card h3 {
            margin: 0;
            font-size: 2.5rem;
            color: var(--primary-orange);
        }

        .stat-card p {
            margin: 0.5rem 0 0;
            color: #666;
        }

        /* Upload section styling */
        .upload-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .upload-section h2 {
            margin-bottom: 1rem;
            color: var(--primary-blue);
        }

        .upload-container {
            max-width: 600px;
            margin: 0 auto;
        }

        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 2rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .upload-area:hover {
            border-color: var(--primary-blue);
            background-color: #f8f9fa;
        }

        .upload-icon {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .upload-text {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .upload-subtext {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .file-input {
            display: none;
        }

        .preview-container {
            margin-top: 2rem;
            display: none;
        }

        .preview-title {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .image-preview {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-upload {
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: background 0.3s;
        }

        .btn-upload:hover {
            background: var(--accent-blue);
        }

        .btn-upload:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .message {
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 6px;
            font-weight: 500;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Recent uploads */
        .uploads-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .uploads-section h2 {
            margin-top: 0;
            color: var(--primary-blue);
        }

        .uploads-table {
            width: 100%;
            border-collapse: collapse;
        }

        .uploads-table th,
        .uploads-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .uploads-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .status-complete {
            color: #28a745;
            font-weight: 500;
        }

        .status-processing {
            color: #ffc107;
            font-weight: 500;
        }

        .btn-small {
            background: var(--primary-blue);
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            cursor: pointer;
        }

        .btn-small:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .no-uploads {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="user_dashboard.php" class="brand">
                <div class="brand-icon"><i class="fas fa-magic"></i></div>
                BG Remover Pro
            </a>
            <ul class="nav-menu">
                <li><a class="nav-link" href="index.php">Home</a></li>
                <li><a class="nav-link about" href="about.php">About</a></li>
            </ul>
            <div class="profile-wrapper">
                <div class="profile-circle" onclick="toggleDropdown()"><?= htmlspecialchars($initials) ?></div>
                <div class="dropdown" id="profileDropdown">
                    <a href="user_dashboard.php">Dashboard</a>
                    <a href="settings.php">Settings</a>
                    <a href="./server/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Welcome Section -->
        <section class="welcome-section">
            <h1>Welcome back, <?= htmlspecialchars($username) ?>!</h1>
            <p>Upload your images to remove backgrounds with our AI-powered tool.</p>
        </section>

        <!-- Stats Section -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-images"></i>
                <h3><?= $upload_count ?></h3>
                <p>Total Uploads</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3><?= $processed_count ?></h3>
                <p>Processed</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-download"></i>
                <h3><?= $download_count ?></h3>
                <p>Downloads</p>
            </div>
        </div>

        <!-- Upload Section -->
        <?php include("./components/upload.php"); ?>
        <?php
        $imageExists = false;
        $imagePath = '';
        include './components/process.php';
        ?>
    </div>

    <script>
        function toggleDropdown() {
            document.getElementById("profileDropdown").style.display =
                document.getElementById("profileDropdown").style.display === "block" ? "none" : "block";
        }

        window.onclick = function (event) {
            if (!event.target.closest('.profile-wrapper')) {
                document.getElementById("profileDropdown").style.display = "none";
            }
        }

        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                // Show preview
                const reader = new FileReader();
                reader.onload = function (e) {
                    const previewContainer = document.getElementById('previewContainer');
                    const imagePreview = document.getElementById('imagePreview');
                    const uploadButton = document.getElementById('uploadButton');

                    imagePreview.src = e.target.result;
                    previewContainer.style.display = 'block';
                    uploadButton.disabled = false;
                };
                reader.readAsDataURL(file);
            }
        }

        // Drag and drop functionality
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');

        uploadArea.addEventListener('dragover', function (e) {
            e.preventDefault();
            uploadArea.style.borderColor = 'var(--primary-blue)';
            uploadArea.style.backgroundColor = '#f8f9fa';
        });

        uploadArea.addEventListener('dragleave', function () {
            uploadArea.style.borderColor = '#ccc';
            uploadArea.style.backgroundColor = 'transparent';
        });

        uploadArea.addEventListener('drop', function (e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#ccc';
            uploadArea.style.backgroundColor = 'transparent';

            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect({ target: { files: e.dataTransfer.files } });
            }
        });
    </script>
     <script >
        // Global variables
        let currentUser = null;
        let processedImageData = null;
        let selectedResolution = '1'; // default original
        let currentUploadId = null;
        let isUploading = false;
        let pollInterval = null;

        // Page navigation
        function showPage(pageId) {
            document.querySelectorAll('.page-section').forEach(page => {
                page.classList.remove('active');
            });
            document.getElementById(pageId).classList.add('active');

            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => {
                if (link.textContent.toLowerCase().includes(pageId.toLowerCase()) ||
                    (pageId === 'home' && link.textContent === 'Home')) {
                    link.classList.add('active');
                }
            });
        }

      

        function showLogin() { showPage('login'); }
        function showRegister() { showPage('register'); }

          // Authentication


        function updateAuthUI() {
            const authLink = document.getElementById('auth-link');
            const adminLink = document.getElementById('admin-link');
            const logoutLink = document.getElementById('logout-link');

            if (!authLink || !adminLink || !logoutLink) {
                console.warn("Auth UI elements not found in DOM");
                return;
            }

            if (currentUser) {
                authLink.style.display = 'none';
                logoutLink.style.display = 'inline-flex';
                if (currentUser.role === 'admin') {
                    adminLink.style.display = 'block';
                }
            } else {
                authLink.style.display = 'block';
                adminLink.style.display = 'none';
                logoutLink.style.display = 'none';
            }
        }
       
        // Drag-drop
        function handleDrop(event) {
            event.preventDefault();
            event.stopPropagation();
            const uploadArea = event.currentTarget;
            uploadArea.classList.remove('dragover');
            const files = event.dataTransfer.files;
            if (files.length > 0) processFile(files[0]);
        }
        function handleDragOver(event) {
            event.preventDefault();
            event.stopPropagation();
            event.currentTarget.classList.add('dragover');
        }
        function handleDragLeave(event) {
            event.preventDefault();
            event.stopPropagation();
            event.currentTarget.classList.remove('dragover');
        }
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) processFile(file);
        }

        // Upload & process
        // ==========================
        // File Upload & Processing
        // ==========================
        function processFile(file) {
            if (isUploading) return; // prevent double upload
            isUploading = true;

            if (!file.type.startsWith('image/')) {
                alert('Please select a valid image file.');
                isUploading = false;
                return;
            }

            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB.');
                isUploading = false;
                return;
            }

            // Show Step 1 preview
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.getElementById('imagePreview');
                img.src = e.target.result;
                img.style.display = 'block';
                document.getElementById('step1').classList.add('completed');
                scrollToPreview();
            };
            reader.readAsDataURL(file);

            // Upload to PHP
            const formData = new FormData();
            formData.append('image', file);

            fetch('upload.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        currentUploadId = data.upload_id;
                        pollProcessing(currentUploadId);
                    } else {
                        alert(data.message || 'Upload failed.');
                        isUploading = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error uploading image.');
                    isUploading = false;
                });
        }

        // ==========================
        // Dynamic Resolution Cards
        // ==========================
        function showDynamicResolutions(img) {
            const createResolutionOptions = function() {
                const w = img.naturalWidth;
                const h = img.naturalHeight;

                const container = document.querySelector('.resolution-options');
                if (!container) return;

                container.innerHTML = `
                    <div class="resolution-card selected" data-scale="1" tabindex="0">
                        <div class="resolution-label">Original</div>
                        <div class="resolution-size">${w} x ${h}</div>
                    </div>
                    <div class="resolution-card" data-scale="0.75" tabindex="0">
                        <div class="resolution-label">75% Quality</div>
                        <div class="resolution-size">${Math.round(w*0.75)} x ${Math.round(h*0.75)}</div>
                    </div>
                    <div class="resolution-card" data-scale="0.5" tabindex="0">
                        <div class="resolution-label">50% Quality</div>
                        <div class="resolution-size">${Math.round(w*0.5)} x ${Math.round(h*0.5)}</div>
                    </div>
                `;

                const cards = container.querySelectorAll('.resolution-card');
                cards.forEach(card => {
                    card.addEventListener('click', function() {
                        cards.forEach(c => c.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedResolution = this.dataset.scale;
                    });
                });

                container.style.display = 'block';
            };

            if (img.complete && img.naturalWidth !== 0) {
                createResolutionOptions();
            } else {
                img.onload = createResolutionOptions;
            }
        }

        // ==========================
        // Download Image
        // ==========================
        function downloadImage() {
            if (!processedImageData) {
                alert('No processed image available.');
                return;
            }

            const link = document.createElement('a');
            link.download = `bg-removed-${Date.now()}.png`;
            link.href = processedImageData;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // Optional: Send download info to DB
            fetch('log_download.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `processed_image_url=${encodeURIComponent(processedImageData)}&user_id=${currentUser ? currentUser.id : 0}`
            });
        }

        function resetProcessor() {
    // Reset all steps
    document.querySelectorAll('.process-step').forEach(step => {
        step.classList.remove('active', 'completed');
    });

    // Reset progress bar
    const progressBar = document.getElementById('progressBar');
    if (progressBar) progressBar.style.width = '0%';

    // Hide images
    const originalImage = document.getElementById('originalImage');
    if (originalImage) originalImage.style.display = 'none';
    const processedImage = document.getElementById('processedImage');
    if (processedImage) processedImage.style.display = 'none';

    // Hide any spinner
    const analysisSpinner = document.getElementById('analysisSpinner');
    if (analysisSpinner) analysisSpinner.style.display = 'none';

    // Reset loading text for steps
    const stepLoadings = document.querySelectorAll('.step-loading');
    const defaultTexts = ['Waiting for upload...', 'Ready for analysis...', 'Awaiting processing...'];
    stepLoadings.forEach((loading, index) => {
        loading.textContent = defaultTexts[index] || 'Processing...';
        loading.style.display = 'block';
    });

    // Clear file input
    const fileInput = document.getElementById('fileInput');
    if (fileInput) fileInput.value = '';

    // Hide resolution container and clear selections
    const resolutionContainer = document.getElementById('resolutionContainer');
    if (resolutionContainer) {
        resolutionContainer.classList.add('hidden');
        const cards = resolutionContainer.querySelectorAll('.resolution-card');
        cards.forEach(card => card.classList.remove('selected'));
    }

    // Reset processed image data
    processedImageData = null;


    const imagePreview = document.getElementById('imagePreview');
if (imagePreview) {
    imagePreview.style.display = 'none';
    imagePreview.src = '';
}

}


        // Admin stats
        function updateAdminStats() {
            if (!currentUser || currentUser.role !== 'admin') return;
            const stats = {
                totalUsers: Math.floor(Math.random() * 100) + 1200,
                imagesProcessed: Math.floor(Math.random() * 1000) + 15000,
                activeToday: Math.floor(Math.random() * 50) + 50,
                successRate: (98 + Math.random() * 2).toFixed(1) + '%'
            };
            Object.keys(stats).forEach(key => {
                const element = document.getElementById(key);
                if (element) element.textContent = stats[key];
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            updateAuthUI();
            setInterval(updateAdminStats, 30000);

            // Handle latest processed image if exists
            const latestProcessedImage = document.getElementById('latestProcessedImage');
            if (latestProcessedImage) {
                latestProcessedImage.onload = function() {
                    console.log('Latest processed image loaded successfully');
                };
                latestProcessedImage.onerror = function() {
                    console.error('Failed to load latest processed image');
                    this.parentElement.innerHTML = '<p style="color: #ff0000;">Error loading latest processed image.</p>';
                };
            }

            // Initialize upload functionality
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');
            
            // Click on upload area to trigger file input
            uploadArea.addEventListener('click', () => {
                fileInput.click();
            });

            // Drag and drop functionality
            uploadArea.addEventListener('dragover', handleDragOver);
            uploadArea.addEventListener('dragleave', handleDragLeave);
            uploadArea.addEventListener('drop', handleDrop);
            fileInput.addEventListener('change', handleFileSelect);
        });

        function pollProcessing(uploadId) {
            const interval = setInterval(async () => {
                try {
                    const response = await fetch(`check_status.php?upload_id=${uploadId}`);
                    const result = await response.json();
                    console.log('Polling result:', result); // Debug log

                    if (result.success) {
                        const step2 = document.getElementById('step2');
                        const step3 = document.getElementById('step3');
                        const processedImage = document.getElementById('processedImage');
                        
                        // Use more robust element selection
                        const step3Loading = document.querySelector('#step3 .step-loading') || 
                                            document.getElementById('step3Loading');

                        if (result.data.status === "processing") {
                            step2.classList.add("active");
                            const step2Desc = step2.querySelector(".step-description");
                            if (step2Desc) step2Desc.textContent = "Analyzing image...";
                        }

                        if (result.data.status === "completed") {
                            clearInterval(interval);
                            console.log('Processing completed!'); // Debug log

                            // Update step 2
                            step2.classList.remove("active");
                            step2.classList.add("completed");
                            const step2Desc = step2.querySelector(".step-description");
                            if (step2Desc) step2Desc.textContent = "Analysis complete!";

                            // Update step 3
                            step3.classList.add("active");
                            const step3Desc = step3.querySelector(".step-description");
                            if (step3Desc) step3Desc.textContent = "Background removed!";
                            
                            // Hide loading and show image
                            if (step3Loading) step3Loading.style.display = "none";
                            
                            // Set image source and make sure it's visible
                            processedImage.src = result.data.processed_image_url;
                            processedImage.style.display = "block";
                            processedImage.onload = function() {
                                console.log('Processed image loaded successfully');
                            };
                            processedImage.onerror = function() {
                                console.error('Failed to load processed image');
                                if (step3Loading) {
                                    step3Loading.style.display = "block";
                                    step3Loading.textContent = "Error loading image";
                                }
                            };

                            // Show resolution options
                            showDynamicResolutions(processedImage);
                            
                            // Store the processed image data for download
                            processedImageData = result.data.processed_image_url;
                            
                            // Show download section
                            const resolutionContainer = document.getElementById("resolutionContainer");
                            if (resolutionContainer) resolutionContainer.classList.remove("hidden");
                        }
                    } else {
                        clearInterval(interval);
                        showMessage("Error: " + (result.message || 'Unknown error'), "error");
                    }
                } catch (error) {
                    clearInterval(interval);
                    showMessage("Error polling status: " + error.message, "error");
                    console.error('Polling error:', error);
                }
            }, 3000); // Check every 3 seconds
        }

        function showDynamicResolutions(img) {
            img.onload = function() {
                const w = img.naturalWidth;
                const h = img.naturalHeight;

                const container = document.querySelector('.resolution-options');
                container.innerHTML = `
                    <div class="resolution-card selected" data-scale="1" tabindex="0">
                        <div class="resolution-label">Original</div>
                        <div class="resolution-size">${w} x ${h}</div>
                    </div>
                    <div class="resolution-card" data-scale="0.75" tabindex="0">
                        <div class="resolution-label">75% Quality</div>
                        <div class="resolution-size">${Math.round(w*0.75)} x ${Math.round(h*0.75)}</div>
                    </div>
                    <div class="resolution-card" data-scale="0.5" tabindex="0">
                        <div class="resolution-label">50% Quality</div>
                        <div class="resolution-size">${Math.round(w*0.5)} x ${Math.round(h*0.5)}</div>
                    </div>
                `;

                // Add click event for selection
                const cards = container.querySelectorAll('.resolution-card');
                cards.forEach(card => {
                    card.addEventListener('click', function() {
                        cards.forEach(c => c.classList.remove('selected'));
                        this.classList.add('selected');
                        selectedResolution = this.dataset.scale;
                    });
                });
            };
        }


</script>
<script src="../components/js/generalFunctions.js"></script>
<script src="../components/js/main.js"></script>
</body>

</html>