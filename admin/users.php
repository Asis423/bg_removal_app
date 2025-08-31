<?php
require_once '../includes/functions.php';
require_once '../config/session.php';

// Require admin access
requireAdmin();

// Get all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
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
                <li><a class="nav-link" href="dashboard.php">Admin Dashboard</a></li>
                <li><a class="nav-link active" href="users.php">Users</a></li>
                <li><a class="nav-link" href="uploads.php">Uploads</a></li>
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
            <h1>Manage Users</h1>
            <p>View and manage all registered users</p>
        </div>

        <div class="content-section">
            <div class="section-header">
                <h2>All Users (<?php echo count($users); ?>)</h2>
            </div>

            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Admin</th>
                            <th>Uploads Count</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <div class="user-info">
                                        <div class="user-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['is_admin'] ? 'admin' : 'user'; ?>">
                                        <?php echo $user['is_admin'] ? 'Admin' : 'User'; ?>
                                    </span>
                                </td>
                                <td><?php echo getUserUploadCount($user['id']); ?></td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="user_details.php?id=<?php echo $user['id']; ?>" class="btn-view">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-actions">
            <a href="dashboard.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
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
