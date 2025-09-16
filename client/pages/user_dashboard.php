<?php
session_start();
require "./server/db.php";
$current_script = basename($_SERVER['PHP_SELF']);

// Redirect if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Redirect admin users to admin_dashboard.php if they try to access user_dashboard.php
if ($_SESSION["is_admin"] && $current_script === 'user_dashboard.php') {
    header("Location: admin_dashboard.php");
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>BG Remover Pro - User Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../components/css/style.css" />
    <link rel="stylesheet" href="../components/css/upload.css" />
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
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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

        .magic-loader {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .magic-loader span {
            width: 10px;
            height: 10px;
            background: var(--primary-orange);
            border-radius: 50%;
            animation: pulse 1.2s ease-in-out infinite;
        }
        
        .magic-loader span:nth-child(2) { animation-delay: 0.2s; }
        .magic-loader span:nth-child(3) { animation-delay: 0.4s; }
        .magic-loader span:nth-child(4) { animation-delay: 0.6s; }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.5); }
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
        <section class="upload-section">
            <h2>Upload New Image</h2>
            <div class="upload-container">
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">Drag & Drop or Click to Browse</div>
                    <div class="upload-subtext">Supports JPG, PNG, WEBP - Max 10MB</div>
                </div>

                <input type="file" id="fileInput" name="image" class="file-input" accept="image/*">

                <div class="preview-container hidden" id="previewContainer">
                    <div class="preview-title hidden">Image Preview</div>
                    <img id="imagePreview" class="image-preview hidden" src="" alt="Preview">
                </div>

                <div id="message" class="message"></div>
            </div>
        </section>
        
        <!-- Processing Section -->
        <section class="processing-section">
            <div class="processing-header">
                <h2 class="processing-title">Processing Your Image</h2>
                <p class="processing-subtitle">Our AI is working its magic to remove the background</p>
            </div>

            <div class="progress-container">
                <div class="progress-bar" id="progressBar"></div>
            </div>

            <div class="process-steps">
                <!-- Step 1 -->
                <div class="process-step" id="step1">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Upload Complete</h3>
                    <p class="step-description">Waiting for upload...</p>
                    <div class="step-image">
                        <img id="imagePreviewStep" class="image-preview preview-target" src="" alt="Preview">
                        <div class="step-loading">Waiting for upload...</div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="process-step" id="step2">
                    <div class="step-number">2</div>
                    <h3 class="step-title">AI Analysis</h3>
                    <p class="step-description">Ready for analysis...</p>
                    <div class="step-image">
                        <div class="magic-loader" id="magicLoader">
                            <span></span><span></span><span></span><span></span>
                        </div>
                        <div class="step-loading">Analyzing...</div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="process-step" id="step3">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Background Removal</h3>
                    <p class="step-description">Awaiting processing...</p>
                    <div class="step-image">
                        <img id="processedImage" src="" alt="Processed Image" style="display:none;" />
                        <div class="step-loading" id="step3Loading">Waiting for processing...</div>
                    </div>
                </div>
            </div>

            <!-- Resolution Selection -->
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
        </section>
        
        <!-- Recent Uploads Section -->
        <section class="uploads-section">
            <h2>Recent Uploads</h2>
            <?php if (count($recent_uploads) > 0): ?>
                <table class="uploads-table">
                    <thead>
                        <tr>
                            <th>Filename</th>
                            <th>Upload Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_uploads as $upload): ?>
                            <tr>
                                <td><?= htmlspecialchars($upload['original_filename']) ?></td>
                                <td><?= date('M j, Y g:i A', strtotime($upload['uploaded_at'])) ?></td>
                                <td>
                                    <?php if ($upload['processed_id']): ?>
                                        <span class="status-complete">Processed</span>
                                    <?php else: ?>
                                        <span class="status-processing">Processing</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($upload['processed_id']): ?>
                                        <a href="<?= $upload['output_path'] ?>" download class="btn-small">Download</a>
                                    <?php else: ?>
                                        <button class="btn-small" disabled>Wait</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-uploads">
                    <i class="fas fa-folder-open" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                    <p>You haven't uploaded any images yet.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

 <script>
// Global variables
let currentUser = { id: <?= json_encode($user_id) ?>, role: <?= json_encode($is_admin ? 'admin' : 'user') ?> };
let processedImageData = null;
let selectedResolution = '1'; // default original
let currentUploadId = null;
let isUploading = false;

function toggleDropdown() {
    document.getElementById("profileDropdown").style.display =
        document.getElementById("profileDropdown").style.display === "block" ? "none" : "block";
}

window.onclick = function(event) {
    if (!event.target.closest('.profile-wrapper')) {
        document.getElementById("profileDropdown").style.display = "none";
    }
}

// Drag and drop functionality
const uploadArea = document.getElementById('uploadArea');
const fileInput = document.getElementById('fileInput');

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
        processFile(e.dataTransfer.files[0]);
    }
});

uploadArea.addEventListener('click', () => {
    fileInput.click();
});

fileInput.addEventListener('change', function() {
    if (this.files.length) {
        processFile(this.files[0]);
    }
});

function showMessage(message, type) {
    const messageDiv = document.getElementById('message');
    messageDiv.textContent = message;
    messageDiv.className = 'message ' + (type === 'error' ? 'error-message' : 'success-message');
    messageDiv.style.display = 'block';
    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 5000);
}

function processFile(file) {
    if (isUploading) return;
    isUploading = true;

    if (!file.type.startsWith('image/')) {
        showMessage('Please select a valid image file.', 'error');
        isUploading = false;
        return;
    }

    if (file.size > 10 * 1024 * 1024) {
        showMessage('File size must be less than 10MB.', 'error');
        isUploading = false;
        return;
    }

    // Show Step 1 preview
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('imagePreview');
        const imgStep = document.getElementById('imagePreviewStep');
        img.src = e.target.result;
        imgStep.src = e.target.result;
        img.style.display = 'block';
        imgStep.style.display = 'block';
        document.getElementById('previewContainer').style.display = 'block';
        document.getElementById('step1').classList.add('completed');
        document.getElementById('step1').querySelector('.step-description').textContent = 'Your image has been uploaded successfully';
        document.getElementById('step1').querySelector('.step-loading').style.display = 'none';
        document.getElementById('progressBar').style.width = '33%';
    };
    reader.readAsDataURL(file);

    // Upload to server
    const formData = new FormData();
    formData.append('image', file);
    formData.append('user_id', currentUser.id);

    fetch('upload.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentUploadId = data.upload_id;
                pollProcessing(currentUploadId);
            } else {
                showMessage(data.message || 'Upload failed.', 'error');
                isUploading = false;
            }
        })
        .catch(err => {
            console.error(err);
            showMessage('Error uploading image.', 'error');
            isUploading = false;
        });
}

function pollProcessing(uploadId) {
    const interval = setInterval(async () => {
        try {
            const response = await fetch(`check_status.php?upload_id=${uploadId}`);
            const result = await response.json();
            console.log('Polling result:', result);

            if (result.success) {
                const step2 = document.getElementById('step2');
                const step3 = document.getElementById('step3');
                const processedImage = document.getElementById('processedImage');
                const step3Loading = document.getElementById('step3Loading');

                if (result.data.status === "processing") {
                    step2.classList.add("active");
                    step2.querySelector(".step-description").textContent = "Analyzing image...";
                    document.getElementById('progressBar').style.width = '66%';
                }

                if (result.data.status === "completed") {
                    clearInterval(interval);
                    console.log('Processing completed!');

                    // Update step 2
                    step2.classList.remove("active");
                    step2.classList.add("completed");
                    step2.querySelector(".step-description").textContent = "Analysis complete!";
                    step2.querySelector(".step-loading").style.display = "none";

                    // Update step 3
                    step3.classList.add("active");
                    step3.querySelector(".step-description").textContent = "Background removed!";
                    step3Loading.style.display = "none";
                    
                    // Set image source
                    processedImage.src = result.data.processed_image_url;
                    processedImage.style.display = "block";
                    processedImage.onload = function() {
                        console.log('Processed image loaded successfully');
                        showDynamicResolutions(processedImage);
                    };
                    processedImage.onerror = function() {
                        console.error('Failed to load processed image');
                        step3Loading.style.display = "block";
                        step3Loading.textContent = "Error loading image";
                    };

                    // Store processed image data
                    processedImageData = result.data.processed_image_url;

                    // Show resolution options
                    document.getElementById("resolutionContainer").classList.remove("hidden");
                    document.getElementById('progressBar').style.width = '100%';

                    // Update recent uploads dynamically
                    updateRecentUploads();
                }
            } else {
                clearInterval(interval);
                showMessage("Error: " + (result.message || 'Unknown error'), "error");
                isUploading = false;
            }
        } catch (error) {
            clearInterval(interval);
            showMessage("Error polling status: " + error.message, "error");
            console.error('Polling error:', error);
            isUploading = false;
        }
    }, 3000);
}

function showDynamicResolutions(img) {
    const container = document.querySelector('.resolution-options');
    if (!container) return;

    const w = img.naturalWidth;
    const h = img.naturalHeight;

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
}

function downloadImage() {
    if (!processedImageData) {
        showMessage('No processed image available.', 'error');
        return;
    }

    const link = document.createElement('a');
    link.download = `bg-removed-${Date.now()}.png`;
    link.href = processedImageData;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Log download to DB
    fetch('log_download.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `processed_image_url=${encodeURIComponent(processedImageData)}&user_id=${currentUser.id}`
    })
    .then(() => {
        // Update recent uploads after download
        updateRecentUploads();
    });
}

function resetProcessor() {
    // Reset all steps
    document.querySelectorAll('.process-step').forEach(step => {
        step.classList.remove('active', 'completed');
    });

    // Reset progress bar
    document.getElementById('progressBar').style.width = '0%';

    // Hide images
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('imagePreviewStep').style.display = 'none';
    document.getElementById('processedImage').style.display = 'none';

    // Reset loading text
    document.querySelector('#step1 .step-description').textContent = 'Waiting for upload...';
    document.querySelector('#step1 .step-loading').style.display = 'block';
    document.querySelector('#step2 .step-description').textContent = 'Ready for analysis...';
    document.querySelector('#step2 .step-loading').style.display = 'block';
    document.querySelector('#step3 .step-description').textContent = 'Awaiting processing...';
    document.getElementById('step3Loading').style.display = 'block';

    // Clear file input
    document.getElementById('fileInput').value = '';

    // Hide resolution container
    document.getElementById('resolutionContainer').classList.add('hidden');

    // Reset processed image data
    processedImageData = null;
    currentUploadId = null;
    isUploading = false;

    // Hide preview
    document.getElementById('previewContainer').style.display = 'none';
}

function updateRecentUploads() {
    fetch('get_recent_uploads.php')
        .then(res => res.json())
        .then(data => {
            const uploadsSection = document.querySelector('.uploads-section');
            const table = uploadsSection.querySelector('.uploads-table');
            const noUploadsDiv = uploadsSection.querySelector('.no-uploads');

            if (data.uploads && data.uploads.length > 0) {
                if (noUploadsDiv) noUploadsDiv.style.display = 'none';
                if (!table) {
                    const newTable = document.createElement('table');
                    newTable.className = 'uploads-table';
                    newTable.innerHTML = `
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>Upload Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    `;
                    uploadsSection.appendChild(newTable);
                }

                const tbody = uploadsSection.querySelector('.uploads-table tbody');
                tbody.innerHTML = '';
                data.uploads.forEach(upload => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${upload.original_filename}</td>
                        <td>${new Date(upload.uploaded_at).toLocaleString('en-US', {
                            month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true
                        })}</td>
                        <td>
                            ${upload.processed_id ? 
                                '<span class="status-complete">Processed</span>' : 
                                '<span class="status-processing">Processing</span>'}
                        </td>
                        <td>
                            ${upload.processed_id ? 
                                `<a href="${upload.output_path}" download class="btn-small">Download</a>` : 
                                '<button class="btn-small" disabled>Wait</button>'}
                        </td>
                    `;
                    tbody.appendChild(row);
                });

                // Update stats
                document.querySelector('.stat-card:nth-child(1) h3').textContent = data.upload_count;
                document.querySelector('.stat-card:nth-child(2) h3').textContent = data.processed_count;
                document.querySelector('.stat-card:nth-child(3) h3').textContent = data.download_count;
            } else {
                if (table) table.remove();
                if (noUploadsDiv) noUploadsDiv.style.display = 'block';
            }
        })
        .catch(err => {
            console.error('Error updating recent uploads:', err);
            showMessage('Error updating recent uploads.', 'error');
        });
}
</script>
</body>
</html>