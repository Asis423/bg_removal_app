
<?php
session_start();
require "./server/db.php";

// Redirect if not logged in or not admin
if (!isset($_SESSION["user_id"]) || !$_SESSION["is_admin"]) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];
$is_admin = $_SESSION["is_admin"];

// Get user initials for profile circle
$nameParts = explode(" ", $username);
$initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ""));

// Get overall statistics
$total_users_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
$total_users_stmt->execute();
$total_users = $total_users_stmt->fetch(PDO::FETCH_ASSOC)['count'];

$total_uploads_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM uploads");
$total_uploads_stmt->execute();
$total_uploads = $total_uploads_stmt->fetch(PDO::FETCH_ASSOC)['count'];

$total_processed_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM processed_images");
$total_processed_stmt->execute();
$total_processed = $total_processed_stmt->fetch(PDO::FETCH_ASSOC)['count'];

$total_downloads_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM downloads");
$total_downloads_stmt->execute();
$total_downloads = $total_downloads_stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get recent uploads from all users
$recent_uploads_stmt = $pdo->prepare("
    SELECT u.*, us.username, pi.id as processed_id, pi.output_path, 
           (SELECT COUNT(*) FROM downloads WHERE processed_image_id = pi.id) as download_count
    FROM uploads u 
    LEFT JOIN processed_images pi ON u.id = pi.upload_id 
    LEFT JOIN users us ON u.user_id = us.id
    ORDER BY u.uploaded_at DESC 
    LIMIT 10
");
$recent_uploads_stmt->execute();
$recent_uploads = $recent_uploads_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all users for the user management section
$users_stmt = $pdo->prepare("
    SELECT id, username, email, created_at, is_admin, is_active 
    FROM users 
    ORDER BY created_at DESC
");

$all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>BG Remover Pro - Admin Dashboard</title>
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

        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .admin-header {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            margin-top: 0;
            color: var(--primary-blue);
        }

        .admin-badge {
            background: var(--primary-orange);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
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

        .admin-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .tab {
            padding: 0.75rem 1.5rem;
            background: #f8f9fa;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .tab.active {
            background: var(--primary-blue);
            color: white;
        }

        .tab:hover {
            background: var(--light-blue);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .admin-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .admin-tools {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .search-box {
            position: relative;
            max-width: 300px;
            width: 100%;
        }

        .search-box i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .search-box input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .filter-select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 0.75rem 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .admin-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .status-active {
            background: #e6f4ea;
            color: #28a745;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status-admin {
            background: var(--primary-orange);
            color: white;
        }

        .status-user {
            background: #e0e7ff;
            color: var(--primary-blue);
        }

        .btn-action {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 4px;
            font-size: 0.85rem;
            cursor: pointer;
            margin-right: 0.5rem;
        }

        .btn-admin {
            background: var(--primary-blue);
            color: white;
        }

        .btn-active {
            background: #28a745;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        
        .no-data {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        .message {
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            display: none;
            text-align: center;
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

        .status-complete {
            color: #28a745;
            font-weight: 500;
        }

        .status-processing {
            color: #ffc107;
            font-weight: 500;
        }

       
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="admin_dashboard.php" class="brand">
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
                    <a href="admin_dashboard.php">Dashboard</a>
                    <a href="settings.php">Settings</a>
                    <a href="./server/logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Admin Header -->
        <section class="admin-header">
            <div>
                <h1>Admin Dashboard</h1>
                <p>Manage users, monitor uploads, and track system performance</p>
            </div>
            <div class="admin-badge">
                <i class="fas fa-shield-alt"></i> Administrator
            </div>
        </section>
        
        <!-- Stats Section -->
        <div class="stats-grid">
            <div class="stat-card users">
                <i class="fas fa-users"></i>
                <h3><?= $total_users ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card uploads">
                <i class="fas fa-images"></i>
                <h3><?= $total_uploads ?></h3>
                <p>Total Uploads</p>
            </div>
            <div class="stat-card processed">
                <i class="fas fa-check-circle"></i>
                <h3><?= $total_processed ?></h3>
                <p>Processed Images</p>
            </div>
            <div class="stat-card downloads">
                <i class="fas fa-download"></i>
                <h3><?= $total_downloads ?></h3>
                <p>Total Downloads</p>
            </div>
        </div>
        
        <!-- Message Container -->
        <div id="message" class="message"></div>

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

        <!-- Admin Tabs -->
        <div class="admin-tabs">
            <div class="tab active" onclick="switchTab('users')">User Management</div>
            <div class="tab" onclick="switchTab('uploads')">Recent Uploads</div>
            <div class="tab" onclick="switchTab('system')">System Analytics</div>
        </div>
        
        <!-- User Management Tab -->
        <div id="users-tab" class="tab-content active">
            <section class="admin-section">
                <div class="admin-tools">
                    <h2>User Management</h2>
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="userSearch" placeholder="Search users..." onkeyup="filterUsers()">
                    </div>
                </div>
                
                <?php if (count($all_users) > 0): ?>
                    <table class="admin-table" id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $user): ?>
                                <tr data-user-id="<?= $user['id'] ?>">
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                    <td>
                                        <span class="status-badge <?= $user['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                            <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $user['is_admin'] ? 'status-admin' : 'status-user' ?>">
                                            <?= $user['is_admin'] ? 'Admin' : 'User' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-action btn-admin" onclick="manageUser(<?= $user['id'] ?>, 'toggle_admin')">
                                            <?= $user['is_admin'] ? 'Revoke Admin' : 'Make Admin' ?>
                                        </button>
                                        <button class="btn-action btn-active" onclick="manageUser(<?= $user['id'] ?>, 'toggle_active')">
                                            <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                        <?php if ($user['id'] != $user_id): ?>
                                            <button class="btn-action btn-delete" onclick="if(confirm('Are you sure you want to delete this user?')) manageUser(<?= $user['id'] ?>, 'delete_user')">
                                                Delete
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-users" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                        <p>No users found.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        
        <!-- Recent Uploads Tab -->
        <div id="uploads-tab" class="tab-content">
            <section class="admin-section">
                <div class="admin-tools">
                    <h2>Recent Uploads</h2>
                    <select class="filter-select" id="uploadFilter" onchange="filterUploads()">
                        <option value="all">All Uploads</option>
                        <option value="processed">Processed Only</option>
                        <option value="processing">Processing Only</option>
                    </select>
                </div>
                
                <?php if (count($recent_uploads) > 0): ?>
                    <table class="admin-table" id="uploadsTable">
                        <thead>
                            <tr>
                                <th>Filename</th>
                                <th>User</th>
                                <th>Upload Date</th>
                                <th>Status</th>
                                <th>Downloads</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_uploads as $upload): ?>
                                <tr>
                                    <td><?= htmlspecialchars($upload['original_filename']) ?></td>
                                    <td><?= htmlspecialchars($upload['username']) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($upload['uploaded_at'])) ?></td>
                                    <td>
                                        <?php if ($upload['processed_id']): ?>
                                            <span class="status-complete">Processed</span>
                                        <?php else: ?>
                                            <span class="status-processing">Processing</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $upload['download_count'] ?? 0 ?></td>
                                    <td>
                                        <?php if ($upload['processed_id']): ?>
                                            <a href="<?= $upload['output_path'] ?>" download class="btn-download">Download</a>
                                        <?php else: ?>
                                            <button class="btn-action" disabled>Wait</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-folder-open" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                        <p>No uploads found.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        
        <!-- System Analytics Tab -->
        <div id="system-tab" class="tab-content">
            <section class="admin-section">
                <h2>System Analytics</h2>
                <p>Advanced analytics and reporting features would be implemented here.</p>
                <div class="no-data">
                    <i class="fas fa-chart-line" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                    <p>Analytics dashboard coming soon.</p>
                </div>
            </section>
        </div>
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

        function showMessage(message, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = message;
            messageDiv.className = 'message ' + (type === 'error' ? 'error-message' : 'success-message');
            messageDiv.style.display = 'block';
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
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

        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabName + '-tab').classList.add('active');
            document.querySelectorAll('.tab').forEach(tab => {
                if (tab.textContent.toLowerCase().includes(tabName)) {
                    tab.classList.add('active');
                }
            });
        }

        function filterUsers() {
            const input = document.getElementById('userSearch');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('usersTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                tr[i].style.display = 'none';
                const td = tr[i].getElementsByTagName('td');
                for (let j = 0; j < td.length; j++) {
                    if (td[j] && td[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                        break;
                    }
                }
            }
        }

        function filterUploads() {
            const filter = document.getElementById('uploadFilter').value;
            const table = document.getElementById('uploadsTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                if (filter === 'all') {
                    tr[i].style.display = '';
                } else {
                    const status = tr[i].getElementsByTagName('td')[3].textContent.toLowerCase();
                    if (filter === 'processed' && status.includes('processed')) {
                        tr[i].style.display = '';
                    } else if (filter === 'processing' && status.includes('processing')) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }

        function manageUser(userId, action) {
            fetch('manage_users.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${userId}&action=${action}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showMessage(`User ${action.replace('_', ' ')} successful.`, 'success');
                    updateUsersTable();
                } else {
                    showMessage(data.message || 'Action failed.', 'error');
                }
            })
            .catch(err => {
                console.error('Error managing user:', err);
                showMessage('Error performing action.', 'error');
            });
        }

        function updateUsersTable() {
            fetch('manage_users.php?action=get_users')
            .then(res => res.json())
            .then(data => {
                const usersSection = document.querySelector('#users-tab .admin-section');
                const table = usersSection.querySelector('.admin-table');
                const noUsersDiv = usersSection.querySelector('.no-data');

                if (data.users && data.users.length > 0) {
                    if (noUsersDiv) noUsersDiv.style.display = 'none';
                    if (!table) {
                        const newTable = document.createElement('table');
                        newTable.className = 'admin-table';
                        newTable.id = 'usersTable';
                        newTable.innerHTML = `
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        `;
                        usersSection.appendChild(newTable);
                    }

                    const tbody = usersSection.querySelector('.admin-table tbody');
                    tbody.innerHTML = '';
                    data.users.forEach(user => {
                        const row = document.createElement('tr');
                        row.setAttribute('data-user-id', user.id);
                        row.innerHTML = `
                            <td>${user.id}</td>
                            <td>${user.username}</td>
                            <td>${user.email}</td>
                            <td>${new Date(user.created_at).toLocaleString('en-US', {
                                month: 'short', day: 'numeric', year: 'numeric'
                            })}</td>
                            <td>
                                <span class="status-badge ${user.is_active ? 'status-active' : 'status-inactive'}">
                                    ${user.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </td>
                            <td>
                                <span class="status-badge ${user.is_admin ? 'status-admin' : 'status-user'}">
                                    ${user.is_admin ? 'Admin' : 'User'}
                                </span>
                            </td>
                            <td>
                                <button class="btn-action btn-admin" onclick="manageUser(${user.id}, 'toggle_admin')">
                                    ${user.is_admin ? 'Revoke Admin' : 'Make Admin'}
                                </button>
                                <button class="btn-action btn-active" onclick="manageUser(${user.id}, 'toggle_active')">
                                    ${user.is_active ? 'Deactivate' : 'Activate'}
                                </button>
                                ${user.id != currentUser.id ? `
                                    <button class="btn-action btn-delete" onclick="if(confirm('Are you sure you want to delete this user?')) manageUser(${user.id}, 'delete_user')">
                                        Delete
                                    </button>
                                ` : ''}
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                    // Update stats
                    document.querySelector('.stat-card.users h3').textContent = data.total_users;
                    document.querySelector('.stat-card.uploads h3').textContent = data.total_uploads;
                    document.querySelector('.stat-card.processed h3').textContent = data.total_processed;
                    document.querySelector('.stat-card.downloads h3').textContent = data.total_downloads;
                } else {
                    if (table) table.remove();
                    if (noUsersDiv) noUsersDiv.style.display = 'block';
                }
            })
            .catch(err => {
                console.error('Error updating users:', err);
                showMessage('Error updating users table.', 'error');
            });
        }

        function updateRecentUploads() {
            fetch('get_recent_uploads.php')
            .then(res => res.json())
            .then(data => {
                const uploadsSection = document.querySelector('#uploads-tab .admin-section');
                const table = uploadsSection.querySelector('.admin-table');
                const noUploadsDiv = uploadsSection.querySelector('.no-data');

                if (data.uploads && data.uploads.length > 0) {
                    if (noUploadsDiv) noUploadsDiv.style.display = 'none';
                    if (!table) {
                        const newTable = document.createElement('table');
                        newTable.className = 'admin-table';
                        newTable.id = 'uploadsTable';
                        newTable.innerHTML = `
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>User</th>
                                    <th>Upload Date</th>
                                    <th>Status</th>
                                    <th>Downloads</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        `;
                        uploadsSection.appendChild(newTable);
                    }

                    const tbody = uploadsSection.querySelector('.admin-table tbody');
                    tbody.innerHTML = '';
                    data.uploads.forEach(upload => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${upload.original_filename}</td>
                            <td>${upload.username}</td>
                            <td>${new Date(upload.uploaded_at).toLocaleString('en-US', {
                                month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true
                            })}</td>
                            <td>
                                ${upload.processed_id ? 
                                    '<span class="status-complete">Processed</span>' : 
                                    '<span class="status-processing">Processing</span>'}
                            </td>
                            <td>${upload.download_count || 0}</td>
                            <td>
                                ${upload.processed_id ? 
                                    `<a href="${upload.output_path}" download class="btn-download">Download</a>` : 
                                    '<button class="btn-action" disabled>Wait</button>'}
                            </td>
                        `;
                        tbody.appendChild(row);
                    });

                    // Update stats
                    document.querySelector('.stat-card.users h3').textContent = data.total_users;
                    document.querySelector('.stat-card.uploads h3').textContent = data.total_uploads;
                    document.querySelector('.stat-card.processed h3').textContent = data.total_processed;
                    document.querySelector('.stat-card.downloads h3').textContent = data.total_downloads;
                } else {
                    if (table) table.remove();
                    if (noUploadsDiv) noUploadsDiv.style.display = 'block';
                }

                // Reapply filter if one is selected
                filterUploads();
            })
            .catch(err => {
                console.error('Error updating recent uploads:', err);
                showMessage('Error updating recent uploads.', 'error');
            });
        }
    </script>
</body>
</html>