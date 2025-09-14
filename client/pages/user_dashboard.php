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
        $allowed = ["jpg","jpeg","png","webp"];
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
        .nav-menu{
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
        
        /* Upload section styling */
        .upload-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
        <section class="upload-section">
            <h2>Upload New Image</h2>
            <form method="post" enctype="multipart/form-data" class="upload-container">
                <div class="upload-area" id="uploadArea" onclick="document.getElementById('fileInput').click()">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">Drag & Drop or Click to Browse</div>
                    <div class="upload-subtext">Supports JPG, PNG, WEBP - Max 10MB</div>
                </div>

                <input type="file" id="fileInput" name="image" class="file-input" accept="image/*" onchange="handleFileSelect(event)">

                <div class="preview-container" id="previewContainer">
                    <div class="preview-title">Image Preview</div>
                    <img id="imagePreview" class="image-preview" src="" alt="Preview">
                </div>

                <button type="submit" id="uploadButton" class="btn-upload" disabled>
                    <i class="fas fa-upload"></i> Upload Image
                </button>

                <?php if (!empty($uploadMessage)): ?>
                    <div id="message" class="message <?= strpos($uploadMessage, '✅') !== false ? 'success-message' : 'error-message' ?>">
                        <?= $uploadMessage ?>
                    </div>
                <?php endif; ?>
            </form>
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
        function toggleDropdown() {
            document.getElementById("profileDropdown").style.display =
                document.getElementById("profileDropdown").style.display === "block" ? "none" : "block";
        }
        
        window.onclick = function(event) {
            if (!event.target.closest('.profile-wrapper')) {
                document.getElementById("profileDropdown").style.display = "none";
            }
        }
        
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
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
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = 'var(--primary-blue)';
            uploadArea.style.backgroundColor = '#f8f9fa';
        });
        
        uploadArea.addEventListener('dragleave', function() {
            uploadArea.style.borderColor = '#ccc';
            uploadArea.style.backgroundColor = 'transparent';
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.style.borderColor = '#ccc';
            uploadArea.style.backgroundColor = 'transparent';
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect({target: {files: e.dataTransfer.files}});
            }
        });
    </script>
</body>
</html>