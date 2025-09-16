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

// Get user initials
$nameParts = explode(" ", $username);
$initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ""));

// Overall statistics
$total_users = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'] ?? 0;
$total_uploads = $pdo->query("SELECT COUNT(*) as count FROM uploads")->fetch()['count'] ?? 0;
$total_processed = $pdo->query("SELECT COUNT(*) as count FROM processed_images")->fetch()['count'] ?? 0;
$total_downloads = $pdo->query("SELECT COUNT(*) as count FROM downloads")->fetch()['count'] ?? 0;

// Recent uploads
$recent_uploads = $pdo->query("
    SELECT u.*, us.username, pi.id as processed_id, pi.output_path, 
           (SELECT COUNT(*) FROM downloads WHERE processed_image_id = pi.id) as download_count
    FROM uploads u 
    LEFT JOIN processed_images pi ON u.id = pi.upload_id 
    LEFT JOIN users us ON u.user_id = us.id
    ORDER BY u.uploaded_at DESC 
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// All users
$all_users = $pdo->query("
    SELECT id, username, email, created_at, is_admin, is_active 
    FROM users 
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Top users for analytics
$top_users = $pdo->query("
    SELECT us.username, COUNT(u.id) as upload_count
    FROM users us LEFT JOIN uploads u ON us.id = u.user_id
    GROUP BY us.id ORDER BY upload_count DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Handle actions (with AJAX support)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $action = $_POST["action"];
    $target_user_id = $_POST["user_id"];
    $success = false;

    if ($action === "toggle_admin") {
        $pdo->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ?")->execute([$target_user_id]);
        $success = true;
    } elseif ($action === "toggle_active") {
        $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?")->execute([$target_user_id]);
        $success = true;
    } elseif ($action === "delete_user") {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$target_user_id]);
        $success = true;
    }

    echo json_encode(['success' => $success]);
    exit();
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
</head>
<body>
    <?php include './components/navbar.php'; ?>

    <div class="dashboard-container">
        <section class="admin-header">
            <div>
                <h1>Admin Dashboard</h1>
                <p>Manage users, monitor uploads, and track system performance</p>
            </div>
            <div class="admin-badge"><i class="fas fa-shield-alt"></i> Administrator</div>
        </section>

        <div class="stats-grid">
            <div class="stat-card users"><i class="fas fa-users"></i><h3><?= $total_users ?></h3><p>Total Users</p></div>
            <div class="stat-card uploads"><i class="fas fa-images"></i><h3><?= $total_uploads ?></h3><p>Total Uploads</p></div>
            <div class="stat-card processed"><i class="fas fa-check-circle"></i><h3><?= $total_processed ?></h3><p>Processed Images</p></div>
            <div class="stat-card downloads"><i class="fas fa-download"></i><h3><?= $total_downloads ?></h3><p>Total Downloads</p></div>
        </div>

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
                    <div class="search-box"><i class="fas fa-search"></i><input type="text" id="userSearch" placeholder="Search users..." onkeyup="filterUsers()"></div>
                </div>
                <?php if (count($all_users) > 0): ?>
                    <table class="admin-table" id="usersTable">
                        <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Joined</th><th>Status</th><th>Role</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach ($all_users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
                                    <td><span class="status-badge <?= $user['is_active'] ? 'active' : 'inactive' ?>"><?= $user['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                                    <td><?= $user['is_admin'] ? 'Admin' : 'User' ?></td>
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="button" onclick="performAction('toggle_admin', <?= $user['id'] ?>)">Toggle Admin</button>
                                            <button type="button" onclick="performAction('toggle_active', <?= $user['id'] ?>)">Toggle Active</button>
                                            <button type="button" onclick="performAction('delete_user', <?= $user['id'] ?>)">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data"><i class="fas fa-folder-open" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i><p>No users found.</p></div>
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
                        <thead><tr><th>Filename</th><th>User</th><th>Upload Date</th><th>Status</th><th>Downloads</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent_uploads as $upload): ?>
                                <tr>
                                    <td><?= htmlspecialchars($upload['original_filename']) ?></td>
                                    <td><?= htmlspecialchars($upload['username']) ?></td>
                                    <td><?= date('M j, Y g:i A', strtotime($upload['uploaded_at'])) ?></td>
                                    <td><span class="status-<?= $upload['processed_id'] ? 'complete' : 'processing' ?>"><?= $upload['processed_id'] ? 'Processed' : 'Processing' ?></span></td>
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
                    <div class="no-data"><i class="fas fa-folder-open" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i><p>No uploads found.</p></div>
                <?php endif; ?>
            </section>
        </div>

        <!-- System Analytics Tab -->
        <div id="system-tab" class="tab-content">
            <section class="admin-section">
                <h2>System Analytics</h2>
                <h3>Top Users by Uploads</h3>
                <table class="admin-table">
                    <thead><tr><th>Username</th><th>Upload Count</th></tr></thead>
                    <tbody>
                        <?php foreach ($top_users as $top_user): ?>
                            <tr><td><?= htmlspecialchars($top_user['username']) ?></td><td><?= $top_user['upload_count'] ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.getElementById(tabName + '-tab').classList.add('active');
            document.querySelector(`.tab[onclick="switchTab('${tabName}')"]`).classList.add('active');
        }

        function filterUsers() {
            const filter = document.getElementById('userSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            rows.forEach(row => {
                row.style.display = Array.from(row.cells).some(cell => cell.textContent.toLowerCase().includes(filter)) ? '' : 'none';
            });
        }

        function filterUploads() {
            const filter = document.getElementById('uploadFilter').value;
            const rows = document.querySelectorAll('#uploadsTable tbody tr');
            rows.forEach(row => {
                const status = row.cells[3].textContent.toLowerCase();
                row.style.display = (filter === 'all' || (filter === 'processed' && status === 'processed') || (filter === 'processing' && status === 'processing')) ? '' : 'none';
            });
        }

        async function performAction(action, userId) {
            if (!confirm(`Are you sure you want to ${action.replace('_', ' ')} this user?`)) return;
            try {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('user_id', userId);
                const response = await fetch('', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    location.reload();  // Or update row dynamically
                } else {
                    alert('Action failed.');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
    </script>
</body>
</html>