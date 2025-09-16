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
$upload_count = $upload_count_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

$processed_count_stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM processed_images pi 
    JOIN uploads u ON pi.upload_id = u.id 
    WHERE u.user_id = ?
");
$processed_count_stmt->execute([$user_id]);
$processed_count = $processed_count_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

$download_count_stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM downloads d 
    JOIN processed_images pi ON d.processed_image_id = pi.id 
    JOIN uploads u ON pi.upload_id = u.id 
    WHERE u.user_id = ?
");
$download_count_stmt->execute([$user_id]);
$download_count = $download_count_stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

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

// Handle messages (e.g., from upload)
$uploadMessage = $_GET['message'] ?? '';
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
        /* Your existing styles here */
        :root {
            --primary-blue: rgb(0, 64, 145);
            --primary-orange: rgb(255, 140, 0);
            --accent-blue: rgb(20, 84, 165);
            --light-blue: rgb(240, 248, 255);
            --dark-blue: rgb(0, 44, 105);
        }
        body { font-family: "Segoe UI", sans-serif; background: #f5f7fa; margin: 0; }
        /* ... (rest of styles) */
    </style>
</head>
<body>
    <?php include './components/navbar.php'; ?>

    <div class="dashboard-container">
        <section class="dashboard-header">
            <div>
                <h1>Welcome, <?= htmlspecialchars($username) ?></h1>
                <p>Manage your images and track your processing history</p>
            </div>
            <div class="profile-circle"><?= htmlspecialchars($initials) ?></div>
        </section>

        <div class="stats-grid">
            <div class="stat-card uploads">
                <i class="fas fa-upload"></i>
                <h3 id="uploadCount"><?= $upload_count ?></h3>
                <p>Uploads</p>
            </div>
            <div class="stat-card processed">
                <i class="fas fa-check-circle"></i>
                <h3 id="processedCount"><?= $processed_count ?></h3>
                <p>Processed</p>
            </div>
            <div class="stat-card downloads">
                <i class="fas fa-download"></i>
                <h3 id="downloadCount"><?= $download_count ?></h3>
                <p>Downloads</p>
            </div>
        </div>

        <!-- Upload Section -->
        <section class="upload-section">
            <h2>Upload New Image</h2>
            <form action="upload.php" method="post" enctype="multipart/form-data">
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="upload-text">Drag & Drop or Click to Browse</div>
                    <div class="upload-subtext">Supports JPG, PNG, WEBP - Max 10MB</div>
                </div>
                <input type="file" name="image" id="fileInput" class="file-input" accept="image/*" required>
                <button type="submit" class="btn-primary">Upload and Process</button>
            </form>
            <?php if ($uploadMessage): ?>
                <p class="message"><?= htmlspecialchars($uploadMessage) ?></p>
            <?php endif; ?>
        </section>

        <!-- Processing Section (shown after upload via JS) -->
        <section id="processingSection" style="display: none;">
            <?php include 'process.php'; ?>  <!-- Reusing process.php for consistency -->
        </section>

        <!-- Recent Uploads -->
        <section class="recent-uploads">
            <h2>Recent Uploads</h2>
            <table id="recentUploadsTable">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Upload Date</th>
                        <th>Status</th>
                        <th>Downloads</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_uploads as $upload): ?>
                        <tr>
                            <td><?= htmlspecialchars($upload['original_filename']) ?></td>
                            <td><?= date('M j, Y g:i A', strtotime($upload['uploaded_at'])) ?></td>
                            <td><?= $upload['processed_id'] ? 'Processed' : 'Processing' ?></td>
                            <td><?= $upload['download_count'] ?? 0 ?></td>
                            <td>
                                <?php if ($upload['processed_id']): ?>
                                    <a href="<?= $upload['output_path'] ?>" download>Download</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>

    <script>
        // Reused from index.php for consistency
        let currentUploadId = null;
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');

        uploadArea.addEventListener('click', () => fileInput.click());
        uploadArea.addEventListener('dragover', e => { e.preventDefault(); uploadArea.classList.add('dragover'); });
        uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
        uploadArea.addEventListener('drop', e => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            document.querySelector('form').submit();  // Auto-submit on drop
        });
        fileInput.addEventListener('change', () => document.querySelector('form').submit());

        // Poll after upload (assume upload.php returns upload_id in JSON, redirect back with ?upload_id=)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('upload_id')) {
            currentUploadId = urlParams.get('upload_id');
            document.getElementById('processingSection').style.display = 'block';
            pollProcessing(currentUploadId);
            updateStats();  // Dynamic update
        }

        function pollProcessing(uploadId) {
            const interval = setInterval(async () => {
                try {
                    const response = await fetch(`check_status.php?upload_id=${uploadId}`);
                    const result = await response.json();
                    if (result.success && result.data.status === "completed") {
                        clearInterval(interval);
                        // Update UI (e.g., show processed image)
                        document.getElementById('processedImage').src = result.data.processed_image_url;
                        document.getElementById('processedImage').style.display = 'block';
                        updateStats();  // Refresh stats/recent
                    }
                } catch (error) {
                    clearInterval(interval);
                    console.error('Polling error:', error);
                }
            }, 3000);
        }

        async function updateStats() {
            try {
                const response = await fetch('get_stats.php?user_id=<?= $user_id ?>');  // Assume a new endpoint for AJAX stats
                const data = await response.json();
                document.getElementById('uploadCount').textContent = data.upload_count;
                document.getElementById('processedCount').textContent = data.processed_count;
                document.getElementById('downloadCount').textContent = data.download_count;
                // Refresh recent table via AJAX if needed
            } catch (error) {
                console.error('Stats update error:', error);
            }
        }
        // ... (add showDynamicResolutions and other functions from index.php if needed)
    </script>
</body>
</html>