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

// Handle user management actions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $action = $_POST["action"];
    $target_user_id = $_POST["user_id"];
    
    if ($action === "toggle_admin") {
        $toggle_stmt = $pdo->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ?");
        $toggle_stmt->execute([$target_user_id]);
        header("Refresh:0");
        exit();
    } elseif ($action === "toggle_active") {
        $toggle_stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $toggle_stmt->execute([$target_user_id]);
        header("Refresh:0");
        exit();
    } elseif ($action === "delete_user") {
        // Note: In a real application, you would need to handle related records appropriately
        $delete_stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delete_stmt->execute([$target_user_id]);
        header("Refresh:0");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>BG Remover Pro - Admin Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../components/css/style.css" />
    <link rel="stylesheet" href="../components/css/admin.css" />
    <link rel="stylesheet" href="../components/css/dashboard.css" />
   
</head>
<body>
   <?php include('./components/navbar.php'); ?>

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
                                <tr>
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
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" name="action" value="toggle_admin" class="btn-action btn-admin">
                                                <?= $user['is_admin'] ? 'Revoke Admin' : 'Make Admin' ?>
                                            </button>
                                        </form>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" name="action" value="toggle_active" class="btn-action btn-active">
                                                <?= $user['is_active'] ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                        <?php if ($user['id'] != $user_id): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" name="action" value="delete_user" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this user?')">
                                                    Delete
                                                </button>
                                            </form>
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
        function toggleDropdown() {
            document.getElementById("profileDropdown").style.display =
                document.getElementById("profileDropdown").style.display === "block" ? "none" : "block";
        }
        
        window.onclick = function(event) {
            if (!event.target.closest('.profile-wrapper')) {
                document.getElementById("profileDropdown").style.display = "none";
            }
        }
        
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Deactivate all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activate selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Find and activate the clicked tab
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
    </script>
</body>
</html>