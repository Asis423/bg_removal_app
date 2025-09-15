<?php
require "db.php";

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
    <link rel="stylesheet" href="../../components/css/style.css" />
    <link rel="stylesheet" href="../../components/css/dashboard.css" />
    <link rel="stylesheet" href="../../components/css/admin.css" />
  


</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="user_dashboard.php" class="brand">
                <div class="brand-icon"><i class="fas fa-magic"></i></div>
                BG Remover Pro
            </a>
               <ul class="nav-menu">
           <a class="nav-link" href="../index.php">Home</a>
            <a class="nav-link about" href="../about.php">About</a>
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
       <?php include('./components/upload.php'); ?>
        
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