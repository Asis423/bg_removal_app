<?php
require_once 'includes/functions.php';
require_once 'config/session.php';

// Require login
requireLogin();

$userId = getCurrentUserId();
$user = getUserById($userId);
$userImages = getUserImages($userId, 20);
$totalImages = getUserImageCount($userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - BG Remover Pro</title>
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
                <li><a class="nav-link" href="upload.php">Upload Image</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#" id="userDropdown">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($user['name']); ?>
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
        <div class="dashboard-header">
            <div class="dashboard-welcome">
                <h1>Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
                <p>Manage your background removal projects and track your progress</p>
            </div>
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $totalImages; ?></div>
                        <div class="stat-label">Total Images</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo count(array_filter($userImages, function($img) { return $img['status'] === 'completed'; })); ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo count(array_filter($userImages, function($img) { return $img['status'] === 'processing'; })); ?></div>
                        <div class="stat-label">Processing</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-actions">
            <a href="upload.php" class="btn-primary">
                <i class="fas fa-upload"></i>
                Upload New Image
            </a>
            <a href="index.php" class="btn-secondary">
                <i class="fas fa-magic"></i>
                Remove Background
            </a>
        </div>

        <div class="dashboard-content">
            <div class="content-section">
                <div class="section-header">
                    <h2>Recent Images</h2>
                    <a href="gallery.php" class="view-all">View All</a>
                </div>

                <?php if (empty($userImages)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-images"></i>
                        </div>
                        <h3>No images yet</h3>
                        <p>Start by uploading your first image to remove the background</p>
                        <a href="upload.php" class="btn-primary">Upload Image</a>
                    </div>
                <?php else: ?>
                    <div class="image-grid">
                        <?php foreach ($userImages as $image): ?>
                            <div class="image-card">
                                <div class="image-preview">
                                    <?php if ($image['status'] === 'completed' && $image['processed_path']): ?>
                                        <img src="<?php echo htmlspecialchars($image['processed_path']); ?>" alt="Processed Image">
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($image['original_path']); ?>" alt="Original Image">
                                    <?php endif; ?>
                                    <div class="image-status status-<?php echo $image['status']; ?>">
                                        <?php echo ucfirst($image['status']); ?>
                                    </div>
                                </div>
                                <div class="image-info">
                                    <div class="image-name"><?php echo htmlspecialchars($image['original_filename']); ?></div>
                                    <div class="image-meta">
                                        <span class="image-size"><?php echo formatFileSize($image['file_size']); ?></span>
                                        <span class="image-date"><?php echo formatDate($image['created_at']); ?></span>
                                    </div>
                                    <?php if ($image['status'] === 'completed' && $image['processed_path']): ?>
                                        <div class="image-actions">
                                            <a href="<?php echo htmlspecialchars($image['processed_path']); ?>" download class="btn-download-small">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="process.php?id=<?php echo $image['id']; ?>" class="btn-process-small">
                                                <i class="fas fa-magic"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
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
