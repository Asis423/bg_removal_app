<?php
require_once '../includes/functions.php';
require_once '../config/session.php';

// Require admin access
requireAdmin();

$totalUsers = getTotalUsersCount();
$totalImages = getTotalImagesCount();
$completedImages = getImagesByStatus('completed');
$processingImages = getImagesByStatus('processing');
$failedImages = getImagesByStatus('failed');
$recentImages = getAllImages(10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BG Remover Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../client/components/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="brand">
                <div class="brand-icon"><i class="fas fa-magic"></i></div>
                BG Remover Pro - Admin
            </a>
            <ul class="nav-menu">
                <li><a class="nav-link" href="../index.php">Home</a></li>
                <li><a class="nav-link active" href="dashboard.php">Admin Dashboard</a></li>
                <li><a class="nav-link" href="users.php">Users</a></li>
                <li><a class="nav-link" href="images.php">Images</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#" id="adminDropdown">
                        <i class="fas fa-user-shield"></i>
                        Admin
                    </a>
                    <div class="dropdown-menu">
                        <a href="../dashboard.php">User Dashboard</a>
                        <a href="../auth/logout.php">Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <p>Monitor system performance and user activity</p>
        </div>

        <div class="admin-stats">
            <div class="stat-card admin-stat">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="stat-card admin-stat">
                <div class="stat-icon">
                    <i class="fas fa-images"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $totalImages; ?></div>
                    <div class="stat-label">Total Images</div>
                </div>
            </div>
            <div class="stat-card admin-stat">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $completedImages; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            <div class="stat-card admin-stat">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $processingImages; ?></div>
                    <div class="stat-label">Processing</div>
                </div>
            </div>
            <div class="stat-card admin-stat">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-number"><?php echo $failedImages; ?></div>
                    <div class="stat-label">Failed</div>
                </div>
            </div>
        </div>

        <div class="admin-content">
            <div class="content-section">
                <div class="section-header">
                    <h2>Recent Image Activity</h2>
                    <a href="images.php" class="view-all">View All Images</a>
                </div>

                <div class="admin-table-container">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Image</th>
                                <th>Status</th>
                                <th>Size</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentImages as $image): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-name"><?php echo htmlspecialchars($image['user_name']); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($image['user_email']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="image-info-cell">
                                            <div class="image-name"><?php echo htmlspecialchars($image['original_filename']); ?></div>
                                            <div class="image-preview-small">
                                                <img src="<?php echo htmlspecialchars($image['original_path']); ?>" alt="Image Preview">
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $image['status']; ?>">
                                            <?php echo ucfirst($image['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatFileSize($image['file_size']); ?></td>
                                    <td><?php echo formatDate($image['created_at']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="../<?php echo htmlspecialchars($image['original_path']); ?>" target="_blank" class="btn-view">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($image['processed_path']): ?>
                                                <a href="../<?php echo htmlspecialchars($image['processed_path']); ?>" target="_blank" class="btn-view">
                                                    <i class="fas fa-magic"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="admin-actions">
                <div class="action-card">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons">
                        <a href="users.php" class="btn-secondary">
                            <i class="fas fa-users"></i>
                            Manage Users
                        </a>
                        <a href="images.php" class="btn-secondary">
                            <i class="fas fa-images"></i>
                            View All Images
                        </a>
                        <a href="system.php" class="btn-secondary">
                            <i class="fas fa-cog"></i>
                            System Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle dropdown menu
        document.getElementById('adminDropdown').addEventListener('click', function(e) {
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
